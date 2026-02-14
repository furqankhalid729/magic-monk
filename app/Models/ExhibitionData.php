<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExhibitionData extends Model
{
    protected $table = 'exhibition_data';

    protected $fillable = [
        'customer_phone',
        'customer_name',
        'customer_email',
        'picked_product',
        'rating',
        'feedback',
    ];
}
