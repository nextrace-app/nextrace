<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Event;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStoreEventsTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/events';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        [, $plain] = ApiToken::issue('scout', [ApiToken::EVENTS_WRITE]);

        $this->withToken($plain);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function race(array $overrides = []): array
    {
        return [
            'slug' => '10k',
            'name' => ['en' => '10 km', 'el' => '10 χλμ.'],
            'is_main' => true,
            'distance_meters' => 10000,
            'starts_at' => now()->addMonth()->setTime(9, 0)->toDateTimeString(),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'slug' => 'chania-10k-2027',
            'name' => ['en' => 'Chania 10K', 'el' => 'Χανιά 10K'],
            'description' => ['en' => 'A 10K in Chania.'],
            'city' => 'Chania',
            'region' => 'chania',
            'source_url' => 'https://example.com/chania-10k',
            'races' => [$this->race()],
            ...$overrides,
        ];
    }

    public function test_it_needs_a_token_with_the_events_write_ability(): void
    {
        $this->withToken('nr_unknown')->postJson(self::URL, $this->payload())->assertUnauthorized();

        [, $readOnly] = ApiToken::issue('reader', [ApiToken::EVENTS_READ, ApiToken::POSTS_WRITE]);
        $this->withToken($readOnly)->postJson(self::URL, $this->payload())->assertForbidden();

        $this->assertDatabaseCount('events', 0);
    }

    public function test_it_creates_a_draft_with_several_races_and_their_prices(): void
    {
        $this->postJson(self::URL, $this->payload([
            'links' => [['type' => 'results', 'url' => 'https://example.com/results']],
            'dates' => [['type' => 'registration_opens', 'starts_on' => '2027-01-10']],
            'races' => [
                $this->race(['prices' => [['amount_cents' => 1500, 'valid_until' => '2027-01-31'], ['amount_cents' => 2000]]]),
                $this->race(['slug' => '5k', 'is_main' => false, 'distance_meters' => 5000, 'prices' => [['amount_cents' => 1000]]]),
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'chania-10k-2027')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.admin_url', route('admin.events.edit', 'chania-10k-2027'));

        $event = Event::query()->sole();

        $this->assertFalse($event->isPublished());
        $this->assertSame('chania', $event->region->slug);
        $this->assertSame(['10k', '5k'], $event->races->pluck('slug')->all());
        $this->assertSame([1500, 2000], $event->mainRace->prices->pluck('amount_cents')->all());
        $this->assertSame([1000], $event->races[1]->prices->pluck('amount_cents')->all());
        $this->assertSame(['results'], $event->links->map(fn ($link) => $link->type->value)->all());
        $this->assertSame(1, $event->dates()->count());
    }

    public function test_sending_it_again_updates_the_draft(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->postJson(self::URL, $this->payload([
            'city' => 'Rethymno',
            'races' => [$this->race(['distance_meters' => 10500])],
        ]))->assertOk()->assertJsonPath('data.status', 'draft');

        $event = Event::query()->sole();

        $this->assertSame('Rethymno', $event->city);
        $this->assertSame(10500, $event->mainRace->distance_meters);
        $this->assertFalse($event->isPublished());
    }

    public function test_an_event_in_review_stays_in_review_when_updated(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();
        Event::query()->sole()->update(['submitted_at' => now()]);

        $this->postJson(self::URL, $this->payload(['city' => 'Rethymno']))
            ->assertOk()
            ->assertJsonPath('data.status', 'in_review');
    }

    public function test_publishing_is_refused(): void
    {
        foreach ([true, false] as $published) {
            $this->postJson(self::URL, $this->payload(['published' => $published]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('published');
        }

        $this->assertDatabaseCount('events', 0);
    }

    public function test_a_published_event_is_left_for_the_admin(): void
    {
        $event = Event::factory()->create(['slug' => 'chania-10k-2027', 'city' => 'Chania', 'published_at' => now()]);

        $this->postJson(self::URL, $this->payload(['city' => 'Rethymno']))
            ->assertConflict()
            ->assertJsonPath('admin_url', route('admin.events.edit', $event));

        // Also when it is found by a previous slug.
        $this->postJson(self::URL, $this->payload(['slug' => 'new-slug', 'previous_slugs' => ['chania-10k-2027']]))->assertConflict();

        $this->assertSame('Chania', $event->fresh()->city);
        $this->assertSame('chania-10k-2027', $event->fresh()->slug);
    }

    public function test_the_required_fields_are_checked(): void
    {
        $this->postJson(self::URL, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'name', 'description', 'city', 'source_url', 'races']);
    }

    public function test_bad_races_are_refused(): void
    {
        foreach ([
            [$this->race(['is_main' => false])],
            [$this->race(), $this->race(['is_main' => false])],
            [$this->race(['starts_at' => now()->subDay()->toDateTimeString()])],
            [$this->race(['prices' => [['amount_cents' => -1]]])],
            [$this->race(['distance_class' => 'sprint'])],
        ] as $races) {
            $this->postJson(self::URL, $this->payload(['races' => $races]))->assertUnprocessable();
        }

        $this->postJson(self::URL, $this->payload(['region' => 'atlantis']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('region');

        $this->assertDatabaseCount('events', 0);
    }
}
