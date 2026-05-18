
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\OdooService;
use App\Jobs\CheckLocationOffer;
use App\Jobs\ProcessOrderReminder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('odoo:sync-order {orderId : The system order ID to sync}', function (string $orderId) {
    $order = Order::with('items')->find($orderId);

    if (!$order) {
        $this->error("Order not found for ID: {$orderId}");

        return Command::FAILURE;
    }

    try {
        $odooSync = app(OdooService::class)->syncOrderInvoice($order);

        $order->update([
            'additional_info' => array_merge($order->additional_info ?? [], [
                'odoo_sync_status' => 'synced',
                'odoo_partner_id' => $odooSync['partner_id'] ?? null,
                'odoo_invoice_id' => $odooSync['invoice_id'] ?? null,
                'odoo_invoice_url' => $odooSync['invoice_url'] ?? null,
                'odoo_invoice_pdf_url' => $odooSync['invoice_pdf_url'] ?? null,
                'odoo_stored_invoice_pdf_disk' => $odooSync['stored_invoice_pdf_disk'] ?? null,
                'odoo_stored_invoice_pdf_path' => $odooSync['stored_invoice_pdf_path'] ?? null,
                'odoo_stored_invoice_pdf_full_path' => $odooSync['stored_invoice_pdf_full_path'] ?? null,
                'odoo_stored_invoice_pdf_url' => $odooSync['stored_invoice_pdf_url'] ?? null,
                'odoo_pos_config_id' => $odooSync['pos_config_id'] ?? null,
                'odoo_pos_config_name' => $odooSync['pos_config_name'] ?? null,
                'odoo_pos_session_id' => $odooSync['pos_session_id'] ?? null,
                'odoo_pos_session_name' => $odooSync['pos_session_name'] ?? null,
            ]),
        ]);

        $invoiceAttachmentUrl = $odooSync['stored_invoice_pdf_url'] ?? $odooSync['invoice_pdf_url'] ?? null;

        if (!empty($order->customer_phone) && !empty($invoiceAttachmentUrl)) {
            $interaktResponse = sendInteraktMessage(
                $order->customer_phone,
                [(string) ($order->order_id ?: $order->id)],
                [$invoiceAttachmentUrl],
                'feedback_w_nps_invoice',
                null
            );

            $order->update([
                'review_message_id' => $interaktResponse['id'] ?? $order->review_message_id,
                'additional_info' => array_merge($order->fresh()->additional_info ?? [], [
                    'feedback_invoice_template_name' => 'feedback_w_nps_invoice',
                    'feedback_invoice_attachment_url' => $invoiceAttachmentUrl,
                    'feedback_invoice_message_id' => $interaktResponse['id'] ?? null,
                    'feedback_invoice_send_error' => $interaktResponse['error'] ?? null,
                ]),
            ]);

            $this->line('Interakt Template: feedback_w_nps_invoice');
            $this->line('Interakt Attachment URL: ' . $invoiceAttachmentUrl);
            $this->line('Interakt Message ID: ' . ($interaktResponse['id'] ?? 'n/a'));

            if (!empty($interaktResponse['error'])) {
                $this->warn('Interakt send error: ' . $interaktResponse['error']);
            }
        }

        $this->info("Order {$order->id} synced with Odoo.");
        $this->line('POS Register: ' . ($odooSync['pos_config_name'] ?? 'n/a'));
        $this->line('POS Session: ' . ($odooSync['pos_session_name'] ?? 'n/a'));
        $this->line('Invoice ID: ' . ($odooSync['invoice_id'] ?? 'n/a'));
        $this->line('Invoice URL: ' . ($odooSync['invoice_url'] ?? 'n/a'));
        $this->line('Invoice PDF URL: ' . ($odooSync['invoice_pdf_url'] ?? 'n/a'));
        $this->line('Stored Invoice PDF: ' . ($odooSync['stored_invoice_pdf_full_path'] ?? 'n/a'));

        return Command::SUCCESS;
    } catch (\Throwable $throwable) {
        $order->update([
            'additional_info' => array_merge($order->additional_info ?? [], [
                'odoo_sync_status' => 'failed',
                'odoo_sync_error' => $throwable->getMessage(),
            ]),
        ]);

        $this->error('Failed to sync order with Odoo: ' . $throwable->getMessage());

        return Command::FAILURE;
    }
})->purpose('Sync an existing order to Odoo and generate an invoice');

Artisan::command('odoo:sync-receipt {orderId : The system order ID to sync}', function (string $orderId) {
    $order = Order::with('items')->find($orderId);

    if (!$order) {
        $this->error("Order not found for ID: {$orderId}");
        return Command::FAILURE;
    }

    try {
        $odooSync = app(OdooService::class)->syncOrderReceipt($order);

        $order->update([
            'additional_info' => array_merge($order->additional_info ?? [], [
                'odoo_sync_status' => 'synced',
                'odoo_partner_id' => $odooSync['partner_id'] ?? null,
                'odoo_receipt_id' => $odooSync['receipt_id'] ?? null,
                'odoo_receipt_url' => $odooSync['receipt_url'] ?? null,
                'odoo_receipt_pdf_url' => $odooSync['receipt_pdf_url'] ?? null,
                'odoo_pos_receipt_url' => $odooSync['pos_receipt_url'] ?? null,
                'odoo_pos_receipt_storage_disk' => $odooSync['stored_pos_receipt_disk'] ?? null,
                'odoo_pos_receipt_storage_path' => $odooSync['stored_pos_receipt_path'] ?? null,
                'odoo_pos_receipt_storage_full_path' => $odooSync['stored_pos_receipt_full_path'] ?? null,
                'odoo_pos_config_id' => $odooSync['pos_config_id'] ?? null,
                'odoo_pos_config_name' => $odooSync['pos_config_name'] ?? null,
                'odoo_pos_session_id' => $odooSync['pos_session_id'] ?? null,
                'odoo_pos_session_name' => $odooSync['pos_session_name'] ?? null,
            ]),
        ]);

        $this->info("Order {$order->id} synced as receipt with Odoo.");
        $this->line('POS Register: ' . ($odooSync['pos_config_name'] ?? 'n/a'));
        $this->line('POS Session: ' . ($odooSync['pos_session_name'] ?? 'n/a'));
        $this->line('POS Order ID: ' . ($odooSync['pos_order_id'] ?? 'n/a'));
        $this->line('POS Order Ref: ' . ($odooSync['pos_order_name'] ?? 'n/a'));
        $this->line('POS Receipt No: ' . ($odooSync['pos_order_reference'] ?? 'n/a'));
        $this->line('POS Receipt URL: ' . ($odooSync['pos_receipt_url'] ?? 'n/a'));
        $this->line('Stored POS Receipt: ' . ($odooSync['stored_pos_receipt_full_path'] ?? 'n/a'));
        $this->line('Receipt ID: ' . ($odooSync['receipt_id'] ?? 'n/a'));
        $this->line('Receipt URL: ' . ($odooSync['receipt_url'] ?? 'n/a'));
        $this->line('Receipt PDF URL: ' . ($odooSync['receipt_pdf_url'] ?? 'n/a'));

        return Command::SUCCESS;
    } catch (\Throwable $throwable) {
        $order->update([
            'additional_info' => array_merge($order->additional_info ?? [], [
                'odoo_sync_status' => 'failed',
                'odoo_sync_error' => $throwable->getMessage(),
            ]),
        ]);

        $this->error('Failed to sync order as receipt with Odoo: ' . $throwable->getMessage());
        return Command::FAILURE;
    }
})->purpose('Sync an existing order to Odoo and generate a receipt');

Schedule::job(new CheckLocationOffer)->everySixHours();
Schedule::job(new ProcessOrderReminder)->everyMinute();
