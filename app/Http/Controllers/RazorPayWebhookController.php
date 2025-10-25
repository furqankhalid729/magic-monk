<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use App\Models\OrderItem;

class RazorPayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Handle the RazorPay webhook payload
        $payload = $request->all();
        Log::info('RazorPay Webhook received', $payload);
        if (($payload['event'] ?? null) === 'payment.captured') {
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
        $amountPaid = $paymentDetails['amount'] / 100; // Convert from paise to rupees
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
        sendInteraktMessage(
            $cacheData['customer_phone'],
            [
                $agent['name'] ?? null,
                $cacheData['agent_number'] ?? null,
                $cacheData['order_id']
            ],
            ['https://fm.monkmagic.in/storage/videos/about-fruit.mp4'],
            'orderconfirmationvideo',
            null
        );

        // Create Order
        $order = Order::create([
            'customer_name'  => $cacheData['customer_name'] ?? null,
            'order_id'       => $cacheData['order_id'] ?? null,
            'customer_phone' => $cacheData['customer_phone'] ?? null,
            'building'       => $cacheData['building'] ?? null,
            'order_time'     => $cacheData['order_time'] ?? now('Asia/Kolkata'),
            'delivery_time'  => $cacheData['delivery_time'] ?? now('Asia/Kolkata')->addMinutes(5),
            'agent_number'   => $cacheData['agent_number'] ?? null,
            'total_amount'   => 0,//$cacheData['total_amount'] ?? null,
            'address'        => $cacheData['address'] ?? null,
            'expo_token'     => data_get($cacheData, 'expo.token'),
            'payment_status' => 'paid',
            'additional_info' => [
                'paid_online' => $cacheData['additional_info']['paid_online'] - $amountPaid ?? 0,
                'to_collect'  => $cacheData['additional_info']['to_collect'] - $amountPaid ?? 0,
                'payment_status' => 'paid',
                'first_time_discount' => $cacheData['additional_info']['first_time_discount'] ?? null
            ]
        ]);

        foreach ($cacheData['order_items'] ?? [] as $item) {
            OrderItem::create([
                'order_id'  => $order->id,
                'item_name' => $item['item_name'],
                'price'     => $item['price'],
                'quantity'  => $item['quantity'],
                'amount'    => $item['amount'],
            ]);
        }
    }
}
