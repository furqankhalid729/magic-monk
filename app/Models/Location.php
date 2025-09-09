<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'type',
        'building_name',
        'google_map_url',
        'agent_id',
        'agent_logged_in',
        'is_offer_live',
        'offer_live_until',
        'latitude',
        'longitude',

        'reach_or_flats',
        'road_name',
        'sub_locality',
        'city',
        'state',
        'pincode',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
