<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventLink;
use App\Models\Race;
use App\Models\User;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_the_events_page_sends_the_configured_page_size(): void
    {
        config(['events.per_page' => 5]);

        $this->get(route('events.index'))->assertInertia(fn (Assert $page) => $page->where('perPage', 5));
    }

    public function test_guests_can_visit_an_event_page(): void
    {
        $event = Event::factory()->create();

        $this->get(route('events.show', ['event' => $event]))->assertOk();
    }

    public function test_the_events_page_sends_the_fields_the_cards_render(): void
    {
        $this->travelTo('2026-10-24');

        Event::factory()->withMainRace([
            'distance_class' => 'marathon',
            'distance_meters' => 42200,
            'starts_at' => '2026-11-08 09:00:00',
        ])->create([
            'slug' => 'athens-marathon',
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
            'description' => ['el' => 'Η αυθεντική διαδρομή.', 'en' => 'The authentic route.'],
            'starts_on' => '2026-11-08',
            'city' => 'Athens',
            'country' => 'GR',
            'participants' => 18000,
            'image_url' => 'https://example.com/athens.jpg',
        ]);

        $this->get(route('en.events.index'))->assertInertia(
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
        $this->travelTo('2026-10-24');

        Event::factory()->withMainRace(['distance_meters' => 70000, 'starts_at' => '2026-12-06 08:00:00'])->create([
            'starts_on' => '2026-12-06',
            'latitude' => '35.3387000',
            'longitude' => '25.1442000',
            'registration_url' => 'https://example.com/register',
        ]);
        Event::factory()->withMainRace(['starts_at' => '2026-12-12 00:00:00', 'start_time_known' => false])->create([
            'starts_on' => '2026-12-12',
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

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page
                ->where('events.0.name', 'Μαραθώνιος Αθήνας')
                ->where('events.0.location', 'Athens, Ελλάδα')
        );
    }

    public function test_the_events_page_lists_the_race_coming_up_first(): void
    {
        $later = Event::factory()->startingAt(now()->addMonths(2))->create();
        $sooner = Event::factory()->startingAt(now()->addWeek())->create();

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

        $this->get(route('events.show', ['event' => $event]))->assertNotFound();
    }

    public function test_events_on_the_same_day_are_ordered_by_the_start_of_their_main_race(): void
    {
        $this->travelTo('2026-10-24');

        $afternoon = Event::factory()->startingAt('2026-12-06 17:00:00')->create();
        $morning = Event::factory()->startingAt('2026-12-06 08:00:00')->create();

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page->where('events.0.id', $morning->id)->where('events.1.id', $afternoon->id)
        );
    }

    public function test_a_past_event_is_kept_off_the_events_page(): void
    {
        $this->travelTo('2026-10-24 12:00:00');

        Event::factory()->startingAt('2026-10-23 09:00:00')->create();
        $coming = Event::factory()->startingAt('2026-10-25 09:00:00')->create();

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page->where('events.0.id', $coming->id)->count('events', 1)
        );
    }

    public function test_an_event_happening_today_is_still_listed(): void
    {
        $this->travelTo('2026-10-24 20:00:00');

        $today = Event::factory()->startingAt('2026-10-24 08:00:00')->create();

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page->where('events.0.id', $today->id)->count('events', 1)
        );
    }

    public function test_the_day_is_the_day_in_greece(): void
    {
        // 22:30 UTC on the 24th is already the 25th in Athens.
        $this->travelTo('2026-10-24 22:30:00');

        Event::factory()->startingAt('2026-10-24 08:00:00')->create();

        $this->get(route('events.index'))->assertInertia(fn ($page) => $page->count('events', 0));
    }

    public function test_a_multi_day_event_stays_listed_until_its_last_day(): void
    {
        $this->travelTo('2026-10-24');

        $running = Event::factory()->startingAt('2026-10-23 09:00:00')->create(['ends_on' => '2026-10-25']);
        Event::factory()->startingAt('2026-10-21 09:00:00')->create(['ends_on' => '2026-10-23']);

        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page->where('events.0.id', $running->id)->count('events', 1)
        );
    }

    public function test_the_page_of_a_past_event_still_works(): void
    {
        $this->travelTo('2026-10-24');

        $past = Event::factory()->startingAt('2026-03-01 09:00:00')->create();

        $this->get(route('events.show', $past))->assertOk();
        $this->get(route('events.index'))->assertInertia(fn ($page) => $page->count('events', 0));
    }

    public function test_the_event_page_sends_the_races_links_and_details(): void
    {
        $event = Event::factory()->create(['registration_status' => 'open', 'date_confirmed' => false]);
        Race::factory()->for($event)->create(['slug' => 'five', 'distance_meters' => 5000]);
        EventLink::factory()->for($event)->create(['url' => 'https://example.com/site']);

        $this->get(route('events.show', $event))->assertInertia(
            fn ($page) => $page
                ->component('Events/Show')
                ->count('event.races', 2)
                ->where('event.races.1.slug', 'five')
                ->where('event.links.0.url', 'https://example.com/site')
                ->where('event.registration.status', 'open')
                ->where('event.date_confirmed', false)
        );
    }

    public function test_every_event_of_the_catalogue_has_its_own_page(): void
    {
        $this->seed(EventSeeder::class);

        foreach (Event::all() as $event) {
            $this->get(route('events.show', ['event' => $event]))->assertOk();
        }
    }

    public function test_an_event_page_lives_at_its_slug(): void
    {
        $event = Event::factory()->create(['slug' => 'crete-marathon']);

        $this->assertSame(url('/events/crete-marathon'), route('events.show', $event));
        $this->get('/events/crete-marathon')->assertOk();
        $this->get('/en/events/crete-marathon')->assertOk();
    }

    public function test_an_id_is_not_an_address_for_an_event(): void
    {
        $event = Event::factory()->create();

        $this->get("/events/{$event->id}")->assertNotFound();
    }

    public function test_addresses_that_are_not_slugs_return_a_404_instead_of_an_error(): void
    {
        foreach (['abc', 'a%20b', '123456789012345678901234567890', 'Crete-Marathon'] as $segment) {
            $this->get("/events/{$segment}")->assertNotFound();
        }
    }

    public function test_visiting_an_unknown_event_returns_a_404(): void
    {
        $response = $this->get(route('events.show', ['event' => 'no-such-race']));
        $response->assertNotFound();
    }
}
