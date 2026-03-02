<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'address',
        'nie',
        'additional_info',
    ];
}
