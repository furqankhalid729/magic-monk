<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'type',
        'building_name',
        'handle',
        'odoo_pos_config_id',
        'odoo_pos_config_name',
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

        'buy_1_get_1_offer'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function agents()
    {
        return $this->belongsToMany(Agent::class)
            ->withTimestamps();
    }

    public function primaryAgent(): ?Agent
    {
        if ($this->relationLoaded('agents') && $this->agents->isNotEmpty()) {
            return $this->agents->first();
        }

        return $this->agent;
    }

    public function additionalOffers()
    {
        return $this->hasMany(AdditionalOffer::class);
    }

    public function liveAdditionalOffers()
    {
        return $this->hasMany(AdditionalOffer::class)->active();
    }
}
