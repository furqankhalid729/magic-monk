<?php

namespace App\Http\Controllers\Interakt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderItem;

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
                // Extract variables
                $orderNumber = $data['id'];
                $orderTime = Carbon::parse($data['created_at_utc'])
                    ->timezone('Asia/Kolkata')
                    ->format('h:i A');

                $deliveryTime = Carbon::parse($data['created_at_utc'])
                    ->timezone('Asia/Kolkata')
                    ->addMinutes(5)
                    ->format('h:i A');

                $name = $data['customer_traits']['RealName'] ?? $data['customer_traits']['name'] ?? 'Customer';
                $address = $data['customer_traits']['FullAddress'] ?? 'N/A';
                $building = $data['customer_traits']['building'] ?? 'N/A';
                $customerPhone = "+91" . $data['customer_phone_number']['phone_number'] ?? 'N/A';
                $agentDetails = getAgentPhoneNumber($data['customer_traits']['building'] ?? '');
                $otherData = [
                    'real_name' => $data['customer_traits']['RealName'] ?? 'NA'
                ];

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
                $eventData = [
                    "orderNumber"   => $orderNumber,
                    "orderTime"     => $orderTime,
                    "deliveryTime"  => $deliveryTime,
                    "customerName"  => $name,
                    "address"       => $address,
                    "building"      => $building,
                    "customerPhone" => $customerPhone,
                    "itemList"      => $itemList,
                    "totalAmount"   => $totalAmount,
                    "paidOnline"    => $paidOnline,
                    "toCollect"     => $toCollect,
                    "agentMobile"   => $agentMobile,
                ];
                //$allStrings = array_map('strval', $eventData);
                Log::info('Body values for order update', $eventData);
                //$message = createInteraktEvent($agentMobile, $allStrings, [], "order2agent");
                $message = createInteraktEvent($agentMobile,"Send Order To Agent", $eventData);
                $title = "New Order Received". " #$orderNumber";
                $body = "$name from $building\n" .
                        "Collect: ₹$toCollect ";
                Log::info('Sending notification to agent', [
                    'token' => $token,
                    'title' => $title,
                    'body' => $body,
                    'data' => $eventData
                ]);
                $message = sendExpoPushNotification($token, $title, $body, $data);
                Log::info('Notification sent', ['message' => $message]);
                $order = Order::create([
                    'customer_name' => $name,
                    'order_id' => $orderNumber,
                    'customer_phone' => $customerPhone,
                    'building' => $address,
                    'order_time' => Carbon::now('Asia/Kolkata'),
                    'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                    'agent_number' => $agentMobile,
                    'message_id' => $message['id'] ?? null,
                    'total_amount' => $totalAmount,
                    'address' => $address,
                    'order_details' => json_encode($otherData),
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


            default:
                $message = 'Unknown webhook topic.';
        }

        return response()->json([
            'status' => 'ok',
            'message' => $message,
        ]);
    }
}
