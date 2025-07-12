<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentDailyStock extends Model
{
    protected $fillable = [
        'agent_id',
        'product_id',
        'picked_qty',
        'returned_qty',
        'date',
        'picked_at',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
