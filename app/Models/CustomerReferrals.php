<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReferrals extends Model
{
    protected $fillable = ['referral_code', 'referrer_number', 'first_order_done', 'reward_given'];
}
