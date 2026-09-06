<?php

namespace Tests\Feature;

use App\Models\Event;
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
                'category' => $event['category'],
                'participants' => $event['participants'],
                'image_url' => $event['image'],
            ]);
        }
    }

    public function test_it_translates_the_columns_the_constants_stored_differently(): void
    {
        $this->seed(EventSeeder::class);

        $athens = Event::query()->findOrFail(1);

        $this->assertSame('athens-marathon', $athens->slug);
        $this->assertSame(['el' => 'Athens Marathon', 'en' => 'Athens Marathon'], $athens->name);
        $this->assertSame(42200, $athens->distance_meters);
        $this->assertSame('2026-11-08 00:00:00', $athens->starts_at->toDateTimeString());
        $this->assertSame('Athens', $athens->city);
        $this->assertSame('GR', $athens->country);
        $this->assertNotNull($athens->published_at);

        $spartathlon = Event::query()->findOrFail(5);

        $this->assertSame(246000, $spartathlon->distance_meters);
        $this->assertSame('Athens to Sparta', $spartathlon->city);
    }

    public function test_it_can_be_run_again_without_duplicating_events(): void
    {
        $this->seed(EventSeeder::class);
        $this->seed(EventSeeder::class);

        $this->assertSame(count(EventSeeder::catalogue()), Event::query()->count());
    }

    public function test_an_event_created_afterwards_gets_a_free_id(): void
    {
        $this->seed(EventSeeder::class);

        $event = Event::factory()->create();

        $this->assertSame(count(EventSeeder::catalogue()) + 1, $event->id);
    }
}
