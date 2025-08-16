<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Laravel\Telescope\Telescope;
use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                "bodyValues" => $bodyValues,
                "order_details" => [
                    [
                        "reference_id" => "furqanTest",
                        "order_items" => [
                            [
                                "name" => "Butterscotch Icecream 100 ml (promo)",
                                "quantity" => 1,
                                "amount" => 39,
                                "country_of_origin" => "India"
                            ]
                        ],
                        "shipping_addresses" => [
                            [
                                "name" => "Nikunj B",
                                "phone_number" => "919867806668",
                                "address" => "Bandra Kurla Complex",
                                "city" => "Mumbai",
                                "state" => "Maharastra",
                                "in_pin_code" => "400051",
                                "house_number" => "12",
                                "tower_number" => "5",
                                "building_name" => "One BKC",
                                "landmark_area" => "Near BKC Circle",
                                "country" => "IN"
                            ]
                        ],
                        "subtotal" => 39,
                        "discount" => 0,
                        "tax" => 0,
                        "shipping" => 0,
                        "total_amount" => 39,
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
            return $response->json();
        } else {
            return ['error' => $response->body()];
        }
    }
}

if (!function_exists('sendWhatsAppPay')) {
    function sendWhatsAppPay($phoneNumber, $bodyValues = [], $headerValues = [], $templateName = 'paymentfm_with_pod2', $campaignId = null, $orderItems = [],$totalAmount = 0, $orderId = "order67557",$address)
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
            "fullPhoneNumber" => "+923135052539",//$phoneNumber,
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
                        "subtotal" => $totalAmount,
                        "discount" => 0,
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
            ];
        }

        return null;
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
             "sound"=> "notification",
            'priority' => 'high',
        ]);

        return $response->json();
    }
}
