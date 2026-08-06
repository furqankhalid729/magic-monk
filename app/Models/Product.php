<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'odoo_product_id',
        'odoo_product_sku',
        'odoo_product_name',
        'price',
        'inventory',
        'image',
    ];
}
