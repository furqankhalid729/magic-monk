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
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!$lat || !$lng) {
            return response()->json([
                'status' => 'error',
                'message' => 'Latitude and Longitude are required.'
            ], 400);
        }

        $distance = 0.1;
        $locations = Location::select('*')
            ->selectRaw("(
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $distance)
            ->orderBy('distance', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $locations
        ]);
    }
}
