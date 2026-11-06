<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventsTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/events';

    protected function setUp(): void
    {
        parent::setUp();

        [, $plain] = ApiToken::issue('test', [ApiToken::EVENTS_READ]);

        $this->withToken($plain);
    }

    public function test_it_lists_published_events_including_past_ones_in_order(): void
    {
        $later = Event::factory()->startingAt('2027-05-10 09:00')->create();
        $past = Event::factory()->startingAt('2025-03-01 09:00')->create();
        $sooner = Event::factory()->startingAt('2026-12-01 09:00')->create();
        Event::factory()->unpublished()->create();

        $response = $this->getJson(self::URL)->assertOk();

        $this->assertSame([$past->id, $sooner->id, $later->id], collect($response->json('data'))->pluck('id')->all());
        $response->assertJsonStructure(['data' => [['id', 'slug', 'name', 'date', 'races']], 'links', 'meta' => ['current_page', 'last_page', 'total', 'per_page']]);
    }

    public function test_text_is_greek_by_default_and_english_on_request(): void
    {
        Event::factory()->create(['name' => ['el' => 'Αγώνας', 'en' => 'The Race']]);

        $this->getJson(self::URL)->assertJsonPath('data.0.name', 'Αγώνας');
        $this->getJson(self::URL.'?locale=en')->assertJsonPath('data.0.name', 'The Race');
    }

    public function test_from_and_to_limit_the_dates(): void
    {
        $early = Event::factory()->startingAt('2026-01-10 09:00')->create();
        $middle = Event::factory()->startingAt('2026-06-10 09:00')->create();
        $late = Event::factory()->startingAt('2026-11-10 09:00')->create();

        $this->assertSame([$middle->id, $late->id], $this->ids('?from=2026-06-10'));
        $this->assertSame([$early->id, $middle->id], $this->ids('?to=2026-06-10'));
        $this->assertSame([$middle->id], $this->ids('?from=2026-06-01&to=2026-06-30'));
    }

    public function test_a_multi_day_event_still_counts_on_its_last_day(): void
    {
        $event = Event::factory()->startingAt('2026-06-10 09:00')->create(['ends_on' => '2026-06-12']);

        $this->assertSame([$event->id], $this->ids('?from=2026-06-12'));
        $this->assertSame([], $this->ids('?from=2026-06-13'));
    }

    public function test_it_filters_by_distance(): void
    {
        $marathon = Event::factory()->withMainRace(['distance_class' => 'marathon', 'distance_meters' => 42195])->create();
        Event::factory()->withMainRace(['distance_class' => '5k', 'distance_meters' => 5000])->create();

        $this->assertSame([$marathon->id], $this->ids('?distance=marathon'));
    }

    public function test_it_searches_by_name_and_city(): void
    {
        $byName = Event::factory()->create(['name' => ['el' => 'Δρόμος', 'en' => 'Sunrise Run'], 'city' => 'Patras']);
        $byCity = Event::factory()->create(['name' => ['el' => 'Άλλος', 'en' => 'Other'], 'city' => 'Sunrisetown']);
        Event::factory()->create(['name' => ['el' => 'Τίποτα', 'en' => 'Nothing'], 'city' => 'Chania']);

        $this->assertEqualsCanonicalizing([$byName->id, $byCity->id], $this->ids('?q=sunrise&locale=en'));
    }

    public function test_it_paginates(): void
    {
        Event::factory()->count(5)->create();

        $first = $this->getJson(self::URL.'?per_page=2')->assertOk();

        $this->assertCount(2, $first->json('data'));
        $this->assertSame(3, $first->json('meta.last_page'));
        $this->assertSame(5, $first->json('meta.total'));
        $this->assertCount(1, $this->getJson(self::URL.'?per_page=2&page=3')->json('data'));
    }

    public function test_bad_parameters_are_rejected(): void
    {
        foreach (['locale=fr', 'from=yesterday', 'to=2026-13-40', 'from=2026-06-10&to=2026-06-01', 'distance=sprint', 'per_page=101', 'per_page=0', 'page=0'] as $query) {
            $this->getJson(self::URL.'?'.$query)->assertUnprocessable();
        }
    }

    public function test_the_rate_limit_is_applied(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson(self::URL)->assertOk();
        }

        $this->getJson(self::URL)->assertStatus(429);
    }

    /**
     * @return list<int>
     */
    private function ids(string $query): array
    {
        return collect($this->getJson(self::URL.$query)->assertOk()->json('data'))->pluck('id')->all();
    }
}
