<?php

namespace App\Http\Controllers\Interakt;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;

class Webhook extends Controller
{
    public function handle(Request $request)
    {
        $topic = $request->input('type');
        switch ($topic) {
            case 'message_received':
                $messageType = $request->input('data.message.message_content_type');
                $customer = $request->input('data.customer');
                $traits = $customer['traits'] ?? [];

                switch ($messageType) {
                    case 'Location':
                        $locationData = json_decode($request->input('data.message.message'), true);

                        if (isset($locationData['latitude'], $locationData['longitude'])) {
                            $lat = $locationData['latitude'];
                            $lng = $locationData['longitude'];
                            $message = "Location received: Latitude = $lat, Longitude = $lng";
                        } else {
                            $message = 'Invalid location data received.';
                        }
                        break;

                    case 'Text':
                        $text = $request->input('data.message.message');
                        if ($text == "I'll Pay UPI on Delivery") {
                            $messageContextId = data_get($request->input('data'), 'message.message_context.id');
                            if ($messageContextId) {
                                Log::info('Message context ID found: ' . $messageContextId);
                                $cacheData = Cache::get($messageContextId);
                                if ($cacheData) {
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
                                        [],
                                        'orderconfirmation',
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
                                        'total_amount'   => $cacheData['total_amount'] ?? null,
                                        'address'        => $cacheData['address'] ?? null,
                                        // Expo token if you cached it
                                        'expo_token'     => data_get($cacheData, 'expo.token'),
                                    ]);

                                    // Create Order Items
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
                            } else {
                                Log::info('Message context ID not found');
                            }
                        }
                        $message = "Text message received: \"$text\"";
                        break;

                    case 'Order':
                        $orderData = json_decode($request->input('data.message.message'), true);
                        $latLng = json_decode($traits['location'] ?? '{}', true);
                        $building = $traits['building'] ?? 'N/A';
                        $fullAddress = $traits['FullAddress'] ?? 'N/A';
                        $latitude = $latLng['latitude'] ?? 'N/A';
                        $longitude = $latLng['longitude'] ?? 'N/A';

                        $orderItems = $orderData['product_items'] ?? [];

                        $itemsText = collect($orderItems)->map(function ($item) {
                            return "- Product ID: {$item['product_retailer_id']}, Quantity: {$item['quantity']}, Price: {$item['item_price']} {$item['currency']}";
                        })->implode("\n");

                        $message = <<<MSG
                            🛒 *New Order Received*

                            📍 *Location:*
                            Lat: {$latitude}, Lng: {$longitude}

                            🏢 *Building:* {$building}
                            🏠 *Full Address:* {$fullAddress}

                            📦 *Order Details:*
                            {$itemsText}
                        MSG;


                    default:
                        $message = "Unhandled message type: $messageType";
                }

                break;

            case "cart_order_update":
                $data = $request->input('data');
                Log::info('Cart order update received', $data);
                $payment_status = $data['payment_status'] ?? 'PENDING';
                switch ($payment_status) {
                    case 'PENDING':
                        $orderNumber = $data['id'];
                        $name = $data['customer_traits']['RealName'] ?? $data['customer_traits']['name'] ?? 'Customer';
                        $address = $data['customer_traits']['FullAddress'] ?? 'N/A';
                        $building = $data['customer_traits']['building'] ?? 'N/A';
                        $customerPhone = "+91" . $data['customer_phone_number']['phone_number'] ?? 'N/A';
                        $agentDetails = getAgentPhoneNumber($data['customer_traits']['building'] ?? '');
                        $otherData = [
                            'real_name' => $data['customer_traits']['RealName'] ?? 'NA'
                        ];
                        $headerImage = "https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/fZSiDosqseLO/WhatsApp%20Image%202025-07-15%20at%2017.39.09.jpeg?se=2030-07-12T14%3A28%3A34Z&sp=rt&sv=2019-12-12&sr=b&sig=dQShOEauRkfq6xrdOzrP%2B4ZmWcwPDcwYEng43lpyQHw%3D";
                        $token = null;
                        $agentMobile = null;

                        if ($agentDetails) {
                            ['whatsapp_number' => $number, 'token' => $agentToken] = $agentDetails;
                            $agentMobile = '+91' . $number;
                            $token = $agentToken;
                        }
                        $itemList = '';
                        foreach ($data['order_items'] as $item) {
                            $itemList .= $item['item_name'] . ' x' . $item['quantity'] . " | ";
                        }
                        $itemList = trim($itemList);
                        $totalAmount = $data['total_amount'];
                        $paidOnline = ($data['payment_status'] === 'PAID') ? $totalAmount : 0;
                        $toCollect = $totalAmount - $paidOnline;
                        $simplifiedItems = array_map(function ($item) {
                            return [
                                "name" => $item["item_name"],
                                "quantity" => $item["quantity"],
                                "amount" => $item["amount"],
                                "country_of_origin" => "India"
                            ];
                        }, $data['order_items']);

                        $pay_address = [
                            "name" => $name,
                            "phone_number" => ltrim($customerPhone, '+'),
                            "address" => $address,
                            "city" => "Mumbai",
                            "state" => "Maharastra",
                            "in_pin_code" => "400093",
                            "building_name" => $building,
                            "landmark_area" => "Chakala",
                            "country" => "IN"
                        ];
                        $new_payload = [
                            $itemList,
                            count($data['order_items']),
                            $totalAmount,
                            "0",
                            $totalAmount
                        ];
                        $response = sendWhatsAppPay($customerPhone, $new_payload, [$headerImage], "paymentfm_with_pod2", null, $simplifiedItems, $totalAmount, $orderNumber, $pay_address);
                        Log::info('WhatsApp Pay response', ['response' => $response]);
                        $cacheKey = $response['id'] ?? null;
                        if ($cacheKey) {
                            $orderData = [
                                'customer_name' => $name,
                                'order_id' => $orderNumber,
                                'customer_phone' => $customerPhone,
                                'building' => $building,
                                'order_time' => Carbon::now('Asia/Kolkata'),
                                'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                                'agent_number' => $agentMobile,
                                'total_amount' => $totalAmount,
                                'address' => $address,
                                'order_items' => $data['order_items'] ?? [],

                                // Expo notification data
                                'expo' => [
                                    'token' => $token,
                                    'title' => "New Order Received #$orderNumber",
                                    'body'  => "$name from $building\nCollect: ₹$toCollect",
                                    'data'  => $data
                                ],
                            ];
                            Cache::put($cacheKey, $orderData, now()->addHours(6));
                            $message = 'Order data cached successfully.';
                        } else {
                            Log::error('Cache key not found in WhatsApp Pay response', ['response' => $response]);
                            $message = 'Failed to cache order data. Cache key not found.';
                        }
                        break;
                    case 'PAID':
                        Log::info('Order payment status is PAID', ['data' => $data]);
                        $orderNumber = $data['id'];
                        $name = $data['customer_traits']['RealName'] ?? $data['customer_traits']['name'] ?? 'Customer';
                        $address = $data['customer_traits']['FullAddress'] ?? 'N/A';
                        $building = $data['customer_traits']['building'] ?? 'N/A';
                        $customerPhone = "+91" . $data['customer_phone_number']['phone_number'] ?? 'N/A';
                        $agentDetails = getAgentPhoneNumber($data['customer_traits']['building'] ?? '');
                        $otherData = [
                            'real_name' => $data['customer_traits']['RealName'] ?? 'NA'
                        ];
                        $headerImage = "https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/fZSiDosqseLO/WhatsApp%20Image%202025-07-15%20at%2017.39.09.jpeg?se=2030-07-12T14%3A28%3A34Z&sp=rt&sv=2019-12-12&sr=b&sig=dQShOEauRkfq6xrdOzrP%2B4ZmWcwPDcwYEng43lpyQHw%3D";
                        $token = null;
                        $agentMobile = null;

                        if ($agentDetails) {
                            ['whatsapp_number' => $number, 'token' => $agentToken] = $agentDetails;
                            $agentMobile = '+91' . $number;
                            $token = $agentToken;
                        }
                        $itemList = '';
                        foreach ($data['order_items'] as $item) {
                            $itemList .= $item['item_name'] . ' x' . $item['quantity'] . " | ";
                        }
                        $itemList = trim($itemList);
                        $totalAmount = $data['total_amount'];
                        $paidOnline = ($data['payment_status'] === 'PAID') ? $totalAmount : 0;
                        $toCollect = $totalAmount - $paidOnline;
                        $simplifiedItems = array_map(function ($item) {
                            return [
                                "name" => $item["item_name"],
                                "quantity" => $item["quantity"],
                                "amount" => $item["amount"],
                                "country_of_origin" => "India"
                            ];
                        }, $data['order_items']);

                        $pay_address = [
                            "name" => $name,
                            "phone_number" => ltrim($customerPhone, '+'),
                            "address" => $address,
                            "city" => "Mumbai",
                            "state" => "Maharastra",
                            "in_pin_code" => "400093",
                            "building_name" => $building,
                            "landmark_area" => "Chakala",
                            "country" => "IN"
                        ];
                        $new_payload = [
                            $itemList,
                            count($data['order_items']),
                            $totalAmount,
                            "0",
                            $totalAmount
                        ];
                        $title = "New Order Received #$orderNumber";
                        $body  = "$name from $building\nCollect: ₹$toCollect";
                        $message = sendExpoPushNotification($token, $title, $body, $data);
                        Log::info('Notification sent', ['message' => $message]);
                        sendInteraktMessage(
                            $customerPhone,
                            [
                                $agentDetails['name'] ?? null,
                                $agentMobile,
                                $orderNumber
                            ],
                            [],
                            'orderconfirmation',
                            null
                        );
                        $order = Order::create([
                            'customer_name' => $name,
                            'order_id' => $orderNumber,
                            'customer_phone' => $customerPhone,
                            'building' => $building,
                            'order_time' => Carbon::now('Asia/Kolkata'),
                            'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                            'agent_number' => $agentMobile,
                            'message_id' => $message['id'] ?? null,
                            'total_amount' => $totalAmount,
                            'address' => $address,
                        ]);
                        foreach ($data['order_items'] as $item) {
                            OrderItem::create([
                                'order_id' => $order->id,
                                'item_name' => $item['item_name'],
                                'price' => $item['price'],
                                'quantity' => $item['quantity'],
                                'amount' => $item['amount'],
                            ]);
                        }
                        break;
                    case 'FAILED':
                        // Handle failed status
                        break;
                    default:
                        // Handle unknown status
                        break;
                }

                break;


            default:
                $message = 'Unknown webhook topic.';
        }

        return response()->json([
            'status' => 'ok',
            'message' => $message
        ]);
    }
}
