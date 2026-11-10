<?php

namespace Database\Factories;

use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureFlag>
 */
class FeatureFlagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'is_active' => false,
            'updated_by' => null,
        ];
    }

    /**
     * Switched on.
     */
    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }
}
