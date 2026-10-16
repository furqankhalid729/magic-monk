<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentLoginSession extends Model
{
    protected $fillable = [
        'agent_id',
        'login_date',
        'login_at',
        'logout_at',
        'device_id',
        'device_info',
        'device_matched',
        'blocked',
        'mismatch_fields',
        'ip',
        'public_ip',
        'app_version',
    ];

    protected $casts = [
        'login_date' => 'date',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'device_info' => 'array',
        'device_matched' => 'boolean',
        'blocked' => 'boolean',
        'mismatch_fields' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
