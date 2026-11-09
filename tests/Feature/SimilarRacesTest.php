<?php

namespace Tests\Feature;

use App\Enums\DistanceClass;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Race;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimilarRacesTest extends TestCase
{
    use RefreshDatabase;

    private Region $region;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-01 10:00:00');
        $this->region = Region::factory()->create();
    }

    /**
     * A published event with one race of each of the given classes.
     *
     * @param  list<DistanceClass>  $classes
     * @param  array<string, mixed>  $attributes
     */
    private function event(string $slug, string $startsOn, array $classes = [DistanceClass::TenK], array $attributes = []): Event
    {
        $event = Event::factory()->withoutRaces()->create([
            'slug' => $slug,
            'starts_on' => $startsOn,
            'ends_on' => null,
            'region_id' => $this->region->id,
            'organizer_id' => null,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);

        foreach ($classes as $position => $class) {
            Race::factory()->for($event)->create([
                'slug' => "race-{$position}",
                'is_main' => $position === 0,
                'distance_class' => $class,
                'starts_at' => "{$startsOn} 09:00:00",
            ]);
        }

        return $event;
    }

    /**
     * The slugs of the cards in a block of the event page.
     *
     * @return list<string>
     */
    private function slugs(Event $event, string $block): array
    {
        $slugs = [];

        $this->get(route('en.events.show', $event))->assertInertia(function (Assert $page) use ($block, &$slugs) {
            $slugs = array_column($page->toArray()['props'][$block], 'slug');
        });

        return $slugs;
    }

    public function test_similar_races_share_the_region_and_a_distance_nearest_first(): void
    {
        $current = $this->event('current', '2026-11-20', [DistanceClass::TenK, DistanceClass::HalfMarathon]);
        $this->event('later-half', '2027-03-01', [DistanceClass::HalfMarathon]);
        $this->event('soon-10k', '2026-12-05', [DistanceClass::FiveK, DistanceClass::TenK]);
        $this->event('marathon-only', '2026-12-01', [DistanceClass::Marathon]);
        $this->event('other-region', '2026-12-02', attributes: ['region_id' => Region::factory()->create()->id]);
        $this->event('past', '2026-10-20');
        $this->event('draft', '2026-12-03', attributes: ['published_at' => null]);

        $this->assertSame(['soon-10k', 'later-half'], $this->slugs($current, 'similarEvents'));
    }

    public function test_at_most_three_similar_races(): void
    {
        $current = $this->event('current', '2026-11-20');

        foreach (['2026-12-04', '2026-12-01', '2026-12-03', '2026-12-02'] as $day) {
            $this->event("race-{$day}", $day);
        }

        $this->assertSame(['race-2026-12-01', 'race-2026-12-02', 'race-2026-12-03'], $this->slugs($current, 'similarEvents'));
    }

    public function test_an_other_class_race_or_no_region_finds_nothing_similar(): void
    {
        $other = $this->event('current', '2026-11-20', [DistanceClass::Other]);
        $this->event('also-other', '2026-12-01', [DistanceClass::Other]);
        $nowhere = $this->event('nowhere', '2026-11-21', attributes: ['region_id' => null]);
        $this->event('nowhere-too', '2026-12-02', attributes: ['region_id' => null]);

        $this->assertSame([], $this->slugs($other, 'similarEvents'));
        $this->app->forgetScopedInstances();
        $this->assertSame([], $this->slugs($nowhere, 'similarEvents'));
    }

    public function test_more_from_the_organizer_leaves_out_what_is_already_similar(): void
    {
        $organizer = Organizer::factory()->create();
        $current = $this->event('current', '2026-11-20', attributes: ['organizer_id' => $organizer->id]);
        $this->event('same-org-similar', '2026-12-01', attributes: ['organizer_id' => $organizer->id]);
        $this->event('same-org-trail', '2026-12-10', [DistanceClass::Ultra], ['organizer_id' => $organizer->id, 'region_id' => null]);
        $this->event('same-org-past', '2026-10-10', attributes: ['organizer_id' => $organizer->id]);
        $this->event('same-org-draft', '2026-12-11', attributes: ['organizer_id' => $organizer->id, 'published_at' => null]);
        $this->event('other-org', '2026-12-12', [DistanceClass::Ultra], ['organizer_id' => Organizer::factory()->create()->id]);

        $this->get(route('en.events.show', $current))->assertInertia(fn (Assert $page) => $page
            ->has('similarEvents', 1)
            ->where('similarEvents.0.slug', 'same-org-similar')
            ->has('organizerEvents', 1)
            ->where('organizerEvents.0.slug', 'same-org-trail')
            ->where('event.organizer.name', $organizer->translated('name')));
    }

    public function test_an_event_without_an_organizer_has_no_organizer_block(): void
    {
        $current = $this->event('current', '2026-11-20');
        $this->event('next', '2026-12-01');

        $this->assertSame([], $this->slugs($current, 'organizerEvents'));
    }

    public function test_the_cards_know_the_favorites_of_the_runner(): void
    {
        $current = $this->event('current', '2026-11-20');
        $next = $this->event('next', '2026-12-01');
        $user = User::factory()->create();
        $user->favorites()->create(['event_id' => $next->id]);

        $this->actingAs($user)->get(route('en.events.show', $current))->assertInertia(fn (Assert $page) => $page
            ->where('favorites', [$next->id]));

        $this->app->forgetScopedInstances();
        auth()->logout();

        $this->get(route('en.events.show', $current))->assertInertia(fn (Assert $page) => $page
            ->where('favorites', []));
    }
}
