<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Laravel\Telescope\Telescope;
use App\Models\Location;
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

        // $body = [
        //     "fullPhoneNumber" => $phoneNumber,
        //     "campaignId" => $campaignId,
        //     "type" => "Template",
        //     "template" => [
        //         "name" => $templateName,
        //         "languageCode" => "en",
        //         "headerValues" => $headerValues,
        //         "bodyValues" => $bodyValues,
        //     ]
        // ];

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
                        "reference_id" => "22july25mjwapaytemp1",
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
                            "expiration_message" => ""
                        ]
                    ]
                ]
            ]
        ];


        $request = new Request('POST', env('INTERAKT_MESSAGE_API_URL'), $headers, json_encode($body));

        try {
            $res = $client->sendAsync($request)->wait();
            return json_decode($res->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('getAgentPhoneNumber')) {
    function getAgentPhoneNumber($bulding)
    {
        // Log::info('getAgentPhoneNumber called with coordinates', ['cor' => $cor]);
        // $coordinates = json_decode($cor, true);
        // Log::info('Parsed coordinates', ['coordinates' => $coordinates]);
        // if (!isset($coordinates['latitude'], $coordinates['longitude'])) {
        //     return null;
        // }

        // $lat = $coordinates['latitude'];
        // $lng = $coordinates['longitude'];

        // // Step 2: Filter nearby locations (within 0.2 km)
        // $distanceInKm = 0.2;
        // $locations = Location::all();

        // $nearby = $locations->filter(function ($location) use ($lat, $lng, $distanceInKm) {
        //     $earthRadius = 6371;

        //     $dLat = deg2rad($location->latitude - $lat);
        //     $dLng = deg2rad($location->longitude - $lng);

        //     $a = sin($dLat / 2) ** 2 +
        //         cos(deg2rad($lat)) * cos(deg2rad($location->latitude)) *
        //         sin($dLng / 2) ** 2;

        //     $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        //     $distance = $earthRadius * $c;

        //     return $distance <= $distanceInKm;
        // })->values();

        // $firstNearby = $nearby->first();

        $location = Location::where('building_name', $bulding)->get();
        if ($location->isNotEmpty()) {
            return $location->first()->agent->whatsapp_number;
        }

        return null;
    }
}

if (!function_exists('createInteraktEvent')) {
    function createInteraktEvent($agentNumber,$eventName, $eventData = [])
    {
        $apiKey = env('INTERAKT_API_KEY');

        $client = new Client();

        $headers = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        $body = [
            "fullPhoneNumber" => "+919867806668",
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
