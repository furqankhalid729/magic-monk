<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Referral;

class ReferralController extends Controller
{
    public function add(Request $request)
    {
        $referrer = $request->query('referrer');
        $referee  = $request->query('referee');
        $whatsappNumber = env('MONK_WHATSAPP_NUMBER');
        $message = urlencode("Hi");
        
        if (empty($referrer) || empty($referee)) {
            return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
        }

        $checkExisting = Referral::where('referee', $referee)
            ->first();

        if ($checkExisting) {
            return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
        }

        $referral = new Referral();
        $referral->referrer = $referrer;
        $referral->referee  = $referee;
        $referral->status   = 'pending';
        $referral->save();
       return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
    }
}
