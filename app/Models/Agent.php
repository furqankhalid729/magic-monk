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
        'source_type',
        'bypass_device_check',
    ];

    protected $hidden = [
        'device_info',
    ];

    protected $casts = [
        'bypass_device_check' => 'boolean',
        'device_info' => 'array',
        'device_registered_at' => 'datetime',
    ];

    public function loginSessions()
    {
        return $this->hasMany(AgentLoginSession::class);
    }

    public function resetDevice(): void
    {
        $this->forceFill([
            'device_id' => null,
            'device_info' => null,
            'device_registered_at' => null,
        ])->save();
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class)
            ->withTimestamps();
    }

    public function primaryLocations()
    {
        return $this->hasMany(Location::class);
    }
}
