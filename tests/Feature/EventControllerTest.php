<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_visit_the_events_page(): void
    {
        $response = $this->get(route('events.index'));
        $response->assertOk();
    }

    public function test_authenticated_users_can_visit_the_events_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('events.index'));
        $response->assertOk();
    }

    public function test_guests_can_visit_an_event_page(): void
    {
        $event = Event::factory()->create();

        $this->get(route('events.show', ['event' => $event->id]))->assertOk();
    }

    public function test_the_events_page_sends_the_fields_the_cards_render(): void
    {
        Event::factory()->create([
            'slug' => 'athens-marathon',
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
            'description' => ['el' => 'Η αυθεντική διαδρομή.', 'en' => 'The authentic route.'],
            'category' => 'Marathon',
            'distance_meters' => 42200,
            'starts_at' => '2026-11-08 09:00:00',
            'city' => 'Athens',
            'country' => 'GR',
            'participants' => 18000,
            'image_url' => 'https://example.com/athens.jpg',
        ]);

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page
                ->component('Events/Index')
                ->where('events.0.slug', 'athens-marathon')
                ->where('events.0.name', 'Athens Marathon')
                ->where('events.0.description', 'The authentic route.')
                ->where('events.0.date', '2026-11-08')
                ->where('events.0.location', 'Athens, Greece')
                ->where('events.0.category', 'Marathon')
                ->where('events.0.distance', '42.2 km')
                ->where('events.0.participants', 18000)
                ->where('events.0.image', 'https://example.com/athens.jpg')
        );
    }

    public function test_the_events_page_sends_the_time_only_when_it_is_not_midnight_and_the_links(): void
    {
        Event::factory()->create([
            'starts_at' => '2026-12-06 08:00:00',
            'latitude' => '35.3387000',
            'longitude' => '25.1442000',
            'registration_url' => 'https://example.com/register',
            'distance_meters' => 70000,
        ]);
        Event::factory()->create([
            'starts_at' => '2026-12-12 00:00:00',
            'latitude' => null,
            'longitude' => null,
            'registration_url' => null,
        ]);

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page
                ->where('events.0.time', '08:00')
                ->where('events.0.latitude', 35.3387)
                ->where('events.0.longitude', 25.1442)
                ->where('events.0.registration_url', 'https://example.com/register')
                ->where('events.0.distance_meters', 70000)
                ->where('events.1.time', null)
                ->where('events.1.latitude', null)
                ->where('events.1.registration_url', null)
        );
    }

    public function test_the_events_page_speaks_the_locale_it_is_visited_in(): void
    {
        Event::factory()->create([
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
            'city' => 'Athens',
        ]);

        $this->get(route('el.events.index'))->assertInertia(
            fn ($page) => $page
                ->where('events.0.name', 'Μαραθώνιος Αθήνας')
                ->where('events.0.location', 'Athens, Ελλάδα')
        );
    }

    public function test_the_events_page_lists_the_race_coming_up_first(): void
    {
        $later = Event::factory()->create(['starts_at' => now()->addMonths(2)]);
        $sooner = Event::factory()->create(['starts_at' => now()->addWeek()]);

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page
                ->where('events.0.id', $sooner->id)
                ->where('events.1.id', $later->id)
                ->count('events', 2)
        );
    }

    public function test_drafts_are_kept_off_the_events_page(): void
    {
        $published = Event::factory()->create();
        Event::factory()->unpublished()->create();

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page->where('events.0.id', $published->id)->count('events', 1)
        );
    }

    public function test_a_draft_has_no_page_of_its_own(): void
    {
        $event = Event::factory()->unpublished()->create();

        $this->get(route('events.show', ['event' => $event->id]))->assertNotFound();
    }

    public function test_every_event_of_the_catalogue_has_its_own_page(): void
    {
        $this->seed(EventSeeder::class);

        foreach (Event::all() as $event) {
            $this->get(route('events.show', ['event' => $event->id]))->assertOk();
        }
    }

    public function test_visiting_an_unknown_event_returns_a_404(): void
    {
        $response = $this->get(route('events.show', ['event' => 999999]));
        $response->assertNotFound();
    }
}
