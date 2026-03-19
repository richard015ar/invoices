<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'address',
        'nie',
        'additional_info',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId): self
    {
        $user = User::query()->find($userId);

        return self::query()->firstOrCreate(['user_id' => $userId], [
            'user_id' => $userId,
            'name' => $user?->name ?? 'New User',
            'email' => $user?->email,
            'address' => null,
            'nie' => null,
            'additional_info' => null,
        ]);
    }
}
