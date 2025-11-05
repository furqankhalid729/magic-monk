<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['shipping_rate','fast_mover_shipping_rate'];
}
