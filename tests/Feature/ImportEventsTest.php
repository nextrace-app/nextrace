<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Organizer;
use App\Models\Race;
use App\Models\RacePrice;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportEventsTest extends TestCase
{
    use RefreshDatabase;

    private string $path = 'storage/framework/testing/events-import.json';

    protected function tearDown(): void
    {
        @unlink(base_path($this->path));

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function race(array $overrides = []): array
    {
        return array_merge([
            'slug' => '10k',
            'name' => ['en' => '10 km', 'el' => '10 χλμ.'],
            'is_main' => true,
            'distance_meters' => 10000,
            'starts_at' => now()->addMonth()->setTime(9, 0)->toDateTimeString(),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'chania-10k-2027',
            'name' => ['en' => 'Chania 10K', 'el' => 'Χανιά 10K'],
            'description' => ['en' => 'A 10K in Chania.'],
            'city' => 'Chania',
            'region' => 'chania',
            'source_url' => 'https://example.com/chania-10k',
            'races' => [$this->race()],
        ], $overrides);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function import(array $rows): void
    {
        @mkdir(dirname(base_path($this->path)), 0777, true);
        file_put_contents(base_path($this->path), json_encode($rows));

        $this->artisan('events:import', ['file' => $this->path])->assertSuccessful();
    }

    public function test_it_imports_a_nested_event_as_an_unpublished_draft(): void
    {
        $this->import([$this->row([
            'series' => ['slug' => 'chania-10k', 'name' => ['en' => 'Chania 10K', 'el' => 'Χανιά 10K'], 'organizer_slug' => 'club'],
            'organizer' => ['slug' => 'club', 'name' => ['en' => 'Club', 'el' => 'Σύλλογος'], 'email' => 'club@example.com', 'phone' => '6900000000', 'website' => 'https://club.example.com'],
            'edition_number' => 3,
            'venue' => ['en' => 'Harbour', 'el' => 'Λιμάνι'],
            'latitude' => 35.5,
            'longitude' => 24.0,
            'date_confirmed' => false,
            'registration' => ['status' => 'open', 'opens_at' => '2027-01-01', 'closes_at' => '2027-02-01', 'url' => 'https://example.com/register'],
            'contact_email' => 'info@example.com',
            'links' => [['type' => 'website', 'url' => 'https://example.com'], ['type' => 'regulations', 'url' => 'https://example.com/rules', 'label' => ['en' => 'Rules']]],
            'races' => [
                $this->race(['prices' => [['label' => ['en' => 'Early'], 'amount_cents' => 1000, 'valid_until' => '2027-01-31'], ['amount_cents' => 1500]]]),
                $this->race(['slug' => '5k', 'is_main' => false, 'distance_meters' => 5000, 'terrain' => 'road', 'min_age' => 12, 'time_limit_minutes' => 60]),
            ],
        ])]);

        $event = Event::query()->where('slug', 'chania-10k-2027')->firstOrFail();

        $this->assertFalse($event->isPublished());
        $this->assertSame('GR', $event->country);
        $this->assertSame('Χανιά 10K', $event->name['el']);
        $this->assertSame('chania', $event->region->slug);
        $this->assertSame('chania-10k', $event->series->slug);
        $this->assertSame('club', $event->organizer->slug);
        $this->assertSame('club', $event->series->organizer->slug);
        $this->assertSame(3, $event->edition_number);
        $this->assertFalse($event->date_confirmed);
        $this->assertSame('open', $event->registration_status->value);
        $this->assertSame('2027-02-01 00:00:00', $event->registration_closes_at->toDateTimeString());
        $this->assertSame(['website', 'regulations'], $event->links->map(fn ($link) => $link->type->value)->all());
        $this->assertSame($event->races->first()->starts_at->toDateString(), $event->starts_on->toDateString());

        $this->assertSame(['10k', '5k'], $event->races->pluck('slug')->all());
        $this->assertSame('10k', $event->mainRace->slug);
        $this->assertSame('10k', $event->mainRace->distance_class->value);
        $this->assertSame('5k', $event->races[1]->distance_class->value);
        $this->assertTrue($event->mainRace->start_time_known);
        $this->assertSame([1000, 1500], $event->mainRace->prices->pluck('amount_cents')->all());
        $this->assertSame('EUR', $event->mainRace->prices[0]->currency);
    }

    public function test_it_runs_idempotently(): void
    {
        $row = $this->row(['links' => [['type' => 'website', 'url' => 'https://example.com']], 'races' => [$this->race(['prices' => [['amount_cents' => 1000]]])]]);

        $this->import([$row]);
        $this->import([$row]);

        $this->assertSame(1, Event::query()->count());
        $this->assertSame(1, Race::query()->count());
        $this->assertSame(1, RacePrice::query()->count());
        $this->assertDatabaseCount('event_links', 1);
    }

    public function test_running_it_again_updates_what_changed_and_replaces_prices_and_links(): void
    {
        $this->import([$this->row(['links' => [['type' => 'website', 'url' => 'https://example.com/old']], 'races' => [$this->race(['prices' => [['amount_cents' => 1000]]])]])]);
        $this->import([$this->row([
            'links' => [['type' => 'facebook', 'url' => 'https://example.com/new']],
            'races' => [$this->race(['distance_meters' => 10500, 'prices' => [['amount_cents' => 1200], ['amount_cents' => 2000]]])],
        ])]);

        $event = Event::query()->firstOrFail();

        $this->assertSame(10500, $event->mainRace->distance_meters);
        $this->assertSame([1200, 2000], $event->mainRace->prices->pluck('amount_cents')->all());
        $this->assertSame(['https://example.com/new'], $event->links->pluck('url')->all());
    }

    public function test_races_missing_from_the_file_are_removed_and_the_main_race_follows_the_file(): void
    {
        $this->import([$this->row(['races' => [$this->race(), $this->race(['slug' => '5k', 'is_main' => false, 'distance_meters' => 5000])]])]);
        $this->import([$this->row(['races' => [$this->race(['slug' => 'half', 'distance_meters' => 21100])]])]);

        $event = Event::query()->firstOrFail();

        $this->assertSame(['half'], $event->races->pluck('slug')->all());
        $this->assertSame('half', $event->mainRace->slug);
        $this->assertSame(1, $event->races()->where('is_main', true)->count());
    }

    public function test_a_previous_slug_renames_the_existing_event_instead_of_duplicating_it(): void
    {
        $old = Event::factory()->unpublished()->create(['slug' => 'chania-10k']);
        $oldRaceId = $old->mainRace->id;

        $this->import([$this->row(['previous_slugs' => ['chania-10k']])]);

        $this->assertSame(1, Event::query()->count());
        $this->assertSame('chania-10k-2027', $old->fresh()->slug);
        $this->assertSame(1, Race::query()->count());
        $this->assertDatabaseMissing('races', ['id' => $oldRaceId]);
    }

    public function test_organizers_and_series_are_shared_by_slug(): void
    {
        $organizer = ['slug' => 'club', 'name' => ['en' => 'Club']];
        $series = ['slug' => 'run', 'name' => ['en' => 'Run']];

        $this->import([
            $this->row(['slug' => 'run-2027', 'organizer' => $organizer, 'series' => $series]),
            $this->row(['slug' => 'run-2028', 'organizer' => $organizer, 'series' => $series]),
        ]);

        $this->assertSame(1, Organizer::query()->count());
        $this->assertSame(1, EventSeries::query()->count());
        $this->assertSame(2, EventSeries::query()->firstOrFail()->events()->count());
    }

    public function test_new_events_are_drafts_and_published_true_publishes_them(): void
    {
        $this->import([
            $this->row(['slug' => 'draft']),
            $this->row(['slug' => 'live', 'published' => true]),
        ]);

        $this->assertFalse(Event::query()->where('slug', 'draft')->firstOrFail()->isPublished());
        $this->assertTrue(Event::query()->where('slug', 'live')->firstOrFail()->isPublished());
    }

    public function test_published_true_publishes_an_existing_draft(): void
    {
        Event::factory()->unpublished()->create(['slug' => 'chania-10k-2027']);

        $this->import([$this->row(['published' => true])]);

        $this->assertTrue(Event::query()->firstOrFail()->isPublished());
    }

    public function test_it_never_unpublishes_or_touches_the_publication_date_of_a_published_event(): void
    {
        $publishedAt = now()->subWeek()->startOfSecond();
        Event::factory()->create(['slug' => 'chania-10k-2027', 'published_at' => $publishedAt]);

        $this->import([$this->row(['published' => false])]);
        $this->assertSame($publishedAt->toDateTimeString(), Event::query()->firstOrFail()->published_at->toDateTimeString());

        $this->import([$this->row(['published' => true])]);
        $this->assertSame($publishedAt->toDateTimeString(), Event::query()->firstOrFail()->published_at->toDateTimeString());

        $this->import([$this->row()]);
        $this->assertTrue(Event::query()->firstOrFail()->isPublished());
    }

    public function test_it_skips_events_that_fail_validation(): void
    {
        $this->import([
            $this->row(['slug' => 'past', 'races' => [$this->race(['starts_at' => now()->subDay()->toDateTimeString()])]]),
            $this->row(['slug' => 'outside-greece', 'latitude' => 51.5, 'longitude' => -0.1]),
            $this->row(['slug' => 'no-source', 'source_url' => null]),
            $this->row(['slug' => 'bad-region', 'region' => 'atlantis']),
            $this->row(['slug' => 'no-main', 'races' => [$this->race(['is_main' => false])]]),
            $this->row(['slug' => 'two-mains', 'races' => [$this->race(), $this->race(['slug' => '5k'])]]),
            $this->row(['slug' => 'no-distance', 'races' => [$this->race(['distance_meters' => 0])]]),
            $this->row(['slug' => 'bad-url', 'races' => [$this->race(['registration_url' => 'nope'])]]),
            $this->row(['slug' => 'no-races', 'races' => []]),
            $this->row(['slug' => 'valid']),
        ]);

        $this->assertSame(['valid'], Event::query()->pluck('slug')->all());
    }

    public function test_an_existing_event_may_keep_a_date_that_has_passed(): void
    {
        Event::factory()->create(['slug' => 'chania-10k-2027']);

        $this->import([$this->row(['races' => [$this->race(['starts_at' => now()->subDay()->toDateTimeString()])]])]);

        $this->assertSame(now()->subDay()->toDateString(), Event::query()->firstOrFail()->starts_on->toDateString());
    }

    public function test_an_event_that_fails_halfway_is_not_written_at_all(): void
    {
        $this->import([$this->row([
            'slug' => 'broken',
            'series' => ['slug' => 'run', 'name' => ['en' => 'Run'], 'organizer_slug' => 'nobody'],
            'organizer' => ['slug' => 'club', 'name' => ['en' => 'Club']],
        ]), $this->row(['slug' => 'fine'])]);

        $this->assertSame(['fine'], Event::query()->pluck('slug')->all());
        $this->assertSame(0, Organizer::query()->count());
        $this->assertSame(0, EventSeries::query()->count());
        $this->assertDatabaseCount('races', 1);
    }

    public function test_it_fails_when_the_file_is_missing(): void
    {
        $this->artisan('events:import', ['file' => 'storage/framework/testing/nope.json'])->assertFailed();
    }

    public function test_the_bundled_file_imports_cleanly(): void
    {
        $this->travelTo('2026-10-24');

        $this->artisan('events:import')->assertSuccessful();

        $rows = json_decode((string) file_get_contents(base_path('database/data/events.json')), true);

        $this->assertSame(count($rows), Event::query()->count());
        $this->assertSame(
            collect($rows)->where('published', true)->count(),
            Event::query()->published()->count(),
        );
        $this->assertDatabaseHas('events', ['slug' => 'minoikos-athlos-2026']);
        $this->assertDatabaseHas('events', ['slug' => 'keri-cross-country-race-2026', 'published_at' => null, 'date_confirmed' => false]);

        foreach (Event::query()->get() as $event) {
            $this->assertSame(1, $event->races()->where('is_main', true)->count(), $event->slug);
        }

        $minoikos = Event::query()->where('slug', 'minoikos-athlos-2026')->firstOrFail();
        $this->assertSame(['70km', 'relay-3x23km', 'cycling-140km'], $minoikos->races->pluck('slug')->all());
        $this->assertSame(7000, $minoikos->mainRace->prices->first()->amount_cents);

        $this->artisan('events:import')->assertSuccessful();
        $this->assertSame(count($rows), Event::query()->count());
    }

    public function test_the_bundled_file_imports_events_from_across_greece_as_drafts(): void
    {
        $this->travelTo('2026-10-28');
        $this->seed(RegionSeeder::class);

        $this->artisan('events:import')->assertSuccessful();

        $athens = Event::query()->where('slug', 'athens-marathon-the-authentic-2026')->firstOrFail();
        $this->assertNull($athens->published_at);
        $this->assertSame('attiki', $athens->region->slug);
        $this->assertSame(42195, $athens->mainRace->distance_meters);
        $this->assertTrue($athens->date_confirmed);

        $this->assertSame('south-aegean', Event::query()->where('slug', 'tui-rhodes-marathon-2027')->firstOrFail()->region->slug);
        $this->assertSame('epirus', Event::query()->where('slug', 'all-terrain-ultra-race-ioannina-2027')->firstOrFail()->region->slug);
    }

    public function test_the_bundled_file_carries_the_drafts_of_the_previous_format_over(): void
    {
        $this->travelTo('2026-10-24');
        $old = Event::factory()->create(['slug' => 'crete-marathon', 'published_at' => null]);

        $this->artisan('events:import')->assertSuccessful();

        $this->assertSame('crete-marathon-2027', $old->fresh()->slug);
        $this->assertSame(1, Event::query()->where('slug', 'like', 'crete-marathon%')->count());
        $this->assertTrue($old->fresh()->isPublished());
    }
}
