<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionOffer extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image_url',
        'number_of_products',
        'discount_amount',
    ];
}
