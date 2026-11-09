<?php

namespace Database\Factories;

use App\Models\Race;
use App\Models\RaceCheckpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaceCheckpoint>
 */
class RaceCheckpointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_id' => Race::factory(),
            'distance_meters' => 21097,
            'closes_at' => fake()->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d').' 12:00:00',
        ];
    }
}
