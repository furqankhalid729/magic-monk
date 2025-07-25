<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function checkMetaData(Request $request)
    {
        $location = $request->query('location');
        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => 'Location parameter is required.'
            ], 400);
        }

        $locationData = Location::where('building_name', $location)->first();

        if (!$locationData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Location not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'offer_active' => $locationData->is_offer_live,
            'agent_logged_in' => $locationData->agent_logged_in,
            'offer_live_until' => $locationData->offer_live_until,
        ]);
    }

    public function getNearbyLocations(Request $request)
    {
        $corJson = $request->query('cor');

        if (!$corJson) {
            return response()->json([
                'status' => 'error',
                'message' => 'Coordinates (cor) are required.'
            ], 400);
        }
        $coordinates = json_decode($corJson, true);

        if (!is_array($coordinates) || !isset($coordinates['latitude'], $coordinates['longitude'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing latitude/longitude in cor.'
            ], 400);
        }

        $lat = $coordinates['latitude'];
        $lng = $coordinates['longitude'];

        $distanceInKm = 0.2;
        $locations = Location::all();
        $nearby = $locations->filter(function ($location) use ($lat, $lng, $distanceInKm) {
            $earthRadius = 6371;

            $dLat = deg2rad($location->latitude - $lat);
            $dLng = deg2rad($location->longitude - $lng);

            $a = sin($dLat / 2) * sin($dLat / 2) +
                cos(deg2rad($lat)) * cos(deg2rad($location->latitude)) *
                sin($dLng / 2) * sin($dLng / 2);

            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;

            return $distance <= $distanceInKm;
        })->values();

        $names = $nearby->mapWithKeys(function ($item, $index) {
            return ["building_name" . ($index + 1) => $item->building_name];
        })->toArray();

        $status = !empty(array_filter($names, fn($name) => !empty($name)));
        return response()->json([
            'status' => $status,
            'names' => $names
        ]);
    }

    public function testPayment($number){
        $response = sendInteraktMessage(
            $number,
            ['Hi User', 'Order #1234'], 
            ['https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/fZSiDosqseLO/WhatsApp%20Image%202025-07-15%20at%2017.39.09.jpeg?se=2030-07-12T14%3A28%3A34Z&sp=rt&sv=2019-12-12&sr=b&sig=dQShOEauRkfq6xrdOzrP%2B4ZmWcwPDcwYEng43lpyQHw%3D'],
            'paymentfm'
        );
        return response()->json($response);
    }
}
