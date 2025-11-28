<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user exists
            $user = User::where('email', $googleUser->getEmail())->first();

            // If user doesn't exist, create new
            if (!$user) {
                $user = User::create([
                    'name'  => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt('google_default_password'), // not used but required
                ]);
            }

            // Login user
            Auth::login($user);

            return redirect('/dashboard'); // Redirect wherever you want

        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Google login failed.');
        }
    }
}
