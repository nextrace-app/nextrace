<?php

namespace Database\Factories;

use App\Constants\Events;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => fake()->randomElement(array_column(Events::LIST, 'id')),
        ];
    }

    /**
     * Indicate the event the favorite points at.
     */
    public function forEvent(int $eventId): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $eventId,
        ]);
    }
}
