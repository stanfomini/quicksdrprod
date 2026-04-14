<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Enums\TenantTier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $slug = Str::slug(fake()->unique()->company());

        return [
            'business_name' => fake()->company(),
            'admin_name' => fake()->name(),
            'admin_email' => fake()->unique()->safeEmail(),
            'admin_phone' => fake()->phoneNumber(),
            'subdomain' => $slug.'-'.Str::random(4),
            'tier' => TenantTier::Tier1,
            'status' => TenantStatus::Ready,
            'stats_hmac_key' => Str::random(64),
        ];
    }

    public function exportOnly(?\DateTimeInterface $until = null): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::ExportOnly,
            'export_only_until' => $until ?? now()->addDays(7),
        ]);
    }
}
