
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
            ]),
        ]);

        $this->info("Order {$order->id} synced with Odoo.");
        $this->line('Invoice ID: ' . ($odooSync['invoice_id'] ?? 'n/a'));
        $this->line('Invoice URL: ' . ($odooSync['invoice_url'] ?? 'n/a'));
        $this->line('Invoice PDF URL: ' . ($odooSync['invoice_pdf_url'] ?? 'n/a'));

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
                'odoo_pos_config_id' => $odooSync['pos_config_id'] ?? null,
                'odoo_pos_config_name' => $odooSync['pos_config_name'] ?? null,
                'odoo_pos_session_id' => $odooSync['pos_session_id'] ?? null,
                'odoo_pos_session_name' => $odooSync['pos_session_name'] ?? null,
            ]),
        ]);

        $this->info("Order {$order->id} synced as receipt with Odoo.");
        $this->line('POS Register: ' . ($odooSync['pos_config_name'] ?? 'n/a'));
        $this->line('POS Session: ' . ($odooSync['pos_session_name'] ?? 'n/a'));
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
