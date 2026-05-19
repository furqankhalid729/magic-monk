<?php

namespace App\Http\Controllers;

use App\Services\OdooService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Order;

class RazorPayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Handle the RazorPay webhook payload
        $payload = $request->all();
        Log::info('RazorPay Webhook received', $payload);
        if (($payload['event'] ?? null) === 'payment.captured') {
            $paymentId = data_get($payload, 'payload.payment.entity.id');

            if (! empty($paymentId)) {
                $webhookCacheKey = "razorpay-webhook-payment-captured:{$paymentId}";

                if (! Cache::add($webhookCacheKey, true, now()->addMinutes(15))) {
                    Log::info('Duplicate RazorPay payment.captured webhook ignored', [
                        'payment_id' => $paymentId,
                    ]);

                    return response()->json(['status' => 'duplicate_ignored'], 200);
                }
            }

            $customerPhone = $payload['payload']['payment']['entity']['contact'] ?? null;
            $orderData = Cache::get('razorPay-' . $customerPhone);
            if ($orderData) {
                // Update payment status
                $orderData = $this->updateOrderData($orderData, $payload['payload']['payment']['entity']);
            } else {
                Log::warning("No order data found in cache for customer phone: $customerPhone");
            }
        }
        return response()->json(['status' => 'success'], 200);
    }

    private function updateOrderData($cacheData, $paymentDetails)
    {
        $paymentId = $paymentDetails['id'] ?? null;

        if (! empty($paymentId)) {
            $processingCacheKey = "razorpay-order-update:{$paymentId}";

            if (! Cache::add($processingCacheKey, true, now()->addMinutes(15))) {
                Log::info('Duplicate RazorPay order update ignored', [
                    'payment_id' => $paymentId,
                    'order_id' => $cacheData['system_order_id'] ?? null,
                ]);

                return $cacheData;
            }
        }

        $amountPaid = $paymentDetails['amount'] / 100;
        if (!empty($cacheData['expo']['token'])) {
            $message = sendExpoPushNotification(
                $cacheData['expo']['token'],
                $cacheData['expo']['title'],
                $cacheData['expo']['body'],
                $cacheData['expo']['data']
            );
            Log::info("Expo notification sent", ['response' => $message]);
        }
        $agent = getAgentPhoneNumber($cacheData['building']);

        $key = "we-fast-{$cacheData['customer_phone']}";
        if (Cache::has($key)) {
            sendInteraktMessage(
                $cacheData['customer_phone'],
                [
                    $agent['building'] ?? null,
                    $cacheData['agent_number'] ?? null,
                    $cacheData['order_id']
                ],
                ['https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/LG0h8e5v7GRS/WhatsApp%20Video%202025-08-24%20at%2023.34.38.mp4?se=2030-08-27T21%3A17%3A03Z&sp=rt&sv=2019-12-12&sr=b&sig=P3tgreah5p8KjEWQmE%2BdFfnjKNHl83xKwAtgj1HVOSA%3D'],
                'wforderconfirmationvideo',
                null
            );
        } else {
            sendInteraktMessage(
                $cacheData['customer_phone'],
                [
                    $cacheData['order_id'],
                    "Infiniti",
                    $cacheData['total_amount'],
                    "been successfully received"
                ],
                ['https://fm.monkmagic.in/storage/videos/about-fruit.mp4'],
                'wforderconfirmationvideo',
                null
            );
        }

        $order = Order::where('id', $cacheData['system_order_id'])->first();
        if($order){
            $order->update([
                'payment_status' => 'paid',
                'additional_info' => array_merge($order->additional_info ?? [], [
                    'paid_online' => $amountPaid,
                    'to_collect' => max(0, ($order->additional_info['to_collect'] ?? 0) - $amountPaid),
                    'payment_status' => 'paid'
                ])
            ]);

            try {
                $order->loadMissing('items');
                $odooSync = app(OdooService::class)->syncOrderInvoice($order);

                $storedInvoiceDisk = $odooSync['stored_invoice_pdf_disk'] ?? null;
                $storedInvoicePath = $odooSync['stored_invoice_pdf_path'] ?? null;

                if (empty($storedInvoiceDisk) || empty($storedInvoicePath)) {
                    throw new \RuntimeException('Missing stored invoice PDF disk/path after Odoo sync.');
                }

                $storedInvoiceStorage = Storage::disk($storedInvoiceDisk);
                $storedInvoiceExists = $storedInvoiceStorage->exists($storedInvoicePath);
                $storedInvoiceSize = $storedInvoiceExists ? $storedInvoiceStorage->size($storedInvoicePath) : null;

                if (! $storedInvoiceExists || empty($storedInvoiceSize)) {
                    throw new \RuntimeException(sprintf(
                        'Stored invoice PDF not found after Odoo sync. disk=%s path=%s size=%s full_path=%s',
                        $storedInvoiceDisk,
                        $storedInvoicePath,
                        $storedInvoiceSize ?? 'null',
                        $storedInvoiceStorage->path($storedInvoicePath),
                    ));
                }

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
                    ])
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

                    Log::info('Sent invoice feedback Interakt template', [
                        'order_id' => $order->id,
                        'template' => 'feedback_w_nps_invoice',
                        'attachment_url' => $invoiceAttachmentUrl,
                        'response' => $interaktResponse,
                    ]);
                }

                Log::info('Order synced with Odoo', [
                    'order_id' => $order->id,
                    'invoice_id' => $odooSync['invoice_id'] ?? null,
                    'invoice_url' => $odooSync['invoice_url'] ?? null,
                ]);
            } catch (\Throwable $throwable) {
                Log::error('Failed to sync order with Odoo', [
                    'order_id' => $order->id,
                    'error' => $throwable->getMessage(),
                ]);

                $order->update([
                    'additional_info' => array_merge($order->additional_info ?? [], [
                        'odoo_sync_status' => 'failed',
                        'odoo_sync_error' => $throwable->getMessage(),
                    ])
                ]);
            }
        } else {
            Log::error("Order not found for system_order_id: {$cacheData['system_order_id']}");
        }

        // Create Order
        // $order = Order::create([
        //     'customer_name'  => $cacheData['customer_name'] ?? null,
        //     'order_id'       => $cacheData['order_id'] ?? null,
        //     'customer_phone' => $cacheData['customer_phone'] ?? null,
        //     'building'       => $cacheData['building'] ?? null,
        //     'order_time'     => $cacheData['order_time'] ?? now('Asia/Kolkata'),
        //     'delivery_time'  => $cacheData['delivery_time'] ?? now('Asia/Kolkata')->addMinutes(5),
        //     'agent_number'   => $cacheData['agent_number'] ?? null,
        //     'total_amount'   => $cacheData['total_amount'] ?? null,
        //     'address'        => $cacheData['address'] ?? null,
        //     'expo_token'     => data_get($cacheData, 'expo.token'),
        //     'payment_status' => 'paid',
        //     'additional_info' => [
        //         'paid_online' => $cacheData['additional_info']['paid_online'] - $amountPaid ?? 0,
        //         'to_collect'  => $cacheData['additional_info']['to_collect'] - $amountPaid ?? 0,
        //         'payment_status' => 'paid',
        //         'first_time_discount' => $cacheData['additional_info']['first_time_discount'] ?? null
        //     ]
        // ]);

        // foreach ($cacheData['order_items'] ?? [] as $item) {
        //     OrderItem::create([
        //         'order_id'  => $order->id,
        //         'item_name' => $item['item_name'],
        //         'price'     => $item['price'],
        //         'quantity'  => $item['quantity'],
        //         'amount'    => $item['amount'],
        //     ]);
        // }
    }

    public function generatePayment(Request $request)
    {
        $product = $request->query('product');

        if (!empty($product)) {
            $product = json_decode('"' . $product . '"');

            // Extract numbers (price)
            preg_match('/\d+(\.\d+)?/', $product, $matches);

            $price = $matches[0] ?? null;
            $url = generatePaymentLink("test", "", "test@gmail.com", $price * 100, "387643764");
            return response()->json([
                'message' => 'Product is not empty',
                'product' => $product,
                'price' => $price,
                'payment_url' => $url
            ]);
        }

        return response()->json([
            'message' => 'Product is empty'
        ]);
    }
}
