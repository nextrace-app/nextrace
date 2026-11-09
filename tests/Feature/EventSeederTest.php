<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Models\Event;
use App\Models\EventSeries;
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

        // The catalogue, plus the 2025 edition of Athens Marathon.
        $this->assertSame(count(EventSeeder::catalogue()) + 1, Event::query()->count());

        foreach (EventSeeder::catalogue() as $event) {
            $this->assertDatabaseHas('events', [
                'id' => $event['id'],
                'participants' => $event['participants'],
                'image_url' => $event['image'],
            ]);
            $this->assertSame($event['id'] === 1 ? 'Marathon' : $event['category'], Event::query()->findOrFail($event['id'])->mainRace->name['en']);
        }
    }

    public function test_it_translates_the_columns_the_constants_stored_differently(): void
    {
        $this->seed(EventSeeder::class);

        $athens = Event::query()->findOrFail(1);

        $this->assertSame('athens-marathon', $athens->slug);
        $this->assertSame(['el' => 'Athens Marathon', 'en' => 'Athens Marathon'], $athens->name);
        $this->assertSame('attiki', $athens->region->slug);
        $this->assertSame('Athens', $athens->city);
        $this->assertSame('GR', $athens->country);
        $this->assertNotNull($athens->published_at);

        $spartathlon = Event::query()->findOrFail(5);

        $this->assertSame(246000, $spartathlon->mainRace->distance_meters);
        $this->assertSame('Athens to Sparta', $spartathlon->city);
    }

    public function test_athens_marathon_is_a_weekend_of_six_races(): void
    {
        $this->seed(EventSeeder::class);

        $athens = Event::query()->findOrFail(1);

        $this->assertSame('2026-11-07', $athens->starts_on->toDateString());
        $this->assertSame('2026-11-08', $athens->ends_on->toDateString());
        $this->assertSame(
            ['marathon', '10k', '5k', 'night-run-5k', 'kids-race', 'special-olympics'],
            $athens->races->pluck('slug')->all(),
        );
        $this->assertSame(1, $athens->races->where('is_main', true)->count());
        $this->assertSame(42195, $athens->mainRace->distance_meters);
        $this->assertSame('2026-11-08 08:30', $athens->mainRace->starts_at->format('Y-m-d H:i'));
        $this->assertTrue($athens->mainRace->start_time_known);
        $this->assertSame(20, $athens->mainRace->min_age);
        $this->assertSame(480, $athens->mainRace->time_limit_minutes);
        $this->assertSame(15, $athens->mainRace->aid_stations);
        $this->assertFalse($athens->mainRace->medical_certificate_required);
        $this->assertTrue($athens->mainRace->sold_out);
        $this->assertSame([7500, 16500], $athens->mainRace->prices->pluck('amount_cents')->all());
        $this->assertSame([0], $athens->races->firstWhere('slug', 'kids-race')->prices->pluck('amount_cents')->all());
        $this->assertSame(4, $athens->races->filter(fn (Race $race) => $race->starts_at->toDateString() === '2026-11-07')->count());
        $this->assertSame(
            ['registration_opens', 'changes_deadline', 'bib_pickup', 'other', 'other'],
            $athens->dates->map(fn ($date) => $date->type->value)->all(),
        );
        $this->assertSame('2026-11-07', $athens->dates->firstWhere('type.value', 'bib_pickup')->ends_on->toDateString());
        $this->assertSame(
            ['transport', 'bag_drop', 'expo', 'road_closures', 'medical'],
            $athens->sections->map(fn ($section) => $section->type->value)->all(),
        );
        $this->assertCount(17, $athens->mainRace->checkpoints);
        $this->assertSame('2026-11-08 14:14', $athens->mainRace->checkpoints->firstWhere('distance_meters', 21097)->closes_at->format('Y-m-d H:i'));
        $this->assertSame('2026-11-08 18:15', $athens->mainRace->checkpoints->last()->closes_at->format('Y-m-d H:i'));
    }

    public function test_it_can_be_run_again_without_duplicating_events(): void
    {
        $this->seed(EventSeeder::class);
        $this->seed(EventSeeder::class);

        $this->assertSame(count(EventSeeder::catalogue()) + 1, Event::query()->count());
        // Every event has one race, except Athens Marathon with its weekend of six.
        $this->assertSame(count(EventSeeder::catalogue()) + 6, Race::query()->count());
        $this->assertSame(1, EventSeries::query()->count());
        $this->assertSame(4, Event::query()->findOrFail(1)->links()->count() + Event::query()->where('slug', 'athens-marathon-2025')->sole()->links()->count());
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
        $this->assertSame(count(EventSeeder::catalogue()) + 1, Event::query()->count());
    }

    public function test_an_event_created_afterwards_gets_a_free_id(): void
    {
        $this->seed(EventSeeder::class);
        $seededIds = Event::query()->pluck('id');

        $event = Event::factory()->create();

        $this->assertFalse($seededIds->contains($event->id));
        $this->assertSame(count(EventSeeder::catalogue()) + 2, Event::query()->count());
    }

    public function test_athens_marathon_is_a_series_with_its_2025_edition(): void
    {
        $this->seed(EventSeeder::class);

        $athens = Event::query()->findOrFail(1);
        $previous = Event::query()->where('slug', 'athens-marathon-2025')->sole();

        $this->assertSame('athens-marathon', $athens->series->slug);
        $this->assertSame(43, $athens->edition_number);
        $this->assertSame($athens->series_id, $previous->series_id);
        $this->assertSame(42, $previous->edition_number);
        $this->assertSame('2025-11-09', $previous->starts_on->toDateString());
        $this->assertNotNull($previous->published_at);
        $this->assertSame(42195, $previous->mainRace->distance_meters);
        $this->assertSame([LinkType::Results, LinkType::Photos], $athens->links->whereIn('type', [LinkType::Results, LinkType::Photos])->pluck('type')->all());
        $this->assertSame([LinkType::Results, LinkType::Photos], $previous->links->pluck('type')->all());
    }
}
