<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Laravel\Telescope\Telescope;
use App\Models\Location;
use App\Models\CustomerReferrals;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

if (!function_exists('sendInteraktMessage')) {
    function sendInteraktMessage($phoneNumber, $bodyValues = [], $headerValues = [], $templateName = 'your_template', $campaignId = null)
    {
        $apiKey = env('INTERAKT_API_KEY');
        $campaignId = $campaignId ?? null;

        $client = new Client();

        $headers = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        $body = [
            "fullPhoneNumber" => $phoneNumber,
            "campaignId" => $campaignId,
            "type" => "Template",
            "template" => [
                "name" => $templateName,
                "languageCode" => "en",
                "headerValues" => $headerValues,
                "bodyValues" => $bodyValues
            ]
        ];


        $response = Http::withHeaders($headers)
            ->post(env('INTERAKT_MESSAGE_API_URL'), $body);

        if ($response->successful()) {
            return $response->json();
        } else {
            return ['error' => $response->body()];
        }
    }
}

if (!function_exists('sendWhatsAppPay')) {
    function sendWhatsAppPay($phoneNumber, $bodyValues = [], $headerValues = [], $templateName = 'paymentfm_with_pod2', $campaignId = null, $orderItems = [], $totalAmount = 0, $orderId = "order67557", $address, $discountAmount)
    {
        $apiKey = env('INTERAKT_API_KEY');
        $campaignId = $campaignId ?? null;

        $client = new Client();
        Log::info('Sending WhatsApp Pay message', [
            'phoneNumber' => $phoneNumber,
            'templateName' => $templateName,
            'campaignId' => $campaignId,
            'totalAmount' => $totalAmount,
            'orderItems' => $orderItems,
        ]);

        $headers = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        $body = [
            "fullPhoneNumber" => $phoneNumber, //"+919867871610",//
            "campaignId" => $campaignId,
            "type" => "Template",
            "template" => [
                "name" => $templateName,
                "languageCode" => "en",
                "headerValues" => $headerValues,
                "bodyValues" => $bodyValues,
                "order_details" => [
                    [
                        "reference_id" => strval($orderId),
                        "order_items" => $orderItems,
                        "shipping_addresses" => [
                            $address
                        ],
                        "subtotal" => $totalAmount + $discountAmount,
                        "discount" => $discountAmount,
                        "tax" => 0,
                        "shipping" => 0,
                        "total_amount" => $totalAmount,
                        "currency" => "INR",
                        "payment_option_expires_in" => [
                            "value" => 15,
                            "unit" => "minutes",
                            "expiration_message" => " Expires in 15 minutes"
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders($headers)
            ->post(env('INTERAKT_MESSAGE_API_URL'), $body);

        if ($response->successful()) {
            Log::info('WhatsApp Pay message sent successfully', [
                'response' => $response->json()
            ]);
            return $response->json();
        } else {
            return ['error' => $response->body()];
        }
    }
}
if (!function_exists('getAgentPhoneNumber')) {
    function getAgentPhoneNumber($building)
    {
        Log::info('getAgentPhoneNumber called with building', ['building' => $building]);

        $location = Location::where('building_name', $building)->get();

        if ($location->isNotEmpty() && $location->first()->agent) {
            $agent = $location->first()->agent;
            return [
                'whatsapp_number' => $agent->whatsapp_number,
                'token' => $agent->notification_token,
                'name' => $agent->name
            ];
        }

        return [
            'whatsapp_number' => '9867806668',
            'token' => 'ExponentPushToken[KWTa_jDgmuBhoOVKmDzSUS]',
            'name' => 'Monku'
        ];
    }
}
if (!function_exists('createInteraktEvent')) {
    function createInteraktEvent($agentNumber, $eventName, $eventData = [])
    {
        $apiKey = env('INTERAKT_API_KEY');

        $client = new Client();

        $headers = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        $body = [
            "fullPhoneNumber" => $agentNumber,
            "event" => $eventName,
            "traits" => $eventData,
        ];
        Log::info('Creating Interakt event', [
            'event_name' => $eventName,
            'event_data' => $eventData
        ]);
        $request = new Request('POST', env('INTERAKT_EVENT_API_URL'), $headers, json_encode($body));
        try {
            $res = $client->sendAsync($request)->wait();
            return json_decode($res->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
if (!function_exists('sendExpoPushNotification')) {
    function sendExpoPushNotification($token, $title, $body, $data = [])
    {
        $response = Http::post('https://exp.host/--/api/v2/push/send', [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            "sound" => "notification",
            'priority' => 'high',
        ]);

        return $response->json();
    }
}

if (!function_exists('updateReview')) {
    function updateReview($reviewMessageId, $reviewText)
    {
        $order = Order::where('review_message_id', $reviewMessageId)->first();
        if ($order) {
            $order->review = $reviewText;
            $order->save();
        }
    }
}

function getDiscountAmount($customerPhone)
{
    $coupon = DB::table('customer_coupons')
        ->join('coupons', 'customer_coupons.coupon_handle', '=', 'coupons.handle')
        ->where('customer_coupons.customer_phone', $customerPhone)
        ->select('customer_coupons.id', 'coupons.discount_amount')
        ->first();

    if ($coupon) {
        DB::table('customer_coupons')->where('id', $coupon->id)->delete();
        return $coupon->discount_amount;
    }

    return 0;
}

function nextWorkingDay(Carbon $date)
{
    $next = $date->copy()->addDay();

    // Skip weekends
    while (in_array($next->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
        $next->addDay();
    }

    return $next;
}

// function getDiscountAmount($customerPhone)
// {
//     $today = now()->startOfDay();

//     // Fetch all order dates for this customer (latest first)
//     $orders = DB::table('orders')
//         ->where('customer_phone', $customerPhone)
//         ->orderByDesc('order_date')
//         ->pluck('order_date')
//         ->map(fn($d) => Carbon::parse($d)->startOfDay())
//         ->toArray();

//     if (empty($orders)) {
//         return 79;
//     }

//     // Start streak calculation
//     $streak = 1;
//     $expectedDate = $today;

//     // Walk through orders until streak breaks
//     foreach ($orders as $orderDate) {
//         if ($orderDate->equalTo($expectedDate)) {
//             // matched, streak continues
//             $streak++;
//             $expectedDate = nextWorkingDay($orderDate);
//         } else {
//             // streak breaks
//             break;
//         }
//     }
//     // Apply discounts
//     if ($streak == 1) {
//         return 79; // first day
//     } elseif ($streak == 2) {
//         return 50; // 2nd consecutive day
//     } elseif ($streak == 3) {
//         return 40; // 3rd consecutive day
//     } else {
//         // streak > 3 → use coupon
//         $coupon = DB::table('customer_coupons')
//             ->join('coupons', 'customer_coupons.coupon_handle', '=', 'coupons.handle')
//             ->where('customer_coupons.customer_phone', $customerPhone)
//             ->select('customer_coupons.id', 'coupons.discount_amount')
//             ->first();

//         if ($coupon) {
//             DB::table('customer_coupons')->where('id', $coupon->id)->delete();
//             return $coupon->discount_amount;
//         }

//         return 0;
//     }
// }

if (!function_exists('addReferrerCoupon')) {
    function addReferrerCoupon($customer_number, $name)
    {
        $referral = CustomerReferrals::where('referee_number', $customer_number)
            ->first();

        if ($referral && !$referral->reward_given && !$referral->first_order_done) {

            $coupon = DB::table('customer_coupons')
                ->where('customer_phone', $customer_number)
                ->where('coupon_handle', 'referee-code')
                ->delete();

            $referral->reward_given = true;
            $referral->first_order_done = true;
            $referral->save();

            DB::table('customer_coupons')->insert([
                'coupon_handle'   => 'referrer-code',
                'customer_phone'  => $referral->referrer_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            sendInteraktMessage(
                $referral->referrer_number,
                [$name],
                [asset('storage/referralconverstion.jpeg')],
                'referralconversion'
            );
            return true;
        }

        return false;
    }
}

if(!function_exists('addCustomerCoupon')) {
    function addCustomerCoupon($customerPhone, $discountAmount) {
        DB::table('customer_coupons')->insert([
            'customer_phone' => $customerPhone,
            'coupon_handle' => "50-off"
        ]);
    }
}
