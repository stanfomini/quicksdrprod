<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminSignupToken extends Model
{
    protected $fillable = [
        'token',
        'email_hint',
        'role',
        'company_ids',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'company_ids' => 'array',
        ];
    }

    public function scopeValid(Builder $query): void
    {
        $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    /**
     * Generate a fresh single-use admin signup token.
     */
    public static function generate(?string $emailHint = null, int $expiresInHours = 72, ?string $role = null, ?array $companyIds = null): self
    {
        return self::create([
            'token' => bin2hex(random_bytes(32)),
            'email_hint' => $emailHint,
            'role' => $role,
            'company_ids' => $companyIds,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    /**
     * Atomically claim this token. Returns true if the caller successfully
     * claimed it (and may proceed to create the user). Returns false if the
     * token was already used, expired, or no longer exists — in which case
     * the caller MUST abort and not create the user.
     *
     * Race-safe: relies on the SQL row-level UPDATE returning 0 or 1 rows
     * affected based on the WHERE clause filtering on used_at IS NULL.
     */
    public static function claim(string $token): bool
    {
        $rowsAffected = self::query()
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        return $rowsAffected === 1;
    }
}
