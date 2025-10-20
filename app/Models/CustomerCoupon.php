<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCoupon extends Model
{
    protected $fillable = ['coupon_handle', 'customer_phone'];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_handle', 'handle');
    }
}
