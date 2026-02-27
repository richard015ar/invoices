<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_unit_price',
        'default_tax_rate',
        'is_active',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
        'default_tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
