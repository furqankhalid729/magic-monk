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
            'referee_number'  => $data['refereePhone'],
            'referrer_number' => $data['referrerPhone'],
            'first_order_done' => false,
            'reward_given'    => false,
            'joined_at'       => now(),
            'ordered_at'      => null,
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
