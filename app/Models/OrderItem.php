<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_name',
        'price',
        'quantity',
        'amount',
    ];

    // Relationship back to order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
