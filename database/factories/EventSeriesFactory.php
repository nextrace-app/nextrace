<?php

namespace Database\Factories;

use App\Models\EventSeries;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventSeries>
 */
class EventSeriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Chania', 'Heraklion', 'Rethymno', 'Lasithi']).' '.fake()->randomElement(['Marathon', 'Night Run', 'Trail Run']);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'name' => ['en' => $name, 'el' => $name],
            'description' => ['en' => fake()->sentence(), 'el' => fake()->sentence()],
            'organizer_id' => null,
            'logo_url' => null,
            'website' => fake()->url(),
        ];
    }
}
