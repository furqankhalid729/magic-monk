<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReferrals extends Model
{
    protected $fillable = ['referrer_number', 'referee_number', 'first_order_done','reward_given','joined_at','ordered_at'];
}
