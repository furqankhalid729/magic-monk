<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSubscription extends Model
{
    protected $fillable = [
        'customer_phone',
        'subscription_id',
        'status',
        'order_count',
        'start_at',
        'end_at',
    ];
}
