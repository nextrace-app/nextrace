<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Race;
use App\Models\Region;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_every_event_of_the_constants_under_its_own_id(): void
    {
        $this->seed(EventSeeder::class);

        $this->assertSame(count(EventSeeder::catalogue()), Event::query()->count());

        foreach (EventSeeder::catalogue() as $event) {
            $this->assertDatabaseHas('events', [
                'id' => $event['id'],
                'participants' => $event['participants'],
                'image_url' => $event['image'],
            ]);
            $this->assertSame($event['category'], Event::query()->findOrFail($event['id'])->mainRace->name['en']);
        }
    }

    public function test_it_translates_the_columns_the_constants_stored_differently(): void
    {
        $this->seed(EventSeeder::class);

        $athens = Event::query()->findOrFail(1);

        $this->assertSame('athens-marathon', $athens->slug);
        $this->assertSame(['el' => 'Athens Marathon', 'en' => 'Athens Marathon'], $athens->name);
        $this->assertSame(42200, $athens->mainRace->distance_meters);
        $this->assertSame('2026-11-08', $athens->starts_on->toDateString());
        $this->assertSame('2026-11-08 00:00:00', $athens->mainRace->starts_at->toDateTimeString());
        $this->assertFalse($athens->mainRace->start_time_known);
        $this->assertSame('attiki', $athens->region->slug);
        $this->assertSame('Athens', $athens->city);
        $this->assertSame('GR', $athens->country);
        $this->assertNotNull($athens->published_at);

        $spartathlon = Event::query()->findOrFail(5);

        $this->assertSame(246000, $spartathlon->mainRace->distance_meters);
        $this->assertSame('Athens to Sparta', $spartathlon->city);
    }

    public function test_it_can_be_run_again_without_duplicating_events(): void
    {
        $this->seed(EventSeeder::class);
        $this->seed(EventSeeder::class);

        $this->assertSame(count(EventSeeder::catalogue()), Event::query()->count());
        $this->assertSame(count(EventSeeder::catalogue()), Race::query()->count());
    }

    public function test_every_seeded_event_has_exactly_one_main_race(): void
    {
        $this->seed(EventSeeder::class);

        foreach (Event::query()->withCount(['races as main_races_count' => fn ($query) => $query->where('is_main', true)])->get() as $event) {
            $this->assertSame(1, $event->main_races_count);
        }
    }

    public function test_the_database_seeder_seeds_the_regions_too(): void
    {
        $this->seed();

        $this->assertDatabaseHas('regions', ['slug' => 'heraklion']);
        $this->assertSame('crete', Region::query()->where('slug', 'heraklion')->firstOrFail()->parent->slug);
        $this->assertSame('greece', Region::query()->where('slug', 'crete')->firstOrFail()->parent->slug);
        $this->assertSame(count(EventSeeder::catalogue()), Event::query()->count());
    }

    public function test_an_event_created_afterwards_gets_a_free_id(): void
    {
        $this->seed(EventSeeder::class);

        $event = Event::factory()->create();

        $this->assertSame(count(EventSeeder::catalogue()) + 1, $event->id);
    }
}
