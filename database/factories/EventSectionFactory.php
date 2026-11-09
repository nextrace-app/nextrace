<?php

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Event;
use App\Models\EventSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSection>
 */
class EventSectionFactory extends Factory
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
            'type' => SectionType::Transport,
            'title' => null,
            'body' => ['en' => fake()->paragraph(), 'el' => fake()->paragraph()],
            'sort_order' => 0,
        ];
    }
}
