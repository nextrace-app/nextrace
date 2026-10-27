<?php

namespace Database\Factories;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $en = fake()->randomElement(['Runners Association of', 'Athletic Club', 'Cultural Association of']).' '.fake()->randomElement(['Chania', 'Heraklion', 'Rethymno', 'Ierapetra']);

        return [
            'slug' => Str::slug($en).'-'.fake()->unique()->numberBetween(1, 100000),
            'name' => ['en' => $en, 'el' => 'Σύλλογος '.fake()->randomElement(['Χανίων', 'Ηρακλείου', 'Ρεθύμνου', 'Ιεράπετρας'])],
            'email' => fake()->safeEmail(),
            'phone' => '69'.fake()->numerify('########'),
            'website' => fake()->url(),
            'social' => null,
        ];
    }
}
