<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        // If user exists AND phone_number is empty
        if ($user && empty($user->phone_number)) {
            // Get phone number from cookie
            $cookiePhone = request()->cookie('signup_phone');
            // If cookie has value, update user
            if (!empty($cookiePhone)) {
                $user->phone_number = $cookiePhone;
                $user->save();
            }
        }

        $buildings = Location::all();

        return view('user.dashboard', compact('user', 'buildings'));
    }

    public function store(Request $request)
    {
        Log::info('Storing user info:', [$request->all(), $request->address]);
        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string',
            'terms' => 'accepted',
            'phone_number' => 'nullable|string',
        ], [
            'terms.accepted' => 'You must agree to the terms before submitting.',
        ]);
        // GET LOGGED-IN USER
        $user = auth()->user();

        // DEBUG: Log what we're about to update
        Log::info('About to update user with:', [
            'name'       => $request->full_name,
            'email'      => $request->email,
            'date_of_birth'  => $request->date_of_birth,
            'gender'     => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'building_name' => $request->building_name,
            'city' => $request->city,
            'state' => $request->state,
            'sub_locality' => $request->sub_locality,
        ]);

        // UPDATE USER INFO
        $result = $user->update([
            'name'       => $request->full_name,
            'email'      => $request->email,
            'date_of_birth'  => $request->date_of_birth,
            'gender'     => $request->gender,
            'phone_number' => $request->phone_number,

            'address' => $request->address,
            'building_name' => $request->building_name,
            'city' => $request->city,
            'state' => $request->state,
            'sub_locality' => $request->sub_locality,

        ]);

        $apiKey = env('INTERAKT_API_KEY');
        $headers = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        $body = [
            "phoneNumber" => substr($user->phone_number, 2),
            "countryCode" => "+92",
            "traits" => [
                "Email" => $user->email,
                "building"=>$user->building_name,
                "FullAddress"=>$user->address,
                "gender"=>$user->gender,
                "birthday"=>$user->date_of_birth,
            ]
        ];

        $response = Http::withHeaders($headers)
            ->post(env('INTERAKT_USER_TRAIT_UPDATE_URL'), $body);

        Log::info('Update result:', ['success' => $result, 'user_after' => $user->refresh()->toArray(), 'interakt_response' => $response->json()]);
        return redirect()->route('survey');
    }

    public function updateField(Request $request)
    {
        $request->validate([
            'field' => 'required|string',
            'value' => 'required',
        ]);

        $user = auth()->user();

        // Allow only certain fields
        $allowedFields = ['date_of_birth', 'gender', 'phone_number', 'building_name', 'city', 'state', 'sub_locality', 'address'];
        if (!in_array($request->field, $allowedFields)) {
            return response()->json(['error' => 'Field not allowed'], 200);
        }

        $user->{$request->field} = $request->value;
        $user->save();

        return response()->json(['success' => 'Field updated successfully']);
    }
}
