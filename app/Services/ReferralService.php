<?php

namespace App\Services;

use App\Models\CustomerReferrals;
use App\Models\CustomerCoupon;

class ReferralService
{
    /**
     * Create a referral record
     */
    public function createReferral(array $data): CustomerReferrals
    {
        $referral = CustomerReferrals::create([
            'referral_code'   => $data['referralCode'] ?? $data['referrerPhone'],
            'referrer_number' => $data['referrerPhone'],
            'first_order_done' => false,
            'reward_given'    => false,
        ]);

        // Then create coupon
        CustomerCoupon::create([
            'coupon_handle'   => 'referee-code',
            'customer_phone'  => $data['refereePhone'],
        ]);

        // Finally return the referral object
        return $referral;
    }
}
