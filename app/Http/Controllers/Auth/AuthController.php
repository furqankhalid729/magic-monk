<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
            
            $user = $this->findOrCreateUser($socialUser, 'google');
            
            Auth::login($user);
            
            return redirect()->intended('/dashboard')->with('success', 'Successfully logged in with Google!');
            
        } catch (\Exception $e) {
            return redirect('/sign-up')->with('error', 'Google login failed. Please try again.');
        }
    }

    /**
     * Redirect to Instagram OAuth provider
     */
    public function redirectToInstagram()
    {
        return Socialite::driver('instagram')->redirect();
    }

    /**
     * Handle Instagram OAuth callback
     */
    public function handleInstagramCallback()
    {
        try {
            $socialUser = Socialite::driver('instagram')->user();
            
            $user = $this->findOrCreateUser($socialUser, 'instagram');
            
            Auth::login($user);
            
            return redirect()->intended('/dashboard')->with('success', 'Successfully logged in with Instagram!');
            
        } catch (\Exception $e) {
            return redirect('/sign-up')->with('error', 'Instagram login failed. Please try again.');
        }
    }

    /**
     * Find or create user based on social provider data
     */
    private function findOrCreateUser($socialUser, $provider)
    {
        // First, try to find user by provider and provider_id
        $user = User::where('provider', $provider)
                   ->where('provider_id', $socialUser->getId())
                   ->first();

        if ($user) {
            return $user;
        }

        // If not found, try to find by email
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update existing user with provider info
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
            return $user;
        }

        // Create new user
        return User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'email_verified_at' => now(),
            'password' => null, // OAuth users don't need passwords
        ]);
    }
}
