<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppPayReminder extends Model
{
    protected $fillable = [
        'phone_number',
        'message_id',
        'is_sent',
        'sent_at',
        'order_data',
    ];

    protected $casts = [
        'order_data' => 'array',
    ];
}
