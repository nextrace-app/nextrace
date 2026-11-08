<?php

namespace Database\Factories;

use App\Enums\KeyDateType;
use App\Models\Event;
use App\Models\EventDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventDate>
 */
class EventDateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'type' => KeyDateType::Expo,
            'starts_on' => fake()->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d'),
            'ends_on' => null,
            'label' => null,
            'sort_order' => 0,
        ];
    }
}
