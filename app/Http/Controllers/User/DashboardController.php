<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $buildings = Location::all();
        return view('user.dashboard', compact('user', 'buildings'));
    }

    public function store(Request $request)
    {
        Log::info('Storing user info:', [$request->all(), $request->address]);
        // VALIDATION
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

        Log::info('Update result:', ['success' => $result, 'user_after' => $user->refresh()->toArray()]);

        return redirect()->back()->with('success', 'Your information has been updated successfully!');
    }

    public function updateField(Request $request)
    {
        $request->validate([
            'field' => 'required|string',
            'value' => 'required',
        ]);

        $user = auth()->user();

        // Allow only certain fields
        $allowedFields = ['date_of_birth', 'gender', 'phone_number','building_name', 'city', 'state', 'sub_locality', 'address'];
        if (!in_array($request->field, $allowedFields)) {
            return response()->json(['error' => 'Field not allowed'], 200);
        }

        $user->{$request->field} = $request->value;
        $user->save();

        return response()->json(['success' => 'Field updated successfully']);
    }
}
