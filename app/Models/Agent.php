<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'name',
        'photo_path',
        'whatsapp_number',
        'pan_number',
        'pan_card_path',
        'aadhar_card_path',
        'upi_id',
        'city',
        'status',
        'source_pos',
        'source_type'
    ];

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
