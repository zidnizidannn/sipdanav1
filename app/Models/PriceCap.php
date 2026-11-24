<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceCap extends Model
{
    protected $fillable = [
        'item_name',
        'unit',
        'max_price',
        'scope',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'max_price' => 'integer',
        'is_active' => 'boolean',
    ];
}