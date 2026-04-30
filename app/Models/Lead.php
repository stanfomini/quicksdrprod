<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Find an existing lead matching email-or-phone (in that order) and update
     * its empty fields with the supplied data, or create a new lead.
     *
     * @param  array{email?: ?string, phone?: ?string, name?: ?string, company?: ?string, source?: ?string, custom_fields?: ?array<string, mixed>}  $fields
     */
    public static function findOrCreateMatching(array $fields): self
    {
        $email = self::normalizeEmail($fields['email'] ?? null);
        $phone = self::normalizePhone($fields['phone'] ?? null);

        $lead = null;
        if ($email !== null) {
            $lead = static::query()->where('email', $email)->first();
        }
        if ($lead === null && $phone !== null) {
            $lead = static::query()->where('phone', $phone)->first();
        }

        $payload = [
            'email' => $email,
            'phone' => $phone,
            'name' => self::trimOrNull($fields['name'] ?? null),
            'company' => self::trimOrNull($fields['company'] ?? null),
            'source' => self::trimOrNull($fields['source'] ?? null),
            'custom_fields' => $fields['custom_fields'] ?? null,
        ];

        if ($lead === null) {
            $payload['last_seen_at'] = now();

            return static::create(array_filter($payload, static fn ($v) => $v !== null));
        }

        // Existing lead — fill empty fields without overwriting present data.
        foreach (['email', 'phone', 'name', 'company', 'source'] as $key) {
            if (empty($lead->{$key}) && ! empty($payload[$key])) {
                $lead->{$key} = $payload[$key];
            }
        }
        if (! empty($payload['custom_fields'])) {
            $lead->custom_fields = array_merge((array) $lead->custom_fields, $payload['custom_fields']);
        }
        $lead->last_seen_at = now();
        $lead->save();

        return $lead;
    }

    /**
     * If this lead's email or phone matches a Tenant's admin contact, link them.
     * Idempotent — only writes when a match is found and tenant_id isn't already set
     * to that tenant.
     */
    public function linkToTenantIfPossible(): void
    {
        $query = Tenant::query();
        $hasMatch = false;

        if (! empty($this->email)) {
            $query->orWhere('admin_email', $this->email);
            $hasMatch = true;
        }
        if (! empty($this->phone)) {
            $query->orWhere('admin_phone', $this->phone);
            $hasMatch = true;
        }
        if (! $hasMatch) {
            return;
        }

        $tenant = $query->first();
        if ($tenant === null) {
            return;
        }

        if ($this->tenant_id !== $tenant->id) {
            $this->tenant_id = $tenant->id;
            $this->save();
        }
    }

    public static function normalizeEmail(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $trimmed = strtolower(trim($raw));

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Strip everything but digits, then add a leading + (US assumed if 10 digits).
     */
    public static function normalizePhone(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        return '+'.$digits;
    }

    protected static function trimOrNull(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $trimmed = trim($raw);

        return $trimmed === '' ? null : $trimmed;
    }
}
