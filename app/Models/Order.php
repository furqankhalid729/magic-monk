<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'order_id',
        'customer_phone',
        'building',
        'order_time',
        'delivery_time',
        'agent_number',
        'message_id',
        'total_amount',
        'delivered_on',
        'status',
        'address',
        'order_details',
        'review',
        'review_message_id'
    ];

    protected $casts = [
        'order_details' => 'array',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
