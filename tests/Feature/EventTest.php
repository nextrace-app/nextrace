<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_event_is_stored_with_all_of_its_columns(): void
    {
        $event = Event::factory()->create([
            'slug' => 'athens-marathon',
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
            'description' => ['el' => 'Η αυθεντική διαδρομή.', 'en' => 'The authentic route.'],
            'category' => 'Marathon',
            'distance_meters' => 42200,
            'starts_at' => '2026-11-08 09:00:00',
            'ends_at' => null,
            'city' => 'Athens',
            'country' => 'GR',
            'participants' => 18000,
        ]);

        $this->assertDatabaseHas('events', [
            'slug' => 'athens-marathon',
            'category' => 'Marathon',
            'distance_meters' => 42200,
            'city' => 'Athens',
            'country' => 'GR',
        ]);

        $event->refresh();

        $this->assertSame(['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'], $event->name);
        $this->assertSame(42200, $event->distance_meters);
        $this->assertSame('2026-11-08 09:00:00', $event->starts_at->toDateTimeString());
        $this->assertNull($event->ends_at);
    }

    public function test_a_country_defaults_to_greece(): void
    {
        $attributes = Event::factory()->raw();
        unset($attributes['country']);

        $event = Event::query()->create($attributes);

        $this->assertSame('GR', $event->fresh()->country);
    }

    public function test_translated_text_follows_the_application_locale(): void
    {
        $event = Event::factory()->create([
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
        ]);

        $this->assertSame('Μαραθώνιος Αθήνας', $event->translated('name'));
        $this->assertSame('Athens Marathon', $event->translated('name', 'en'));

        $this->app->setLocale('en');

        $this->assertSame('Athens Marathon', $event->translated('name'));
    }

    public function test_translated_text_falls_back_when_a_locale_is_missing(): void
    {
        $event = Event::factory()->create([
            'name' => ['en' => 'Athens Marathon'],
        ]);

        $this->assertSame('Athens Marathon', $event->translated('name'));
    }

    public function test_two_events_cannot_share_a_slug(): void
    {
        Event::factory()->create(['slug' => 'athens-marathon']);

        $this->expectException(QueryException::class);

        Event::factory()->create(['slug' => 'athens-marathon']);
    }

    public function test_an_event_can_wait_to_be_published(): void
    {
        Event::factory()->create();
        Event::factory()->unpublished()->create();

        $this->assertSame(1, Event::whereNotNull('published_at')->count());
    }
}
