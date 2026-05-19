<?php

namespace App\Services;

use App\Models\Order;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OdooService
{
    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;

    public function __construct()
    {
        $this->url = $this->normalizeOdooUrl(env('ODOO_URL'));
        $this->db = env('ODOO_DB');
        $this->username = env('ODOO_USERNAME');
        $this->password = env('ODOO_PASSWORD');

        $this->uid = $this->authenticate();
    }

    protected function call($service, $method, $args = [])
    {
        $response = Http::post(
            "{$this->url}/jsonrpc",
            [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => $service,
                    'method' => $method,
                    'args' => $args,
                ],
                'id' => rand(),
            ]
        );

        if ($response->failed()) {
            Log::error('Odoo HTTP request failed', [
                'service' => $service,
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Odoo HTTP request failed with status ' . $response->status() . '.');
        }

        $payload = $response->json();

        if (!empty($payload['error'])) {
            Log::error('Odoo RPC error', [
                'service' => $service,
                'method' => $method,
                'args' => $args,
                'error' => $payload['error'],
            ]);

            $message = $payload['error']['data']['message']
                ?? $payload['error']['message']
                ?? 'Unknown Odoo RPC error.';

            throw new \RuntimeException($message);
        }

        return $payload['result'] ?? null;
    }

    protected function normalizeOdooUrl(?string $url): string
    {
        $url = rtrim(trim((string) $url), '/');

        if ($url === '') {
            throw new \RuntimeException('ODOO_URL is not configured. Set it to the full Odoo base URL, for example https://odoo.example.com.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) {
            throw new \RuntimeException('ODOO_URL is invalid. Set it to the full Odoo base URL, for example https://odoo.example.com.');
        }

        return $url;
    }

    protected function authenticate()
    {
        return $this->call(
            'common',
            'login',
            [
                $this->db,
                $this->username,
                $this->password,
            ]
        );
    }

    public function syncOrderInvoice(Order $order): array
    {
        Log::info('Odoo invoice sync started', [
            'order_id' => $order->id,
            'order_number' => $order->order_id,
            'building' => $order->building,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
        ]);

        $posConfig = $this->resolvePosConfigForBuilding($order->building);
        Log::info('Odoo invoice POS mapping resolved', [
            'order_id' => $order->id,
            'building' => $order->building,
            'selected_pos_config' => $this->summarizePosConfig($posConfig),
        ]);

        $partnerId = $this->findOrCreatePartner(
            $order->customer_name,
            $order->customer_phone
        );

        $invoiceId = $this->createInvoice($partnerId, $order, $posConfig);
        $storedInvoicePdf = $this->downloadAndStoreInvoicePdf($order, $invoiceId);

        Log::info('Odoo invoice sync completed', [
            'order_id' => $order->id,
            'invoice_id' => $invoiceId,
            'selected_pos_config' => $this->summarizePosConfig($posConfig),
            'stored_invoice' => $storedInvoicePdf,
        ]);

        return [
            'partner_id' => $partnerId,
            'invoice_id' => $invoiceId,
            'invoice_url' => $this->getInvoiceUrl($invoiceId),
            'invoice_pdf_url' => $this->getInvoicePdfUrl($invoiceId),
            'stored_invoice_pdf_disk' => $storedInvoicePdf['disk'] ?? null,
            'stored_invoice_pdf_path' => $storedInvoicePdf['path'] ?? null,
            'stored_invoice_pdf_full_path' => $storedInvoicePdf['full_path'] ?? null,
            'stored_invoice_pdf_url' => $storedInvoicePdf['url'] ?? null,
            'pos_config_id' => $posConfig['id'] ?? null,
            'pos_config_name' => $posConfig['name'] ?? null,
            'pos_session_id' => $posConfig['current_session_id'][0] ?? null,
            'pos_session_name' => $posConfig['current_session_id'][1] ?? null,
        ];
    }

    protected function findOrCreatePartner(?string $name, ?string $phone = null, ?string $email = null): int
    {
        $partnerId = null;

        if (!empty($phone)) {
            $partnerIds = $this->call(
                'object',
                'execute_kw',
                [
                    $this->db,
                    $this->uid,
                    $this->password,
                    'res.partner',
                    'search',
                    [[['phone', '=', $phone]], 0, 1]
                ]
            );

            if (!empty($partnerIds)) {
                $partnerId = $this->normalizeOdooId($partnerIds);
            }
        }

        if (!empty($partnerId)) {
            return $partnerId;
        }

        $createdPartnerId = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'res.partner',
                'create',
                [[
                    'name' => $name ?: $phone ?: 'Customer',
                    'phone' => $phone,
                    'email' => $email,
                ]]
            ]
        );

        Log::info('Odoo partner created', [
            'name' => $name,
            'phone' => $phone,
            'raw_partner_id' => $createdPartnerId,
            'partner_id' => $this->normalizeOdooId($createdPartnerId),
        ]);

        return $this->normalizeOdooId($createdPartnerId);
    }

    public function createInvoice(int $partnerId, Order $order, ?array $posConfig = null): int
    {
        $invoiceLines = $this->buildInvoiceLines($order);
        $payload = [
            'move_type' => 'out_invoice',
            'partner_id' => $partnerId,
            'invoice_origin' => $order->order_id ?: (string) $order->id,
            'ref' => $order->order_id ?: (string) $order->id,
            'invoice_date' => now()->toDateString(),
            'invoice_line_ids' => $invoiceLines,
        ];

        if (!empty($posConfig['invoice_journal_id'][0])) {
            $payload['journal_id'] = $posConfig['invoice_journal_id'][0];
        }

        if (!empty($posConfig['name'])) {
            $payload['narration'] = 'POS Register: ' . $posConfig['name'];
        }

        Log::info('Creating Odoo invoice', [
            'order_id' => $order->id,
            'partner_id' => $partnerId,
            'pos_config' => $this->summarizePosConfig($posConfig),
            'payload' => [
                'move_type' => $payload['move_type'],
                'partner_id' => $payload['partner_id'],
                'invoice_origin' => $payload['invoice_origin'],
                'ref' => $payload['ref'],
                'invoice_date' => $payload['invoice_date'],
                'journal_id' => $payload['journal_id'] ?? null,
                'narration' => $payload['narration'] ?? null,
                'invoice_line_count' => count($invoiceLines),
            ],
        ]);

        $invoiceId = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'account.move',
                'create',
                [[$payload]]
            ]
        );

        if (empty($invoiceId)) {
            throw new \RuntimeException('Unable to create invoice in Odoo.');
        }

        $invoiceId = $this->normalizeOdooId($invoiceId);

        // Confirm invoice
        $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'account.move',
                'action_post',
                [[$invoiceId]]
            ]
        );

        return $invoiceId;
    }

    protected function buildInvoiceLines(Order $order): array
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        $invoiceLines = [];

        foreach ($items as $item) {
            $invoiceLines[] = [0, 0, [
                'name' => $item->item_name ?: 'Order Item',
                'quantity' => max(1, (float) ($item->quantity ?? 1)),
                'price_unit' => (float) ($item->price ?? $item->amount ?? 0),
            ]];
        }

        if (empty($invoiceLines)) {
            $invoiceLines[] = [0, 0, [
                'name' => $order->order_id ? "Order {$order->order_id}" : "Order {$order->id}",
                'quantity' => 1,
                'price_unit' => (float) ($order->total_amount ?? 0),
            ]];
        }

        return $invoiceLines;
    }

    protected function getInvoiceUrl(int $invoiceId): string
    {
        return rtrim((string) $this->url, '/') . "/web#id={$invoiceId}&model=account.move&view_type=form";
    }

    protected function getInvoicePdfUrl(int $invoiceId): string
    {
        $reportName = $this->resolveInvoiceReportName();

        return rtrim((string) $this->url, '/') . "/report/pdf/{$reportName}/{$invoiceId}";
    }

    protected function downloadAndStoreInvoicePdf(Order $order, int $invoiceId): array
    {
        $invoicePdfUrl = $this->getInvoicePdfUrl($invoiceId);
        $cookieJar = new CookieJar();

        $authResponse = Http::withOptions(['cookies' => $cookieJar])
            ->acceptJson()
            ->post(rtrim((string) $this->url, '/') . '/web/session/authenticate', [
                'jsonrpc' => '2.0',
                'params' => [
                    'db' => $this->db,
                    'login' => $this->username,
                    'password' => $this->password,
                ],
            ]);

        if ($authResponse->failed()) {
            throw new \RuntimeException('Unable to authenticate Odoo web session for invoice PDF download.');
        }

        $invoiceResponse = Http::withOptions(['cookies' => $cookieJar])
            ->accept('application/pdf')
            ->get($invoicePdfUrl);

        if ($invoiceResponse->failed()) {
            throw new \RuntimeException('Unable to download Odoo invoice PDF.');
        }

        $directory = 'odoo-invoices';
        $filename = sprintf(
            '%s-%s-%s.pdf',
            now()->format('YmdHis'),
            $order->id,
            Str::slug((string) ($order->order_id ?: $invoiceId ?: 'invoice')),
        );
        $path = $directory . '/' . $filename;

        $disk = Storage::disk('public');

        Log::info('Odoo invoice PDF storage context', [
            'order_id' => $order->id,
            'invoice_id' => $invoiceId,
            'running_in_console' => app()->runningInConsole(),
            'php_sapi' => PHP_SAPI,
            'disk_root' => (string) config('filesystems.disks.public.root'),
            'disk_url' => (string) config('filesystems.disks.public.url'),
            'public_storage_path' => public_path('storage'),
            'public_storage_exists' => file_exists(public_path('storage')),
            'public_storage_is_link' => is_link(public_path('storage')),
            'public_storage_realpath' => realpath(public_path('storage')) ?: null,
            'storage_public_realpath' => realpath(storage_path('app/public')) ?: null,
            'target_path' => $path,
        ]);

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $stored = $disk->put($path, $invoiceResponse->body());
        $exists = $disk->exists($path);
        $size = $exists ? $disk->size($path) : null;

        if (! $stored || ! $exists || empty($size)) {
            throw new \RuntimeException(sprintf(
                'Invoice PDF storage verification failed. disk=public path=%s stored=%s exists=%s size=%s full_path=%s',
                $path,
                $stored ? 'true' : 'false',
                $exists ? 'true' : 'false',
                $size ?? 'null',
                $disk->path($path),
            ));
        }

        $storedInvoice = [
            'disk' => 'public',
            'path' => $path,
            'full_path' => $disk->path($path),
            'url' => rtrim((string) config('filesystems.disks.public.url'), '/') . '/' . ltrim($path, '/'),
            'size' => $size,
        ];

        Log::info('Stored Odoo invoice PDF', [
            'order_id' => $order->id,
            'invoice_id' => $invoiceId,
            'invoice_pdf_url' => $invoicePdfUrl,
            'stored_invoice' => $storedInvoice,
        ]);

        return $storedInvoice;
    }

    protected function resolveInvoiceReportName(): string
    {
        $reportIds = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'ir.actions.report',
                'search',
                [[
                    ['model', '=', 'account.move'],
                    ['report_type', '=', 'qweb-pdf'],
                    '|',
                    ['report_name', '=', 'account.report_invoice'],
                    ['report_name', '=', 'account.report_invoice_with_payments'],
                ], 0, 1]
            ]
        );

        if (empty($reportIds)) {
            return 'account.report_invoice';
        }

        $reports = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'ir.actions.report',
                'read',
                [[$reportIds[0]], ['report_name']]
            ]
        );

        return $reports[0]['report_name'] ?? 'account.report_invoice';
    }

    public function syncOrderReceipt(Order $order): array
    {
        Log::info('Odoo receipt sync started', [
            'order_id' => $order->id,
            'order_number' => $order->order_id,
            'building' => $order->building,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
        ]);

        $posConfig = $this->resolvePosConfigForBuilding($order->building);
        Log::info('Odoo receipt POS mapping resolved', [
            'order_id' => $order->id,
            'building' => $order->building,
            'selected_pos_config' => $this->summarizePosConfig($posConfig),
        ]);

        $partnerId = $this->findOrCreatePartner(
            $order->customer_name,
            $order->customer_phone
        );
        Log::info('Odoo receipt partner resolved', [
            'order_id' => $order->id,
            'partner_id' => $partnerId,
        ]);

        $posOrder = $this->createPosOrder($partnerId, $order, $posConfig);
        Log::info('Odoo POS order sync completed', [
            'order_id' => $order->id,
            'pos_order' => $posOrder,
        ]);

        $receiptId = $this->createReceipt($partnerId, $order, $posConfig, $posOrder);
        $storedReceipt = $this->downloadAndStorePosReceipt($order, $posOrder);
        Log::info('Odoo receipt sync completed', [
            'order_id' => $order->id,
            'receipt_id' => $receiptId,
            'selected_pos_config' => $this->summarizePosConfig($posConfig),
            'pos_order' => $posOrder,
            'stored_receipt' => $storedReceipt,
        ]);

        return [
            'partner_id' => $partnerId,
            'pos_order_id' => $posOrder['id'] ?? null,
            'pos_order_name' => $posOrder['name'] ?? null,
            'pos_order_reference' => $posOrder['pos_reference'] ?? null,
            'pos_receipt_url' => $posOrder['receipt_url'] ?? null,
            'receipt_id' => $receiptId,
            'receipt_url' => $this->getReceiptUrl($receiptId),
            'receipt_pdf_url' => $this->getReceiptPdfUrl($receiptId),
            'stored_pos_receipt_disk' => $storedReceipt['disk'] ?? null,
            'stored_pos_receipt_path' => $storedReceipt['path'] ?? null,
            'stored_pos_receipt_full_path' => $storedReceipt['full_path'] ?? null,
            'pos_config_id' => $posConfig['id'] ?? null,
            'pos_config_name' => $posConfig['name'] ?? null,
            'pos_session_id' => $posConfig['current_session_id'][0] ?? null,
            'pos_session_name' => $posConfig['current_session_id'][1] ?? null,
        ];
    }

    public function createReceipt(int $partnerId, Order $order, ?array $posConfig = null, ?array $posOrder = null): int
    {
        $receiptLines = $this->buildInvoiceLines($order);
        $payload = [
            'move_type' => 'out_receipt',
            'partner_id' => $partnerId,
            'invoice_origin' => $posOrder['name'] ?? ($order->order_id ?: (string) $order->id),
            'ref' => $posOrder['pos_reference'] ?? ($order->order_id ?: (string) $order->id),
            'invoice_date' => now()->toDateString(),
            'invoice_line_ids' => $receiptLines,
        ];

        if (!empty($posConfig['invoice_journal_id'][0])) {
            $payload['journal_id'] = $posConfig['invoice_journal_id'][0];
        }

        if (!empty($posConfig['name'])) {
            $payload['narration'] = 'POS Register: ' . $posConfig['name'];
        }

        Log::info('Creating Odoo receipt', [
            'order_id' => $order->id,
            'partner_id' => $partnerId,
            'pos_config' => $this->summarizePosConfig($posConfig),
            'line_count' => count($receiptLines),
            'payload' => [
                'move_type' => $payload['move_type'],
                'partner_id' => $payload['partner_id'],
                'invoice_origin' => $payload['invoice_origin'],
                'ref' => $payload['ref'],
                'invoice_date' => $payload['invoice_date'],
                'journal_id' => $payload['journal_id'] ?? null,
                'narration' => $payload['narration'] ?? null,
            ],
        ]);

        $receiptId = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'account.move',
                'create',
                [[$payload]]
            ]
        );

        if (empty($receiptId)) {
            Log::error('Odoo receipt creation returned empty ID', [
                'order_id' => $order->id,
                'pos_config' => $this->summarizePosConfig($posConfig),
            ]);
            throw new \RuntimeException('Unable to create receipt in Odoo.');
        }

        $rawReceiptId = $receiptId;
        $receiptId = $this->normalizeOdooId($receiptId);

        Log::info('Odoo receipt created', [
            'order_id' => $order->id,
            'raw_receipt_id' => $rawReceiptId,
            'receipt_id' => $receiptId,
        ]);

        // Confirm receipt
        $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'account.move',
                'action_post',
                [[$receiptId]]
            ]
        );

        Log::info('Odoo receipt posted', [
            'order_id' => $order->id,
            'receipt_id' => $receiptId,
        ]);

        return $receiptId;
    }

    protected function createPosOrder(int $partnerId, Order $order, ?array $posConfig): array
    {
        if (empty($posConfig['id']) || empty($posConfig['current_session_id'][0])) {
            throw new \RuntimeException('No open POS session found for the selected building.');
        }

        $paymentMethodId = $this->resolvePosPaymentMethodId($posConfig);
        $totalAmount = (float) ($order->total_amount ?? 0);
        $linePayloads = $this->buildPosOrderLinePayloads($order);

        Log::info('Creating Odoo POS order', [
            'order_id' => $order->id,
            'session_id' => $posConfig['current_session_id'][0],
            'config_id' => $posConfig['id'],
            'payment_method_id' => $paymentMethodId,
            'line_payloads' => $linePayloads,
            'total_amount' => $totalAmount,
        ]);

        $orderPayload = [
            'session_id' => $posConfig['current_session_id'][0],
            'config_id' => $posConfig['id'],
            'partner_id' => $partnerId ?: false,
            'amount_total' => $totalAmount,
            'amount_tax' => 0,
            'amount_paid' => 0,
            'amount_return' => 0,
            'lines' => $linePayloads,
            'tracking_number' => (string) ($order->order_id ?: $order->id),
            'general_customer_note' => 'Magic Monk order #' . ($order->order_id ?: $order->id),
        ];

        $posOrderId = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.order',
                'create',
                [[$orderPayload]]
            ]
        );

        if (empty($posOrderId)) {
            throw new \RuntimeException('Unable to create POS order in Odoo.');
        }

        $posOrderId = $this->normalizeOdooId($posOrderId);

        Log::info('Odoo POS order created', [
            'order_id' => $order->id,
            'pos_order_id' => $posOrderId,
        ]);

        $posPaymentId = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.payment',
                'create',
                [[
                    'pos_order_id' => $posOrderId,
                    'payment_method_id' => $paymentMethodId,
                    'amount' => $totalAmount,
                    'payment_ref_no' => (string) ($order->order_id ?: $order->id),
                ]]
            ]
        );

        $posPaymentId = $this->normalizeOdooId($posPaymentId);

        Log::info('Odoo POS payment created', [
            'order_id' => $order->id,
            'pos_order_id' => $posOrderId,
            'pos_payment_id' => $posPaymentId,
            'payment_method_id' => $paymentMethodId,
            'amount' => $totalAmount,
        ]);

        $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.order',
                'write',
                [[$posOrderId], [
                    'amount_paid' => $totalAmount,
                    'amount_return' => 0,
                ]]
            ]
        );

        Log::info('Odoo POS order payment totals updated', [
            'order_id' => $order->id,
            'pos_order_id' => $posOrderId,
            'amount_paid' => $totalAmount,
        ]);

        $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.order',
                'action_pos_order_paid',
                [[$posOrderId]]
            ]
        );

        Log::info('Odoo POS order marked paid', [
            'order_id' => $order->id,
            'pos_order_id' => $posOrderId,
        ]);

        $posOrders = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.order',
                'read',
                [[$posOrderId], ['id', 'name', 'uuid', 'pos_reference', 'tracking_number', 'state', 'session_id', 'config_id']]
            ]
        );

        return [
            'id' => $posOrderId,
            'name' => $posOrders[0]['name'] ?? null,
            'pos_reference' => $posOrders[0]['pos_reference'] ?? null,
            'uuid' => $posOrders[0]['uuid'] ?? null,
            'tracking_number' => $posOrders[0]['tracking_number'] ?? null,
            'state' => $posOrders[0]['state'] ?? null,
            'session_id' => $posOrders[0]['session_id'][0] ?? null,
            'session_name' => $posOrders[0]['session_id'][1] ?? null,
            'config_id' => $posOrders[0]['config_id'][0] ?? null,
            'config_name' => $posOrders[0]['config_id'][1] ?? null,
            'receipt_url' => $this->getPosReceiptUrl(
                $posOrders[0]['config_id'][0] ?? null,
                $posOrders[0]['uuid'] ?? null,
            ),
        ];
    }

    protected function downloadAndStorePosReceipt(Order $order, array $posOrder): array
    {
        if (empty($posOrder['config_id']) || empty($posOrder['uuid'])) {
            Log::warning('Skipping POS receipt download because receipt identity is incomplete', [
                'order_id' => $order->id,
                'pos_order' => $posOrder,
            ]);

            return [];
        }

        $receiptUrl = $this->getPosReceiptUrl($posOrder['config_id'], $posOrder['uuid']);
        $cookieJar = new CookieJar();

        $authResponse = Http::withOptions(['cookies' => $cookieJar])
            ->acceptJson()
            ->post(rtrim((string) $this->url, '/') . '/web/session/authenticate', [
                'jsonrpc' => '2.0',
                'params' => [
                    'db' => $this->db,
                    'login' => $this->username,
                    'password' => $this->password,
                ],
            ]);

        if ($authResponse->failed()) {
            throw new \RuntimeException('Unable to authenticate Odoo web session for POS receipt download.');
        }

        $receiptResponse = Http::withOptions(['cookies' => $cookieJar])
            ->get($receiptUrl);

        if ($receiptResponse->failed()) {
            throw new \RuntimeException('Unable to download Odoo POS receipt HTML.');
        }

        $directory = 'odoo-receipts';
        $filename = sprintf(
            '%s-%s-%s.html',
            now()->format('YmdHis'),
            $order->id,
            Str::slug((string) ($posOrder['pos_reference'] ?? $posOrder['name'] ?? 'receipt')),
        );
        $path = $directory . '/' . $filename;

        Storage::disk('local')->put($path, $receiptResponse->body());

        $storedReceipt = [
            'disk' => 'local',
            'path' => $path,
            'full_path' => Storage::disk('local')->path($path),
        ];

        Log::info('Stored Odoo POS receipt HTML', [
            'order_id' => $order->id,
            'receipt_url' => $receiptUrl,
            'stored_receipt' => $storedReceipt,
        ]);

        return $storedReceipt;
    }

    protected function buildPosOrderLinePayloads(Order $order): array
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        $linePayloads = [];
        $lineTotal = 0.0;
        $lineNumber = 1;

        foreach ($items as $item) {
            $quantity = max(1, (float) ($item->quantity ?? 1));
            $unitPrice = (float) ($item->price ?? 0);

            if ($unitPrice <= 0 && !empty($item->amount)) {
                $unitPrice = (float) $item->amount / $quantity;
            }

            $product = $this->resolveOrCreatePosProduct($item->item_name, $unitPrice);
            $subtotal = round($unitPrice * $quantity, 2);
            $linePayloads[] = [0, 0, [
                'name' => str_pad((string) $lineNumber, 6, '0', STR_PAD_LEFT),
                'product_id' => $product['id'],
                'full_product_name' => $product['name'],
                'qty' => $quantity,
                'price_unit' => $unitPrice,
                'price_subtotal' => $subtotal,
                'price_subtotal_incl' => $subtotal,
                'discount' => 0,
            ]];

            $lineTotal += $unitPrice * $quantity;
            $lineNumber++;
        }

        $delta = round((float) ($order->total_amount ?? 0) - $lineTotal, 2);

        if (abs($delta) > 0.009) {
            $adjustmentProduct = $this->resolveOrCreatePosProduct('Magic Monk POS Adjustment', $delta);
            $subtotal = round($delta, 2);
            $linePayloads[] = [0, 0, [
                'name' => str_pad((string) $lineNumber, 6, '0', STR_PAD_LEFT),
                'product_id' => $adjustmentProduct['id'],
                'full_product_name' => 'Magic Monk POS Adjustment',
                'qty' => 1,
                'price_unit' => $delta,
                'price_subtotal' => $subtotal,
                'price_subtotal_incl' => $subtotal,
                'discount' => 0,
            ]];

            Log::info('Added POS adjustment line for order total delta', [
                'order_id' => $order->id,
                'line_total' => $lineTotal,
                'order_total' => (float) ($order->total_amount ?? 0),
                'delta' => $delta,
            ]);
        }

        return $linePayloads;
    }

    protected function resolvePosPaymentMethodId(?array $posConfig): int
    {
        $paymentMethodIds = $posConfig['payment_method_ids'] ?? [];

        if (empty($paymentMethodIds)) {
            throw new \RuntimeException('No payment methods configured for the selected POS register.');
        }

        $paymentMethods = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.payment.method',
                'read',
                [$paymentMethodIds, ['id', 'name', 'type', 'active']]
            ]
        );

        Log::info('Odoo POS payment methods resolved', [
            'pos_config_id' => $posConfig['id'] ?? null,
            'payment_methods' => $paymentMethods,
        ]);

        foreach ($paymentMethods as $paymentMethod) {
            if (($paymentMethod['type'] ?? null) !== 'online') {
                return (int) $paymentMethod['id'];
            }
        }

        return (int) $paymentMethods[0]['id'];
    }

    protected function resolveOrCreatePosProduct(?string $name, float $price): array
    {
        $name = trim((string) $name) ?: 'Magic Monk POS Item';
        $product = $this->findPosProductByName($name);

        if ($product !== null) {
            Log::info('Matched Odoo POS product for order item', [
                'item_name' => $name,
                'product' => $product,
            ]);

            return $product;
        }

        $templateId = $this->normalizeOdooId($this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'product.template',
                'create',
                [[
                    'name' => $name,
                    'list_price' => $price,
                    'available_in_pos' => true,
                    'sale_ok' => true,
                    'purchase_ok' => false,
                    'type' => 'consu',
                ]]
            ]
        ));

        $productIds = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'product.product',
                'search',
                [[[ 'product_tmpl_id', '=', $templateId ]], 0, 1]
            ]
        );

        $productId = $this->normalizeOdooId($productIds);
        $products = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'product.product',
                'read',
                [[$productId], ['id', 'name']]
            ]
        );

        $product = [
            'id' => $productId,
            'name' => $products[0]['name'] ?? $name,
        ];

        Log::info('Created Odoo POS product for order item', [
            'item_name' => $name,
            'template_id' => $templateId,
            'product' => $product,
            'price' => $price,
        ]);

        return $product;
    }

    protected function findPosProductByName(string $name): ?array
    {
        $searchTerms = array_values(array_unique(array_filter([
            $name,
            trim((string) preg_replace('/\s*\(.*/', '', $name)),
        ])));

        foreach ($searchTerms as $searchTerm) {
            $productIds = $this->call(
                'object',
                'execute_kw',
                [
                    $this->db,
                    $this->uid,
                    $this->password,
                    'product.product',
                    'search',
                    [[
                        ['available_in_pos', '=', true],
                        ['name', 'ilike', $searchTerm],
                    ], 0, 1]
                ]
            );

            if (empty($productIds)) {
                continue;
            }

            $productId = $this->normalizeOdooId($productIds);
            $products = $this->call(
                'object',
                'execute_kw',
                [
                    $this->db,
                    $this->uid,
                    $this->password,
                    'product.product',
                    'read',
                    [[$productId], ['id', 'name']]
                ]
            );

            return [
                'id' => $productId,
                'name' => $products[0]['name'] ?? $searchTerm,
            ];
        }

        return null;
    }

    protected function getReceiptUrl(int $receiptId): string
    {
        return rtrim((string) $this->url, '/') . "/web#id={$receiptId}&model=account.move&view_type=form";
    }

    protected function getPosReceiptUrl(?int $configId, ?string $uuid): ?string
    {
        if (empty($configId) || empty($uuid)) {
            return null;
        }

        return rtrim((string) $this->url, '/') . "/pos/ui/{$configId}/receipt/{$uuid}";
    }

    protected function getReceiptPdfUrl(int $receiptId): string
    {
        $reportName = $this->resolveReceiptReportName();
        return rtrim((string) $this->url, '/') . "/report/pdf/{$reportName}/{$receiptId}";
    }

    protected function resolveReceiptReportName(): string
    {
        $reportIds = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'ir.actions.report',
                'search',
                [[
                    ['model', '=', 'account.move'],
                    ['report_type', '=', 'qweb-pdf'],
                    '|',
                    ['report_name', '=', 'account.report_receipt'],
                    ['report_name', '=', 'account.report_receipt_with_payments'],
                ], 0, 1]
            ]
        );

        if (empty($reportIds)) {
            return 'account.report_receipt';
        }

        $reports = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'ir.actions.report',
                'read',
                [[$reportIds[0]], ['report_name']]
            ]
        );

        return $reports[0]['report_name'] ?? 'account.report_receipt';
    }

    protected function resolvePosConfigForBuilding(?string $building): ?array
    {
        $building = trim((string) $building);
        $searchTerms = array_values(array_filter([
            $building,
            trim((string) preg_replace('/\s*\(.*/', '', $building)),
        ]));

        Log::info('Resolving Odoo POS config for building', [
            'building' => $building,
            'search_terms' => $searchTerms,
        ]);

        foreach ($searchTerms as $searchTerm) {
            $posConfigs = $this->searchPosConfigsByName($searchTerm, 5);

            Log::info('Odoo POS candidates found for building search term', [
                'search_term' => $searchTerm,
                'candidate_count' => count($posConfigs),
                'candidates' => array_map([$this, 'summarizePosConfig'], $posConfigs),
            ]);

            if (!empty($posConfigs)) {
                Log::info('Odoo POS config selected from search term', [
                    'search_term' => $searchTerm,
                    'selected' => $this->summarizePosConfig($posConfigs[0]),
                ]);
                return $posConfigs[0];
            }
        }

        $openConfigs = array_values(array_filter(
            $this->searchPosConfigsByName('', 20),
            static fn (array $posConfig): bool => !empty($posConfig['current_session_id'][0])
        ));

        Log::info('Odoo POS open-session fallback candidates', [
            'candidate_count' => count($openConfigs),
            'candidates' => array_map([$this, 'summarizePosConfig'], $openConfigs),
        ]);

        if (!empty($openConfigs)) {
            Log::info('Odoo POS config selected from open-session fallback', [
                'selected' => $this->summarizePosConfig($openConfigs[0]),
            ]);

            return $openConfigs[0];
        }

        $fallbackConfigs = $this->searchPosConfigsByName('', 1);

        Log::info('Odoo POS final fallback candidates', [
            'candidate_count' => count($fallbackConfigs),
            'candidates' => array_map([$this, 'summarizePosConfig'], $fallbackConfigs),
        ]);

        if (!empty($fallbackConfigs)) {
            Log::warning('Odoo POS config selected from final fallback', [
                'selected' => $this->summarizePosConfig($fallbackConfigs[0]),
            ]);
        } else {
            Log::warning('No Odoo POS config could be resolved', [
                'building' => $building,
            ]);
        }

        return $fallbackConfigs[0] ?? null;
    }

    protected function searchPosConfigsByName(string $name, int $limit): array
    {
        $domain = [];

        if ($name !== '') {
            $domain[] = ['name', 'ilike', $name];
        }

        return $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.config',
                'search_read',
                [$domain],
                [
                    'fields' => ['name', 'current_session_id', 'payment_method_ids', 'journal_id', 'invoice_journal_id'],
                    'limit' => $limit,
                    'order' => 'id asc',
                ]
            ]
        );
    }

    protected function summarizePosConfig(?array $posConfig): ?array
    {
        if (empty($posConfig)) {
            return null;
        }

        return [
            'id' => $posConfig['id'] ?? null,
            'name' => $posConfig['name'] ?? null,
            'session_id' => $posConfig['current_session_id'][0] ?? null,
            'session_name' => $posConfig['current_session_id'][1] ?? null,
            'payment_method_ids' => $posConfig['payment_method_ids'] ?? [],
            'journal_id' => $posConfig['journal_id'][0] ?? null,
            'journal_name' => $posConfig['journal_id'][1] ?? null,
            'invoice_journal_id' => $posConfig['invoice_journal_id'][0] ?? null,
            'invoice_journal_name' => $posConfig['invoice_journal_id'][1] ?? null,
        ];
    }

    protected function normalizeOdooId($value): int
    {
        if (is_array($value)) {
            if ($value === []) {
                return 0;
            }

            $firstValue = reset($value);

            return $this->normalizeOdooId($firstValue);
        }

        return (int) $value;
    }
}