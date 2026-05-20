<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Order;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\CustomerSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AndroidAgentController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|string',
            'password' => 'required|string',
        ]);

        $phone = preg_replace('/^\+91/', '', $request->phoneNumber);
        $password = preg_replace('/^\+91/', '', $request->password);

        $user = Agent::where('whatsapp_number', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($phone === $password) {
            return response()->json(['message' => 'Login successful.', 'status' => true, 'user' => $user], 200);
        }

        return response()->json(['message' => 'Invalid credentials.', 'status' => false], 200);
    }

    public function order(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|integer',
        ]);

        $agentId = $request->query('phoneNumber');
        if (!str_starts_with($agentId, '+91')) {
            $agentId = '+91' . ltrim($agentId, '+');
        }

        $orders = Order::where('agent_number', $agentId)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get();


        if (!$orders) {
            return response()->json(['message' => 'Orders not found.', 'status' => false], 200);
        }

        return response()->json(['message' => 'Order retrieved successfully.', 'status' => true, 'orders' => $orders], 200);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|string',
            'token' => 'required|string',
        ]);

        $phone = preg_replace('/^\+91/', '', $request->phoneNumber);
        $token = $request->token;

        $user = Agent::where('whatsapp_number', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->notification_token = $token;
        $user->save();

        return response()->json(['message' => 'Token stored successfully.'], 200);
    }

    public function updateOrderStatus(Request $request)
    {
        $request->validate([
            'orderId' => 'required|integer',
            'status' => 'required|string',
        ]);

        $order = Order::find($request->orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->status = $request->status;
        $order->delivered_on = $request->status === 'delivered'
            ? Carbon::now('Asia/Kolkata')
            : null;
        $order->save();

        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'additional_info' => array_merge($order->additional_info ?? [], [
                    'payment_status' => 'paid',
                ]),
            ]);

            $order->refresh();
            $this->syncOrderInvoiceAndSendFeedback($order);
        }

        $subscriptionCheck = CustomerSubscription::where('customer_phone', $order->customer_phone)
            ->exists();

        if ($subscriptionCheck) {
            $customerSubscription = CustomerSubscription::where('customer_phone', $order->customer_phone)
                ->first();
            $customerSubscription->order_count -= 1;
            $customerSubscription->save();
            $response = sendInteraktMessage($order->customer_phone, [
                (string) $order->order_id,
                $customerSubscription->order_count
            ], [asset('storage/feedback.jpeg')], 'subs_backup_paymentfm', "");
        } else {
            // $response = sendInteraktMessage($order->customer_phone, [
            //     (string) $order->order_id
            // ], [
            //     'https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_sample/CA1CykiSSOY4/Why%20Monk%20Fruit%20is%20the%20Best.jpeg?se=2031-04-26T09%3A01%3A40Z&sp=rt&sv=2019-12-12&sr=b&sig=iOeVD1TTeyj6mPeLd02LyP1xCPD3m8ceYmE%2B4ZolgFk%3D'
            // ], 'feedback_with_nps', "");
        }
        $order->review_message_id = $response['id'] ?? null;
        $order->save();

        $orderCount = Order::where('customer_phone', $order->customer_phone)
            ->where('status', 'delivered')
            ->count();

        $additionalInfo = $order->additional_info ?? [];
        if ($orderCount === 1 && $additionalInfo['first_time_discount'] === true) {
            addCustomerCoupon($order->customer_phone, '50-off');
        }



        return response()->json(['message' => 'Order status updated successfully.', 'status' => true], 200);
    }

    private function syncOrderInvoiceAndSendFeedback(Order $order): void
    {
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

            if (! empty($order->customer_phone) && ! empty($invoiceAttachmentUrl)) {
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

            Log::info('Order synced with Odoo from Android agent status update', [
                'order_id' => $order->id,
                'invoice_id' => $odooSync['invoice_id'] ?? null,
                'invoice_url' => $odooSync['invoice_url'] ?? null,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to sync order with Odoo from Android agent status update', [
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
    }
}
