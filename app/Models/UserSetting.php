<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'monthly_tax_reserve',
    ];

    protected $casts = [
        'monthly_tax_reserve' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['monthly_tax_reserve' => 1750.00]
        );
    }
}
