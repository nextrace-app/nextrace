<?php

namespace Tests\Feature;

use App\Models\Event;
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
    private function row(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'chania-10k',
            'name' => ['en' => 'Chania 10K', 'el' => 'Χανιά 10K'],
            'description' => ['en' => 'A 10K in Chania.'],
            'category' => '10K',
            'distance_meters' => 10000,
            'starts_at' => now()->addMonth()->toDateTimeString(),
            'city' => 'Chania',
            'source_url' => 'https://example.com/chania-10k',
            'registration_url' => null,
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

    public function test_it_imports_new_events_as_unpublished_drafts(): void
    {
        $this->import([$this->row()]);

        $event = Event::query()->where('slug', 'chania-10k')->firstOrFail();

        $this->assertFalse($event->isPublished());
        $this->assertSame('GR', $event->country);
        $this->assertSame('Χανιά 10K', $event->name['el']);
        $this->assertSame(10000, $event->distance_meters);
    }

    public function test_running_it_twice_updates_instead_of_duplicating(): void
    {
        $this->import([$this->row()]);
        $this->import([$this->row(['distance_meters' => 10500])]);

        $this->assertSame(1, Event::query()->count());
        $this->assertSame(10500, Event::query()->firstOrFail()->distance_meters);
    }

    public function test_it_keeps_an_event_published_after_a_reimport(): void
    {
        Event::factory()->create(['slug' => 'chania-10k', 'published_at' => now()]);

        $this->import([$this->row()]);

        $this->assertTrue(Event::query()->where('slug', 'chania-10k')->firstOrFail()->isPublished());
    }

    public function test_it_skips_rows_that_fail_validation(): void
    {
        $this->import([
            $this->row(['slug' => 'past', 'starts_at' => now()->subDay()->toDateTimeString()]),
            $this->row(['slug' => 'outside-greece', 'latitude' => 51.5, 'longitude' => -0.1]),
            $this->row(['slug' => 'no-source', 'source_url' => null]),
            $this->row(['slug' => 'valid']),
        ]);

        $this->assertSame(['valid'], Event::query()->pluck('slug')->all());
    }

    public function test_it_fails_when_the_file_is_missing(): void
    {
        $this->artisan('events:import', ['file' => 'storage/framework/testing/nope.json'])->assertFailed();
    }

    public function test_the_bundled_crete_file_imports_cleanly(): void
    {
        $this->travelTo('2026-10-24');

        $this->artisan('events:import')->assertSuccessful();

        $this->assertDatabaseHas('events', ['slug' => 'minoikos-athlos', 'published_at' => null]);
    }
}
