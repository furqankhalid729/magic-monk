<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransfer extends Model
{
    protected $fillable = [
        'source_agent_id',
        'destination_agent_id',
        'transfer_type',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function sourceAgent()
    {
        return $this->belongsTo(Agent::class, 'source_agent_id');
    }

    public function destinationAgent()
    {
        return $this->belongsTo(Agent::class, 'destination_agent_id');
    }
}
