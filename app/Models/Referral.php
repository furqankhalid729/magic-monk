<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = ['referee', 'referrer', 'status', 'accepted_at', 'rewarded_at'];
}
