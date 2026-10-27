<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventLink;
use App\Models\EventSeries;
use App\Models\Organizer;
use App\Models\Race;
use App\Models\RacePrice;
use App\Models\Region;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_event_is_stored_with_all_of_its_columns(): void
    {
        $event = Event::factory()->startingAt('2026-11-08 09:00:00')->create([
            'slug' => 'athens-marathon',
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
            'description' => ['el' => 'Η αυθεντική διαδρομή.', 'en' => 'The authentic route.'],
            'ends_on' => null,
            'city' => 'Athens',
            'country' => 'GR',
            'participants' => 18000,
        ]);

        $this->assertDatabaseHas('events', [
            'slug' => 'athens-marathon',
            'starts_on' => '2026-11-08',
            'city' => 'Athens',
            'country' => 'GR',
        ]);

        $event->refresh();

        $this->assertSame(['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'], $event->name);
        $this->assertSame('2026-11-08', $event->starts_on->toDateString());
        $this->assertNull($event->ends_on);
        $this->assertSame('2026-11-08 09:00:00', $event->mainRace->starts_at->toDateTimeString());
    }

    public function test_the_defaults_of_a_new_event(): void
    {
        $attributes = Event::factory()->raw();
        unset($attributes['country']);

        $event = Event::query()->create($attributes)->fresh();

        $this->assertSame('GR', $event->country);
        $this->assertSame('Europe/Athens', $event->timezone);
        $this->assertSame(EventStatus::Scheduled, $event->status);
        $this->assertSame(RegistrationStatus::Unknown, $event->registration_status);
        $this->assertTrue($event->date_confirmed);
        $this->assertFalse($event->medical_certificate_required);
    }

    public function test_status_and_registration_status_are_cast_to_enums(): void
    {
        $event = Event::factory()->create(['status' => 'postponed', 'registration_status' => 'sold_out']);

        $event = $event->fresh();

        $this->assertSame(EventStatus::Postponed, $event->status);
        $this->assertSame(RegistrationStatus::SoldOut, $event->registration_status);
    }

    public function test_the_factory_creates_an_event_with_one_main_race(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(1, $event->races()->count());
        $this->assertTrue($event->mainRace->is_main);
        $this->assertSame($event->starts_on->toDateString(), $event->mainRace->starts_at->toDateString());
    }

    public function test_the_factory_can_leave_the_races_out(): void
    {
        $event = Event::factory()->withoutRaces()->unpublished()->create();

        $this->assertSame(0, $event->races()->count());
        $this->assertFalse($event->isPublished());
    }

    public function test_an_event_has_its_relations(): void
    {
        $organizer = Organizer::factory()->create();
        $series = EventSeries::factory()->create(['organizer_id' => $organizer->id]);
        $region = Region::factory()->create();
        $event = Event::factory()->create([
            'series_id' => $series->id,
            'organizer_id' => $organizer->id,
            'region_id' => $region->id,
        ]);
        $second = Race::factory()->for($event)->create(['sort_order' => 2]);
        $first = Race::factory()->for($event)->create(['sort_order' => 1]);
        RacePrice::factory()->for($second)->create();
        EventLink::factory()->for($event)->create(['sort_order' => 2, 'url' => 'https://example.com/b']);
        EventLink::factory()->for($event)->create(['sort_order' => 1, 'url' => 'https://example.com/a']);

        $event = $event->fresh();

        $this->assertTrue($event->series->is($series));
        $this->assertTrue($event->organizer->is($organizer));
        $this->assertTrue($event->region->is($region));
        $this->assertTrue($series->events->first()->is($event));
        $this->assertTrue($series->organizer->is($organizer));
        $this->assertSame([$event->mainRace->id, $first->id, $second->id], $event->races->pluck('id')->all());
        $this->assertSame(['https://example.com/a', 'https://example.com/b'], $event->links->pluck('url')->all());
        $this->assertSame(1, $event->prices()->count());
    }

    public function test_translated_text_follows_the_application_locale(): void
    {
        $event = Event::factory()->create([
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
        ]);

        $this->assertSame('Athens Marathon', $event->translated('name'));
        $this->assertSame('Μαραθώνιος Αθήνας', $event->translated('name', 'el'));

        $this->app->setLocale('el');

        $this->assertSame('Μαραθώνιος Αθήνας', $event->translated('name'));
    }

    public function test_translated_text_falls_back_when_a_locale_is_missing(): void
    {
        $event = Event::factory()->create([
            'name' => ['en' => 'Athens Marathon'],
            'venue' => null,
        ]);

        $this->app->setLocale('el');

        $this->assertSame('Athens Marathon', $event->translated('name'));
        $this->assertSame('', $event->translated('venue'));
        $this->assertNull($event->translatedOrNull('venue'));
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

        $this->assertSame(1, Event::query()->published()->count());
    }

    public function test_the_upcoming_scope_counts_the_last_day_in_greece(): void
    {
        $this->travelTo('2026-10-24 21:30:00');

        $today = Event::factory()->create(['slug' => 'today', 'starts_on' => '2026-10-25']);
        Event::factory()->create(['slug' => 'yesterday', 'starts_on' => '2026-10-24']);
        Event::factory()->create(['slug' => 'running', 'starts_on' => '2026-10-23', 'ends_on' => '2026-10-26']);
        Event::factory()->create(['slug' => 'over', 'starts_on' => '2026-10-22', 'ends_on' => '2026-10-24']);

        $this->assertEqualsCanonicalizing(['today', 'running'], Event::query()->upcoming()->pluck('slug')->all());
        $this->assertTrue($today->exists);
    }

    public function test_events_can_be_filtered_by_a_region_and_the_regions_inside_it(): void
    {
        $crete = Region::factory()->create();
        $chania = Region::factory()->within($crete)->create();
        $other = Region::factory()->create();

        $inCrete = Event::factory()->create(['region_id' => $crete->id]);
        $inChania = Event::factory()->create(['region_id' => $chania->id]);
        Event::factory()->create(['region_id' => $other->id]);

        $this->assertEqualsCanonicalizing(
            [$inCrete->id, $inChania->id],
            Event::query()->inRegion($crete)->pluck('id')->all(),
        );
        $this->assertSame([$inChania->id], Event::query()->inRegion($chania->id)->pluck('id')->all());
    }

    public function test_deleting_an_event_keeps_its_row_and_deleting_it_for_good_removes_its_races(): void
    {
        $event = Event::factory()->create();

        $event->delete();

        $this->assertSoftDeleted($event);
        $this->assertDatabaseCount('races', 1);

        $event->forceDelete();

        $this->assertDatabaseCount('races', 0);
    }
}
