<?php

namespace Database\Factories;

use App\Models\Race;
use App\Models\RacePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RacePrice>
 */
class RacePriceFactory extends Factory
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
            'label' => ['en' => 'Standard', 'el' => 'Κανονική'],
            'amount_cents' => fake()->randomElement([1000, 1200, 1500, 2500, 3000, 7000]),
            'currency' => 'EUR',
            'valid_from' => null,
            'valid_until' => null,
        ];
    }
}
