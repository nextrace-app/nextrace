<?php

namespace Tests\Feature;

use App\Enums\Certification;
use App\Enums\DistanceClass;
use App\Enums\RaceDiscipline;
use App\Enums\RaceStatus;
use App\Enums\RaceTerrain;
use App\Models\Event;
use App\Models\Race;
use App\Models\RacePrice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_race_of_an_event_becomes_its_main_race(): void
    {
        $event = Event::factory()->withoutRaces()->create();

        $race = Race::factory()->for($event)->create(['is_main' => false]);

        $this->assertTrue($race->fresh()->is_main);
    }

    public function test_a_later_race_is_not_main_by_default(): void
    {
        $event = Event::factory()->create();

        $race = Race::factory()->for($event)->create();

        $this->assertFalse($race->fresh()->is_main);
        $this->assertSame(1, $event->races()->where('is_main', true)->count());
    }

    public function test_marking_a_race_as_main_demotes_the_previous_main_race(): void
    {
        $event = Event::factory()->create();
        $previous = $event->mainRace;

        $new = Race::factory()->for($event)->main()->create();

        $this->assertTrue($new->fresh()->is_main);
        $this->assertFalse($previous->fresh()->is_main);

        $previous->fresh()->update(['is_main' => true]);

        $this->assertSame([$previous->id], $event->races()->where('is_main', true)->pluck('id')->all());
    }

    public function test_deleting_the_main_race_promotes_the_next_one(): void
    {
        $event = Event::factory()->create();
        $second = Race::factory()->for($event)->create(['sort_order' => 2]);
        $first = Race::factory()->for($event)->create(['sort_order' => 1]);

        $event->mainRace->delete();

        $this->assertTrue($first->fresh()->is_main);
        $this->assertFalse($second->fresh()->is_main);
    }

    public function test_races_of_different_events_can_share_a_slug_but_not_within_an_event(): void
    {
        Race::factory()->for(Event::factory()->withoutRaces()->create())->create(['slug' => '10k']);
        $event = Event::factory()->withoutRaces()->create();
        Race::factory()->for($event)->create(['slug' => '10k']);

        $this->expectException(QueryException::class);

        Race::factory()->for($event)->create(['slug' => '10k']);
    }

    public function test_a_race_has_defaults_and_casts_its_enums(): void
    {
        $race = Race::factory()->create([
            'discipline' => 'relay',
            'terrain' => 'cross_country',
            'distance_class' => 'half_marathon',
            'certification' => 'aims',
            'status' => 'postponed',
        ])->fresh();

        $this->assertSame(RaceDiscipline::Relay, $race->discipline);
        $this->assertSame(RaceTerrain::CrossCountry, $race->terrain);
        $this->assertSame(DistanceClass::HalfMarathon, $race->distance_class);
        $this->assertSame(Certification::Aims, $race->certification);
        $this->assertSame(RaceStatus::Postponed, $race->status);

        $plain = Race::query()->create([
            'event_id' => Event::factory()->create()->id,
            'slug' => 'plain',
            'name' => ['en' => 'Plain'],
            'distance_meters' => 1000,
            'starts_at' => '2027-01-01 10:00:00',
        ])->fresh();

        $this->assertSame(RaceDiscipline::Run, $plain->discipline);
        $this->assertSame(RaceStatus::Scheduled, $plain->status);
        $this->assertTrue($plain->is_competitive);
        $this->assertTrue($plain->start_time_known);
        $this->assertSame(0, $plain->sort_order);
    }

    public function test_a_race_formats_its_distance_and_category(): void
    {
        $this->assertSame('42.2 km', Race::factory()->make(['distance_meters' => 42200])->formattedDistance());
        $this->assertSame('5 km', Race::factory()->make(['distance_meters' => 5000])->formattedDistance());
        $this->assertSame('Marathon', Race::factory()->make(['distance_class' => DistanceClass::Marathon, 'terrain' => RaceTerrain::Road])->category());
        $this->assertSame('Trail', Race::factory()->make(['distance_class' => DistanceClass::HalfMarathon, 'terrain' => RaceTerrain::Trail])->category());
        $this->assertSame('Ultra', Race::factory()->make(['distance_class' => DistanceClass::Ultra, 'terrain' => RaceTerrain::Trail])->category());
    }

    public function test_prices_are_replaced_with_the_race_and_deleted_along_with_it(): void
    {
        $race = Race::factory()->create();
        RacePrice::factory()->for($race)->create(['amount_cents' => 1500, 'valid_until' => '2027-01-01']);

        $price = $race->prices()->first();

        $this->assertSame(1500, $price->amount_cents);
        $this->assertSame('EUR', $price->currency);
        $this->assertSame('2027-01-01', $price->valid_until->toDateString());

        $race->delete();

        $this->assertDatabaseCount('race_prices', 0);
    }
}
