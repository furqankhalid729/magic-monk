<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'handle',
        'name',
        'rank',
        'status',
        'expiration_days',
        'discount_amount'
    ];

    public function customerCoupons()
    {
        return $this->hasMany(CustomerCoupon::class, 'coupon_handle', 'handle');
    }
}
