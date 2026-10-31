<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders_the_home_component(): void
    {
        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->component('Home')
        );
    }

    public function test_the_home_page_renders_the_home_component_for_every_locale(): void
    {
        $this->get(route('en.home'))->assertInertia(
            fn ($page) => $page->component('Home')->where('locale', 'en')
        );
    }

    public function test_the_home_page_lists_the_three_next_published_events(): void
    {
        $first = Event::factory()->create(['starts_on' => now()->addDays(10)]);
        $second = Event::factory()->create(['starts_on' => now()->addDays(20)]);
        $third = Event::factory()->create(['starts_on' => now()->addDays(30)]);
        Event::factory()->create(['starts_on' => now()->addDays(40)]);

        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->has('events', 3)
                ->where('events.0.id', $first->id)
                ->where('events.1.id', $second->id)
                ->where('events.2.id', $third->id)
        );
    }

    public function test_the_home_page_skips_drafts_and_past_events(): void
    {
        $upcoming = Event::factory()->create(['starts_on' => now()->addDays(10)]);
        Event::factory()->unpublished()->create(['starts_on' => now()->addDays(5)]);
        Event::factory()->create(['starts_on' => now()->subDays(5)]);

        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->has('events', 1)->where('events.0.id', $upcoming->id)
        );
    }

    public function test_the_home_page_has_no_events_when_nothing_is_upcoming(): void
    {
        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->has('events', 0)->where('favorites', [])
        );
    }
}
