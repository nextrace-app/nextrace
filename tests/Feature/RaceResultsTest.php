<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RaceResultsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A published edition of the series.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function edition(EventSeries $series, string $slug, string $startsOn, ?int $number, array $attributes = []): Event
    {
        return Event::factory()->create([
            'slug' => $slug,
            'series_id' => $series->id,
            'edition_number' => $number,
            'starts_on' => $startsOn,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_the_page_lists_the_published_editions_of_the_series_oldest_first(): void
    {
        $series = EventSeries::factory()->create();
        $current = $this->edition($series, 'athens-2026', '2026-11-08', 43);
        $this->edition($series, 'athens-2024', '2024-11-10', 41);
        $this->edition($series, 'athens-2025', '2025-11-09', null);
        $this->edition($series, 'athens-2027', '2027-11-14', 44, ['published_at' => null]);
        // An event of another series is not an edition of this one.
        $this->edition(EventSeries::factory()->create(), 'other-2025', '2025-05-01', 5);

        $this->get(route('en.events.show', $current))->assertInertia(fn (Assert $page) => $page
            ->has('editions', 3)
            ->where('editions.0', ['slug' => 'athens-2024', 'year' => 2024, 'edition_number' => 41, 'current' => false])
            ->where('editions.1', ['slug' => 'athens-2025', 'year' => 2025, 'edition_number' => null, 'current' => false])
            ->where('editions.2', ['slug' => 'athens-2026', 'year' => 2026, 'edition_number' => 43, 'current' => true]));
    }

    public function test_a_single_published_edition_or_no_series_has_nothing_to_switch(): void
    {
        $series = EventSeries::factory()->create();
        $only = $this->edition($series, 'only-2026', '2026-11-08', 1);
        $this->edition($series, 'draft-2027', '2027-11-14', 2, ['published_at' => null]);
        $alone = Event::factory()->create(['series_id' => null, 'published_at' => now()]);

        $this->get(route('en.events.show', $only))->assertInertia(fn (Assert $page) => $page
            ->where('editions', []));

        $this->app->forgetScopedInstances();

        $this->get(route('en.events.show', $alone))->assertInertia(fn (Assert $page) => $page
            ->where('editions', []));
    }

    public function test_the_page_sends_the_results_photos_and_certificate_links(): void
    {
        $event = Event::factory()->create(['published_at' => now()]);
        $event->links()->create(['type' => LinkType::Website, 'url' => 'https://example.com', 'sort_order' => 0]);
        $event->links()->create(['type' => LinkType::Results, 'url' => 'https://example.com/results', 'sort_order' => 1]);
        $event->links()->create(['type' => LinkType::Certificate, 'url' => 'https://example.com/certificate', 'sort_order' => 2]);

        $this->get(route('en.events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('event.links.1.type', 'results')
            ->where('event.links.2.type', 'certificate')
            ->where('event.links.2.url', 'https://example.com/certificate'));
    }

    public function test_the_admin_saves_a_certificate_link(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');
        $event = Event::factory()->create(['published_at' => now()]);

        $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'slug' => $event->slug,
            'name' => ['el' => 'Αγώνας', 'en' => 'Race'],
            'description' => ['el' => 'Περιγραφή', 'en' => 'About'],
            'starts_on' => '2026-11-08',
            'city' => 'Athens',
            'status' => 'scheduled',
            'registration_status' => 'open',
            'date_confirmed' => true,
            'medical_certificate_required' => false,
            'races' => [[
                'slug' => 'ten-k',
                'name' => ['el' => '10 χλμ', 'en' => '10K'],
                'is_main' => true,
                'discipline' => 'run',
                'terrain' => 'road',
                'distance_meters' => 10000,
                'starts_at' => '2026-11-08T09:00',
                'status' => 'scheduled',
            ]],
            'links' => [['type' => 'certificate', 'url' => 'https://example.com/certificate']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(LinkType::Certificate, $event->links()->sole()->type);
    }
}
