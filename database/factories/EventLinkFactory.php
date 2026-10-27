<?php

namespace Database\Factories;

use App\Enums\LinkType;
use App\Models\Event;
use App\Models\EventLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventLink>
 */
class EventLinkFactory extends Factory
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
            'type' => LinkType::Website,
            'url' => fake()->url(),
            'label' => null,
            'sort_order' => 0,
        ];
    }
}
