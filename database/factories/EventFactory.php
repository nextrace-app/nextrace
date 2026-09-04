<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->randomElement(['Athens', 'Thessaloniki', 'Chania', 'Nafplio', 'Ioannina', 'Patras']);
        $category = fake()->randomElement(['5K', '10K', 'Half Marathon', 'Marathon', 'Trail', 'Ultra']);

        $name = "{$city} {$category}";

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'name' => ['el' => $name, 'en' => $name],
            'description' => [
                'el' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'category' => $category,
            'distance_meters' => fake()->randomElement([5000, 10000, 21100, 42200, 80000, 246000]),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+1 year'),
            'ends_at' => null,
            'city' => $city,
            'country' => 'GR',
            'latitude' => fake()->latitude(35, 41),
            'longitude' => fake()->longitude(20, 28),
            'participants' => fake()->numberBetween(400, 18000),
            'image_url' => fake()->imageUrl(800, 600),
            'registration_url' => fake()->url(),
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the event is not published yet.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
