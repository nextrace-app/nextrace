<?php

namespace Tests\Feature;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Race;
use App\Models\RacePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RaceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-06-10 12:00:00');
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  list<array<string, mixed>>  $prices
     * @param  array<string, mixed>  $race
     * @return array<string, mixed>
     */
    private function resolve(array $event = [], array $prices = [], array $race = []): array
    {
        $model = Event::factory()->withMainRace(['starts_at' => '2026-11-08 08:30:00', ...$race])->create(['registration_status' => 'unknown', ...$event]);

        foreach ($prices as $price) {
            RacePrice::factory()->for($model->races->first())->create($price);
        }

        return (new EventResource($model->fresh()))->toArray(Request::create('/'));
    }

    public function test_a_race_with_a_tier_valid_today_is_open_and_marks_the_tiers(): void
    {
        $data = $this->resolve(prices: [
            ['amount_cents' => 7500, 'valid_from' => null, 'valid_until' => '2026-05-31'],
            ['amount_cents' => 16500, 'valid_from' => '2026-06-01', 'valid_until' => '2026-09-25'],
        ]);

        $race = $data['races'][0];

        $this->assertSame('open', $race['registration_state']);
        $this->assertSame(['past', 'current'], array_column($race['prices'], 'state'));
        $this->assertNull($race['price_rises_after']);
        $this->assertSame(['state' => 'open', 'price_rises_soon' => false], $data['registration_state']);
    }

    public function test_the_price_rises_soon_when_a_dearer_tier_starts_within_a_week(): void
    {
        $data = $this->resolve(prices: [
            ['amount_cents' => 4000, 'valid_from' => null, 'valid_until' => '2026-06-15'],
            ['amount_cents' => 6000, 'valid_from' => '2026-06-16', 'valid_until' => null],
        ]);

        $this->assertSame('2026-06-15', $data['races'][0]['price_rises_after']);
        $this->assertTrue($data['registration_state']['price_rises_soon']);
    }

    public function test_a_cheaper_or_far_away_next_tier_is_not_a_rise(): void
    {
        $far = $this->resolve(prices: [
            ['amount_cents' => 4000, 'valid_until' => '2026-07-31'],
            ['amount_cents' => 6000, 'valid_from' => '2026-08-01'],
        ]);
        $cheaper = $this->resolve(prices: [
            ['amount_cents' => 4000, 'valid_until' => '2026-06-12'],
            ['amount_cents' => 3000, 'valid_from' => '2026-06-13'],
        ]);

        $this->assertNull($far['races'][0]['price_rises_after']);
        $this->assertNull($cheaper['races'][0]['price_rises_after']);
    }

    public function test_a_free_tier_makes_the_race_free(): void
    {
        $this->assertSame('free', $this->resolve(prices: [['amount_cents' => 0]])['races'][0]['registration_state']);
    }

    public function test_a_race_after_its_last_tier_is_closed_and_before_its_first_not_open(): void
    {
        $over = $this->resolve(prices: [['amount_cents' => 4000, 'valid_until' => '2026-06-01']]);
        $ahead = $this->resolve(prices: [['amount_cents' => 4000, 'valid_from' => '2026-07-01']]);

        $this->assertSame('closed', $over['races'][0]['registration_state']);
        $this->assertSame(['past'], array_column($over['races'][0]['prices'], 'state'));
        $this->assertSame('not_open', $ahead['races'][0]['registration_state']);
    }

    public function test_sold_out_and_the_event_fields_win_over_the_tiers(): void
    {
        $tier = [['amount_cents' => 4000, 'valid_until' => '2026-12-31']];

        $this->assertSame('sold_out', $this->resolve(prices: $tier, race: ['sold_out' => true])['races'][0]['registration_state']);
        $this->assertSame('closed', $this->resolve(['registration_closes_at' => '2026-06-01 00:00:00'], $tier)['races'][0]['registration_state']);
        $this->assertSame('not_open', $this->resolve(['registration_opens_at' => '2026-07-01 00:00:00'], $tier)['races'][0]['registration_state']);
        $this->assertSame('closed', $this->resolve(['registration_status' => 'closed'], $tier)['races'][0]['registration_state']);
    }

    public function test_without_tiers_the_event_status_decides(): void
    {
        $this->assertSame('open', $this->resolve(['registration_status' => 'open'])['races'][0]['registration_state']);
        $this->assertSame('unknown', $this->resolve()['races'][0]['registration_state']);
    }

    public function test_a_closed_race_shows_no_current_tier(): void
    {
        $data = $this->resolve(['registration_status' => 'closed'], [['amount_cents' => 0]]);

        $this->assertSame([null], array_column($data['races'][0]['prices'], 'state'));
    }

    public function test_the_event_shows_the_most_useful_state_of_its_races(): void
    {
        $event = Event::factory()->withMainRace(['starts_at' => '2026-11-08 08:30:00', 'sold_out' => true])->create(['registration_status' => 'open']);
        Race::factory()->for($event)->create(['starts_at' => '2026-11-07 17:00:00']);

        $data = (new EventResource($event->fresh()))->toArray(Request::create('/'));

        $this->assertSame(['sold_out', 'open'], array_column($data['races'], 'registration_state'));
        $this->assertSame('open', $data['registration_state']['state']);
    }

    public function test_the_list_can_show_only_the_events_open_for_registration(): void
    {
        config(['events.per_page' => 12]);

        $tiered = Event::factory()->withMainRace(['starts_at' => '2026-11-01 09:00:00'])->create(['starts_on' => '2026-11-01']);
        RacePrice::factory()->for($tiered->races->first())->create(['valid_from' => '2026-06-01', 'valid_until' => '2026-10-01']);

        $marked = Event::factory()->withMainRace(['starts_at' => '2026-11-02 09:00:00'])->create(['starts_on' => '2026-11-02', 'registration_status' => 'open']);

        $over = Event::factory()->withMainRace(['starts_at' => '2026-11-03 09:00:00'])->create(['starts_on' => '2026-11-03']);
        RacePrice::factory()->for($over->races->first())->create(['valid_until' => '2026-06-01']);

        Event::factory()->withMainRace(['starts_at' => '2026-11-04 09:00:00', 'sold_out' => true])->create(['starts_on' => '2026-11-04', 'registration_status' => 'open']);
        Event::factory()->withMainRace(['starts_at' => '2026-11-05 09:00:00'])->create(['starts_on' => '2026-11-05', 'registration_status' => 'open', 'registration_closes_at' => '2026-06-01 00:00:00']);
        Event::factory()->withMainRace(['starts_at' => '2026-11-06 09:00:00'])->create(['starts_on' => '2026-11-06', 'registration_status' => 'unknown']);

        $this->get(route('events.index', ['open' => 1]))->assertInertia(
            fn (Assert $page) => $page
                ->where('filters.open', true)
                ->count('events', 2)
                ->where('events.0.id', $tiered->id)
                ->where('events.1.id', $marked->id)
        );

        $this->get(route('events.index'))->assertInertia(
            fn (Assert $page) => $page->where('filters.open', false)->count('events', 6)
        );
    }
}
