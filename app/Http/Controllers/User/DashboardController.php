<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('user.dashboard', compact('user'));
    }

    public function store(Request $request)
    {
        // VALIDATION
        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'You must agree to the terms before submitting.',
        ]);

        // GET LOGGED-IN USER
        $user = auth()->user();

        // UPDATE USER INFO
        $user->update([
            'name'       => $request->full_name,
            'email'      => $request->email,
            'date_of_birth'  => $request->date_of_birth,
            'gender'     => $request->gender,
        ]);

        return redirect()->back()->with('success', 'Your information has been updated successfully!');
    }
}
