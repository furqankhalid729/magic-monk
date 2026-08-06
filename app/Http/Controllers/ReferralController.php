<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Referral;
use App\Models\CustomerReferrals;

class ReferralController extends Controller
{
    public function add(Request $request)
    {
        $referrer = $request->query('referral');
        $referee  = $request->query('referee');
        $whatsappNumber = env('MONK_WHATSAPP_NUMBER');
        $message = urlencode("Hi");
        
        if (empty($referrer) || empty($referee)) {
            return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
        }

        $checkExisting = CustomerReferrals::where('referrer_number', $referee)
            ->first();

        if ($checkExisting) {
            return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
        }

        $referral = new CustomerReferrals();
        $referral->referral_code = $referrer;
        $referral->referrer_number = $referee;
        $referral->first_order_done = false;
        $referral->reward_given = false;
        $referral->save();
       return redirect("https://wa.me/{$whatsappNumber}?text={$message}");
    }
}
