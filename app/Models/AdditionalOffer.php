<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdditionalOffer extends Model
{
    protected $fillable = [
        'location_id',
        'discount_type',
        'discount_value',
        'expire_date',
    ];

    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'discount_value' => 'decimal:2',
        'expire_date'    => 'date',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope to get active offers (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expire_date')
              ->orWhere('expire_date', '>=', Carbon::today());
        });
    }
}
