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

        return response()->json([
            'status' => 'success',
            'data' => $nearby
        ]);
    }
}
