<?php

namespace Database\Factories;

use App\Enums\LeadStage;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => Lead::normalizePhone((string) $this->faker->numerify('##########')),
            'name' => $this->faker->name(),
            'company' => $this->faker->company(),
            'source' => 'survey:test',
            'stage' => LeadStage::New,
            'last_seen_at' => now(),
        ];
    }

    public function stage(LeadStage $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }

    public function withoutEmail(): static
    {
        return $this->state(fn () => ['email' => null]);
    }

    public function withoutPhone(): static
    {
        return $this->state(fn () => ['phone' => null]);
    }
}
