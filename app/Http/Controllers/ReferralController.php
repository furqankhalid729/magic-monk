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

        $checkExisting = Referral::where('customer_number', $referee)
            ->first();

        if ($checkExisting) {
            return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
        }

        $referral = new Referral();
        $referral->referral_id = $referrer;
        $referral->customer_number = $referee;
        $referral->save();
       return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
    }
}
