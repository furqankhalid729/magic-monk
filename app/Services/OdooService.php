<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooService
{
    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;

    public function __construct()
    {
        $this->url = env('ODOO_URL');
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

        return $response->json('result');
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
        $partnerId = $this->findOrCreatePartner(
            $order->customer_name,
            $order->customer_phone
        );

        $invoiceId = $this->createInvoice($partnerId, $order);

        return [
            'partner_id' => $partnerId,
            'invoice_id' => $invoiceId,
            'invoice_url' => $this->getInvoiceUrl($invoiceId),
            'invoice_pdf_url' => $this->getInvoicePdfUrl($invoiceId),
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
                    [[
                        '|',
                        ['mobile', '=', $phone],
                        ['phone', '=', $phone],
                    ], 0, 1]
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
                    'mobile' => $phone,
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

    public function createInvoice(int $partnerId, Order $order): int
    {
        $invoiceLines = $this->buildInvoiceLines($order);

        $invoiceId = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'account.move',
                'create',
                [[
                    'move_type' => 'out_invoice',
                    'partner_id' => $partnerId,
                    'invoice_origin' => $order->order_id ?: (string) $order->id,
                    'ref' => $order->order_id ?: (string) $order->id,
                    'invoice_date' => now()->toDateString(),
                    'invoice_line_ids' => $invoiceLines,
                ]]
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

        $receiptId = $this->createReceipt($partnerId, $order, $posConfig);
        Log::info('Odoo receipt sync completed', [
            'order_id' => $order->id,
            'receipt_id' => $receiptId,
            'selected_pos_config' => $this->summarizePosConfig($posConfig),
        ]);

        return [
            'partner_id' => $partnerId,
            'receipt_id' => $receiptId,
            'receipt_url' => $this->getReceiptUrl($receiptId),
            'receipt_pdf_url' => $this->getReceiptPdfUrl($receiptId),
            'pos_config_id' => $posConfig['id'] ?? null,
            'pos_config_name' => $posConfig['name'] ?? null,
            'pos_session_id' => $posConfig['current_session_id'][0] ?? null,
            'pos_session_name' => $posConfig['current_session_id'][1] ?? null,
        ];
    }

    public function createReceipt(int $partnerId, Order $order, ?array $posConfig = null): int
    {
        $receiptLines = $this->buildInvoiceLines($order);
        $payload = [
            'move_type' => 'out_receipt',
            'partner_id' => $partnerId,
            'invoice_origin' => $order->order_id ?: (string) $order->id,
            'ref' => $order->order_id ?: (string) $order->id,
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

    protected function getReceiptUrl(int $receiptId): string
    {
        return rtrim((string) $this->url, '/') . "/web#id={$receiptId}&model=account.move&view_type=form";
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

        $openConfigs = $this->call(
            'object',
            'execute_kw',
            [
                $this->db,
                $this->uid,
                $this->password,
                'pos.config',
                'search_read',
                [[['current_session_id', '!=', false]]],
                [
                    'fields' => ['name', 'current_session_id', 'journal_id', 'invoice_journal_id'],
                    'limit' => 1,
                ]
            ]
        );

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
                    'fields' => ['name', 'current_session_id', 'journal_id', 'invoice_journal_id'],
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