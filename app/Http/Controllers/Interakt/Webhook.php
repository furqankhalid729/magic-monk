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

                    case 'InteractiveButtonReply':
                        $text = $request->input('data.message.message');
                        $decoded = json_decode($text, true);
                        $title = $decoded['button_reply']['title'] ?? null;
                        if ($title == "OK Please Deliver It") {
                            $data = $request->input('data');
                            Log::info('Delivery fee confirmation received', $data);
                            $commonData = [
                                'orderNumber'   => str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
                                'name'          => $data['customer']['traits']['RealName'] ?? $data['customer']['traits']['name'] ?? 'Customer',
                                'address'       => $data['customer']['traits']['FullAddress'] ?? 'N/A',
                                'building'      => $data['customer']['traits']['building'] ?? 'N/A',
                                'customerPhone' => "+91" . ($data['customer']['phone_number'] ?? 'N/A'),
                                'product'       => $data['customer']['traits']['FreeIcecream'],
                                'headerImage'   => asset('storage/payment.jpeg'),
                            ];
                            $location = Location::where('building_name', $commonData['building'])->first();

                            $agentDetails = getAgentPhoneNumber($commonData['building'] ?? '');
                            $token = $agentDetails['token'] ?? null;
                            $agentMobile = isset($agentDetails['whatsapp_number']) ? '+91' . $agentDetails['whatsapp_number'] : null;

                            $order = Order::create([
                                'customer_name' => $commonData['name'],
                                'order_id'      => $commonData['orderNumber'],
                                'customer_phone' => $commonData['customerPhone'],
                                'building'      => $commonData['building'],
                                'order_time'    => Carbon::now('Asia/Kolkata'),
                                'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                                'agent_number'  => $agentMobile,
                                'message_id'    => $message['id'] ?? null,
                                'total_amount'  => 9,
                                'address'       => $commonData['address'],
                                'additional_info' => [
                                    'paid_online' => 0,
                                    'to_collect'  => 9,
                                    'payment_status' => "pending",
                                    'first_time_discount' => false
                                ]
                            ]);

                            OrderItem::create([
                                'order_id'  => $order->id,
                                'item_name' => $commonData['product'] ?? 'N/A',
                                'price'     => 0,
                                'quantity'  => 1,
                                'amount'    => 0,
                            ]);
                            $title = "New Order Received #{$commonData['orderNumber']}";
                            $body  = "{$commonData['name']} from {$commonData['building']}\nCollect: ₹9";
                            $message = sendExpoPushNotification($token, $title, $body, $data);
                        }

                    case 'Text':
                        $text = $request->input('data.message.message');
                        if ($text === "I'll Pay UPI on Delivery") {
                            $data = $request->input('data');
                            Log::info('UPI on Delivery confirmation received', $data);
                            $messageContextId = data_get($request->input('data'), 'message.message_context.id');
                            if ($messageContextId) {
                                Log::info('Message context ID found: ' . $messageContextId);
                                $cacheData = Cache::get($messageContextId);
                                $residentialFlow = false;
                                $key = "sample-{$data['customer']['phone_number']}";
                                if (Cache::has($key)) {
                                    $residentialFlow = true;
                                }

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
                                    addReferrerCoupon($cacheData['customer_phone'], $cacheData['customer_name']);
                                    Cache::forget($messageContextId);
                                }
                            } else {
                                Log::info('Message context ID not found');
                            }
                        } else if ($text == "Free Sample") {
                            Cache::put("sample-{$customer['phone_number']}", true, now()->addMinutes(30));
                        } else if ($text == "OK Please Deliver It") {
                            $data = $request->input('data');
                            Log::info('Delivery fee confirmation received', $data);
                            $commonData = [
                                'orderNumber'   => str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
                                'name'          => $data['customer']['traits']['RealName'] ?? $data['customer']['traits']['name'] ?? 'Customer',
                                'address'       => $data['customer']['traits']['FullAddress'] ?? 'N/A',
                                'building'      => $data['customer']['traits']['building'] ?? 'N/A',
                                'customerPhone' => "+91" . ($data['customer']['phone_number'] ?? 'N/A'),
                                'product'       => $data['customer']['traits']['FreeIcecream'],
                                'headerImage'   => asset('storage/payment.jpeg'),
                            ];
                            $location = Location::where('building_name', $commonData['building'])->first();

                            $agentDetails = getAgentPhoneNumber($commonData['building'] ?? '');
                            $token = $agentDetails['token'] ?? null;
                            $agentMobile = isset($agentDetails['whatsapp_number']) ? '+91' . $agentDetails['whatsapp_number'] : null;

                            $order = Order::create([
                                'customer_name' => $commonData['name'],
                                'order_id'      => $commonData['orderNumber'],
                                'customer_phone' => $commonData['customerPhone'],
                                'building'      => $commonData['building'],
                                'order_time'    => Carbon::now('Asia/Kolkata'),
                                'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                                'agent_number'  => $agentMobile,
                                'message_id'    => $message['id'] ?? null,
                                'total_amount'  => 9,
                                'address'       => $commonData['address'],
                                'additional_info' => [
                                    'paid_online' => 0,
                                    'to_collect'  => 9,
                                    'payment_status' => "pending",
                                    'first_time_discount' => false
                                ]
                            ]);

                            OrderItem::create([
                                'order_id'  => $order->id,
                                'item_name' => $commonData['product'] ?? 'N/A',
                                'price'     => 0,
                                'quantity'  => 1,
                                'amount'    => 0,
                            ]);
                            $title = "New Order Received #{$commonData['orderNumber']}";
                            $body  = "{$commonData['name']} from {$commonData['building']}\nCollect: ₹9";
                            $message = sendExpoPushNotification($token, $title, $body, $data);
                        } else if ($text == "OK - I'll Pick it up Myself") {
                        } else if ($text == "I'll Pick Up Myself") {
                        } else if ($text == "₹15 Delivery Fee OK") {
                            $data = $request->input('data');
                            Log::info('Delivery fee confirmation received', $data);
                            $commonData = [
                                'orderNumber'   => str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
                                'name'          => $data['customer']['traits']['RealName'] ?? $data['customer']['traits']['name'] ?? 'Customer',
                                'address'       => $data['customer']['traits']['ResiAddress'] ?? 'N/A',
                                'building'      => $data['customer']['traits']['ResiBuilding'] ?? 'N/A',
                                'customerPhone' => "+91" . ($data['customer']['phone_number'] ?? 'N/A'),
                                'product'       => $data['customer']['traits']['FreeIcecream'],
                                'headerImage'   => asset('storage/payment.jpeg'),
                            ];
                            $location = Location::where('building_name', $commonData['building'])->first();

                            $agentDetails = getAgentPhoneNumber($commonData['building'] ?? '');
                            $token = $agentDetails['token'] ?? null;
                            $agentMobile = isset($agentDetails['whatsapp_number']) ? '+91' . $agentDetails['whatsapp_number'] : null;

                            $order = Order::create([
                                'customer_name' => $commonData['name'],
                                'order_id'      => $commonData['orderNumber'],
                                'customer_phone' => $commonData['customerPhone'],
                                'building'      => $commonData['building'],
                                'order_time'    => Carbon::now('Asia/Kolkata'),
                                'delivery_time' => Carbon::now('Asia/Kolkata')->addMinutes(5),
                                'agent_number'  => $agentMobile,
                                'message_id'    => $message['id'] ?? null,
                                'total_amount'  => 15,
                                'address'       => $commonData['address'],
                                'additional_info' => [
                                    'paid_online' => 0,
                                    'to_collect'  => 15,
                                    'payment_status' => "pending",
                                    'first_time_discount' => false
                                ]
                            ]);

                            OrderItem::create([
                                'order_id'  => $order->id,
                                'item_name' => $commonData['product'] ?? 'N/A',
                                'price'     => 0,
                                'quantity'  => 1,
                                'amount'    => 0,
                            ]);
                            $title = "New Order Received #{$commonData['orderNumber']}";
                            $body  = "{$commonData['name']} from {$commonData['building']}\nCollect: ₹15";
                            $message = sendExpoPushNotification($token, $title, $body, $data);
                        } else if ($text == "Let Me Add Something") {
                        } else if ($text == "5") {
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "5");
                            // sendInteraktMessage(
                            //     $request->input('data.message.message_context.from'),
                            //     //'+923474593912',
                            //     [],
                            //     [],
                            //     'referralrequest',
                            //     null
                            // );
                        } else if ($text == "4") {
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "4");
                            // sendInteraktMessage(
                            //     $request->input('data.message.message_context.from'),
                            //     //'+923474593912',
                            //     [],
                            //     [],
                            //     'referralrequest',
                            //     null
                            // );
                        } else if ($text == "3") {
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "3");
                        } else if ($text == "2") {
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "2");
                        } else if ($text == "1") {
                            $review_message_id = $request->input('data.message.message_context.id');
                            updateReview($review_message_id, "1");
                        } else if ($text == "Send Me My Referral Link") {
                            sendInteraktMessage(
                                $request->input('data.customer.channel_phone_number'),
                                [$request->input('data.customer.phone_number')],
                                ['https://fm.monkmagic.in/storage/videos/about-fruit.mp4'],
                                'referraloffer',
                                null
                            );
                        } else if (Str::contains($text, 'I Would Like to Try the Magic Now (Ref:')) {
                            preg_match('/\(Ref:\s*(\d+)\)/', $text, $matches);
                            $referrerCode = $matches[1] ?? null;
                            $refereePhone = $request->input('data.customer.phone_number');

                            if ($referrerCode && $refereePhone) {
                                $this->referralService->createReferral([
                                    'referrerPhone' => "+91" . $referrerCode,
                                    'refereePhone'  => "+91" . $refereePhone,
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

                $key = "sample-{$data['customer_phone_number']['phone_number']}";
                if (Cache::has($key)) {
                    $this->residentialCartFlow($data);
                    $message = 'Residential cart flow processed.';
                    break;
                }

                $commonData = [
                    'orderNumber'   => $data['id'],
                    'name'          => $data['customer_traits']['RealName'] ?? $data['customer_traits']['name'] ?? 'Customer',
                    'address'       => $data['customer_traits']['FullAddress'] ?? 'N/A',
                    'building'      => $data['customer_traits']['building'] ?? 'N/A',
                    'customerPhone' => "+91" . ($data['customer_phone_number']['phone_number'] ?? 'N/A'),
                    'headerImage'   => asset('storage/payment.jpeg'),
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

                $liveOffer = $location?->liveAdditionalOffers()->first();
                Log::info('Live offer for location', ['location' => $location->building_name ?? null, 'liveOffer' => $liveOffer]);
                if ($liveOffer) {
                    if ($liveOffer->discount_type === 'percentage') {
                        $discountAmount = ($data['total_amount'] * $liveOffer->discount_value) / 100;
                    } elseif ($liveOffer->discount_type === 'fixed') {
                        $discountAmount = (float) $liveOffer->discount_value;
                    }
                } else {
                    $discountAmount = $firstTimeDiscount ? 79 : getDiscountAmount($commonData['customerPhone']);
                }
                $discountAmount = floor($discountAmount);
                $totalAmount = max(0, $data['total_amount'] - $discountAmount);
                //$totalAmount = ceil($totalAmount);
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

                if ($liveOffer && $firstTimeDiscount) {
                    addCustomerCoupon($commonData['customerPhone'], $discountAmount);
                }
                break;
        }

        return response()->json([
            'status'  => 'ok',
            'message' => $message
        ]);
    }

    public function residentialCartFlow($data)
    {
        $payment_status = $data['payment_status'] ?? 'PENDING';
        $commonData = [
            'orderNumber'   => $data['id'],
            'name'          => $data['customer_traits']['RealName'] ?? $data['customer_traits']['name'] ?? 'Customer',
            'address'       => $data['customer_traits']['FullAddress'] ?? 'N/A',
            'building'      => $data['customer_traits']['ResiBuilding'] ?? 'N/A',
            'customerPhone' => "+91" . ($data['customer_phone_number']['phone_number'] ?? 'N/A'),
            'headerImage'   => asset('storage/payment.jpeg'),
            'product'       => $data['customer_traits']['FreeIcecream'] ?? 'N/A',
        ];
        $location = Location::where('building_name', $commonData['building'])->first();
        $agentDetails = getAgentPhoneNumber($commonData['building'] ?? '');
        $token = $agentDetails['token'] ?? null;
        $agentMobile = isset($agentDetails['whatsapp_number']) ? '+91' . $agentDetails['whatsapp_number'] : null;
        $itemList = collect($data['order_items'] ?? [])
            ->map(fn($item) => "{$item['item_name']} x{$item['quantity']}")
            ->push(($data['customer_traits']['FreeIcecream'] ?? 'N/A') . ' x 1')
            ->implode(' | ');
        $discountAmount = 0;
        $totalAmount = max(0, $data['total_amount'] - $discountAmount);
        $paidOnline = $payment_status === 'PAID' ? $totalAmount : 0;
        $toCollect  = $totalAmount - $paidOnline;
        Log::info('Residential Cart Order details', [
            'totalAmount' => $totalAmount,
            'paidOnline' => $paidOnline,
            'toCollect' => $toCollect,
            'payment_status' => $payment_status
        ]);

        if (!empty($data['customer_traits']['FreeIcecream'])) {
            $data['order_items'][] = [
                'item_name' => $data['customer_traits']['FreeIcecream'],
                'quantity'  => 1,
                'amount'    => 0,
                'price'     => 0
            ];
        }

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
            $response = sendWhatsAppPay($commonData['customerPhone'], $new_payload, [$commonData['headerImage']], "paymentfm_with_pod2", null, $simplifiedItems, $totalAmount, $commonData['orderNumber'], $pay_address, $discountAmount, false);
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
                        'first_time_discount' => false
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
                    'first_time_discount' => false
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
        }
    }
}
