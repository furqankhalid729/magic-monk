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
use App\Models\Location;
use Illuminate\Support\Str;
use App\Services\ReferralService;

class Webhook extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }
    public function handle(Request $request)
    {
        $topic = $request->input('type');
        $message = 'Unknown webhook topic.';

        switch ($topic) {
            case 'message_received':
                $messageType = $request->input('data.message.message_content_type');
                $customer = $request->input('data.customer');
                $traits = $customer['traits'] ?? [];

                switch ($messageType) {
                    case 'Location':
                        $locationData = json_decode($request->input('data.message.message'), true);
                        if (!empty($locationData['latitude']) && !empty($locationData['longitude'])) {
                            $lat = $locationData['latitude'];
                            $lng = $locationData['longitude'];
                            $message = "Location received: Latitude = $lat, Longitude = $lng";
                        } else {
                            $message = 'Invalid location data received.';
                        }
                        break;

                    case 'Text':
                        $text = $request->input('data.message.message');
                        if ($text === "I'll Pay UPI on Delivery") {
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
                                        'total_amount'   => $cacheData['total_amount'] ?? null,
                                        'address'        => $cacheData['address'] ?? null,
                                        'expo_token'     => data_get($cacheData, 'expo.token'),
                                        'additional_info' => [
                                            'paid_online' => $cacheData['additional_info']['paid_online'] ?? 0,
                                            'to_collect'  => $cacheData['additional_info']['to_collect'] ?? 0,
                                            'payment_status' => $cacheData['additional_info']['payment_status'] ?? null,
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
                                    addReferrerCoupon($cacheData['customer_phone'],$cacheData['customer_name']);
                                    Cache::forget($messageContextId);
                                }
                            } else {
                                Log::info('Message context ID not found');
                            }
                        }
                        else if($text == "Nice! Tasted like Normal"){
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "nice");
                            sendInteraktMessage(
                                //$request->input('data.message.message_context.from'),
                                '+923474593912',
                                [],
                                [asset('storage/feedback.jpeg')],
                                'referralrequest',
                                null
                            );
                        }
                        else if($text == "Found the Taste Average"){
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "average");
                        }
                        else if($text == "Sorry, I didn't like it!"){
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "bad");
                        }
                        else if($text == "Send Me My Referral Link"){
                            sendInteraktMessage(
                                $request->input('data.customer.channel_phone_number'),
                                [$request->input('data.customer.phone_number')],
                                ['https://fm.monkmagic.in/storage/videos/about-fruit.mp4'],
                                'referraloffer',
                                null
                            );
                        }
                        else if (Str::contains($text, 'I Would Like to Try the Magic Now (Ref:')){
                            preg_match('/\(Ref:\s*(\d+)\)/', $text, $matches);
                            $referrerCode = $matches[1] ?? null;
                            $refereePhone = $request->input('data.customer.phone_number');

                            if ($referrerCode && $refereePhone) {
                                $this->referralService->createReferral([
                                    'referrerPhone' => "+91".$referrerCode,
                                    'refereePhone'  => "+91".$refereePhone,
                                ]);
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

                        $itemsText = collect($orderData['product_items'] ?? [])->map(
                            fn($item) =>
                            "- Product ID: {$item['product_retailer_id']}, Quantity: {$item['quantity']}, Price: {$item['item_price']} {$item['currency']}"
                        )->implode("\n");

                        $message = <<<MSG
                            🛒 *New Order Received*

                            📍 *Location:*
                            Lat: {$latitude}, Lng: {$longitude}

                            🏢 *Building:* {$building}
                            🏠 *Full Address:* {$fullAddress}

                            📦 *Order Details:*
                            {$itemsText}
                        MSG;
                        break;

                    default:
                        $message = "Unhandled message type: $messageType";
                }
                break;

            case "cart_order_update":
                $data = $request->input('data');
                $payment_status = $data['payment_status'] ?? 'PENDING';
                Log::info('Cart order update received', $data);
                $commonData = [
                    'orderNumber'   => $data['id'],
                    'name'          => $data['customer_traits']['RealName'] ?? $data['customer_traits']['name'] ?? 'Customer',
                    'address'       => $data['customer_traits']['FullAddress'] ?? 'N/A',
                    'building'      => $data['customer_traits']['building'] ?? 'N/A',
                    'customerPhone' => "+91" . ($data['customer_phone_number']['phone_number'] ?? 'N/A'),
                    'headerImage'   => "https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/fZSiDosqseLO/WhatsApp%20Image%202025-07-15%20at%2017.39.09.jpeg?se=2030-07-12T14%3A28%3A34Z&sp=rt&sv=2019-12-12&sr=b&sig=dQShOEauRkfq6xrdOzrP%2B4ZmWcwPDcwYEng43lpyQHw%3D"
                ];

                $firstTimeDiscount = false;
                $location = Location::where('building_name', $commonData['building'])->first();
                $checkCustomer = Order::where('customer_phone', $commonData['customerPhone'])
                    ->exists();
                if ($location && $location->is_offer_live && !$checkCustomer) {
                    $firstTimeDiscount = true;
                }
                Log::info('First time discount check', [
                    'is_offer_live' => $location->is_offer_live ?? false,
                    'checkCustomer' => $checkCustomer,
                    'firstTimeDiscount' => $firstTimeDiscount,
                    'location' => $location
                ]);
                $agentDetails = getAgentPhoneNumber($commonData['building'] ?? '');
                $token = $agentDetails['token'] ?? null;
                $agentMobile = isset($agentDetails['whatsapp_number']) ? '+91' . $agentDetails['whatsapp_number'] : null;

                $itemList = collect($data['order_items'] ?? [])->map(fn($item) => "{$item['item_name']} x{$item['quantity']}")->implode(' | ');
                $discountAmount = $firstTimeDiscount ? 79 : getDiscountAmount($commonData['customerPhone']);
                $totalAmount = max(0, $data['total_amount'] - $discountAmount);
                $paidOnline = $payment_status === 'PAID' ? $totalAmount : 0;
                $toCollect  = $totalAmount - $paidOnline;

                Log::info('Order details', [
                    'totalAmount' => $totalAmount,
                    'paidOnline' => $paidOnline,
                    'toCollect' => $toCollect,
                    'payment_status' => $payment_status
                ]);

                if ($totalAmount <= 0) {
                    $payment_status = 'PAID';
                }
                //$payment_status = 'PAID';
                $simplifiedItems = array_map(fn($item) => [
                    "name"             => $item["item_name"],
                    "quantity"         => $item["quantity"],
                    "amount"           => $item["amount"],
                    "country_of_origin" => "India"
                ], $data['order_items'] ?? []);

                $pay_address = [
                    "name"          => $commonData['name'],
                    "phone_number"  => ltrim($commonData['customerPhone'], '+'),
                    "address"       => $commonData['address'],
                    "city"          => "Mumbai",
                    "state"         => "Maharastra",
                    "in_pin_code"   => "400093",
                    "building_name" => $commonData['building'],
                    "landmark_area" => "Chakala",
                    "country"       => "IN"
                ];

                $new_payload = [$itemList, count($data['order_items'] ?? []), $totalAmount, (string) $discountAmount, $totalAmount];

                if ($payment_status === 'PENDING') {
                    $response = sendWhatsAppPay($commonData['customerPhone'], $new_payload, [$commonData['headerImage']], "paymentfm_with_pod2", null, $simplifiedItems, $totalAmount, $commonData['orderNumber'], $pay_address, $discountAmount, $firstTimeDiscount);
                    Log::info('WhatsApp Pay response', ['response' => $response]);

                    $cacheKey = $response['id'] ?? null;
                    if ($cacheKey) {
                        $orderData = [
                            'customer_name' => $commonData['name'],
                            'order_id'      => $commonData['orderNumber'],
                            'customer_phone' => $commonData['customerPhone'],
                            'building'      => $commonData['building'],
                            'order_time'    => Carbon::now('Asia/Kolkata'),
                            'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                            'agent_number'  => $agentMobile,
                            'total_amount'  => $totalAmount,
                            'address'       => $commonData['address'],
                            'order_items'   => $data['order_items'] ?? [],
                            'discount'      => $discountAmount,
                            'additional_info' => [
                                'paid_online' => $paidOnline,
                                'to_collect'  => $toCollect,
                                'payment_status' => $payment_status,
                                'first_time_discount' => $firstTimeDiscount
                            ],
                            'expo'          => [
                                'token' => $token,
                                'title' => "New Order Received #{$commonData['orderNumber']}",
                                'body'  => "{$commonData['name']} from {$commonData['building']}\nCollect: ₹$toCollect",
                                'data'  => $data
                            ],
                        ];
                        Cache::put($cacheKey, $orderData, now()->addHours(6));
                        $message = 'Order data cached successfully.';
                    } else {
                        Log::error('Cache key not found in WhatsApp Pay response', ['response' => $response]);
                        $message = 'Failed to cache order data. Cache key not found.';
                    }
                }

                if ($payment_status === 'PAID') {
                    Log::info('Order payment status is PAID', ['data' => $data]);
                    $title = "New Order Received #{$commonData['orderNumber']}";
                    $body  = "{$commonData['name']} from {$commonData['building']}\nCollect: ₹$toCollect";

                    $message = sendExpoPushNotification($token, $title, $body, $data);
                    Log::info('Notification sent', ['message' => $message]);

                    sendInteraktMessage(
                        $commonData['customerPhone'],
                        [
                            $agentDetails['name'] ?? null,
                            $agentMobile,
                            $commonData['orderNumber']
                        ],
                        ['https://fm.monkmagic.in/storage/videos/about-fruit.mp4'],
                        'orderconfirmationvideo',
                        null
                    );

                    $order = Order::create([
                        'customer_name' => $commonData['name'],
                        'order_id'      => $commonData['orderNumber'],
                        'customer_phone' => $commonData['customerPhone'],
                        'building'      => $commonData['building'],
                        'order_time'    => Carbon::now('Asia/Kolkata'),
                        'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                        'agent_number'  => $agentMobile,
                        'message_id'    => $message['id'] ?? null,
                        'total_amount'  => $totalAmount,
                        'address'       => $commonData['address'],
                        'additional_info' => [
                            'paid_online' => $paidOnline,
                            'to_collect'  => $toCollect,
                            'payment_status' => $payment_status,
                            'first_time_discount' => $firstTimeDiscount
                        ]
                    ]);

                    foreach ($data['order_items'] ?? [] as $item) {
                        OrderItem::create([
                            'order_id'  => $order->id,
                            'item_name' => $item['item_name'],
                            'price'     => $item['price'],
                            'quantity'  => $item['quantity'],
                            'amount'    => $item['amount'],
                        ]);
                    }
                    addReferrerCoupon($commonData['customerPhone'], $commonData['name']);
                }
                break;
        }

        return response()->json([
            'status'  => 'ok',
            'message' => $message
        ]);
    }
}
