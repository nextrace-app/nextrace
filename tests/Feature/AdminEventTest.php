<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function organizerUser(?Organizer $organizer = null): User
    {
        return User::factory()->create(['organizer_id' => ($organizer ?? Organizer::factory()->create())->id])->assignRole('organizer');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'night-run',
            'name' => ['el' => 'Νυχτερινός', 'en' => 'Night Run'],
            'description' => ['el' => 'Περιγραφή', 'en' => 'About'],
            'starts_on' => '2027-05-01',
            'city' => 'Athens',
            'status' => 'scheduled',
            'registration_status' => 'open',
            'date_confirmed' => true,
            'medical_certificate_required' => false,
            'races' => [$this->racePayload()],
            'links' => [],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function racePayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'ten-k',
            'name' => ['el' => '10 χλμ', 'en' => '10K'],
            'is_main' => true,
            'discipline' => 'run',
            'terrain' => 'road',
            'distance_meters' => 10000,
            'starts_at' => '2027-05-01T09:00',
            'status' => 'scheduled',
            'prices' => [['label' => ['en' => 'Early', 'el' => ''], 'amount_cents' => 2500, 'valid_until' => '2027-03-01']],
        ], $overrides);
    }

    private function draft(?Organizer $organizer = null): Event
    {
        return Event::factory()->create(['published_at' => null, 'organizer_id' => $organizer?->id]);
    }

    public function test_guests_get_a_404(): void
    {
        $this->get(route('admin.events.index'))->assertNotFound();
    }

    public function test_the_admin_creates_a_draft_in_both_languages(): void
    {
        $organizer = Organizer::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.events.store'), $this->payload(['organizer_id' => $organizer->id]))
            ->assertRedirect(route('admin.events.edit', 'night-run'));

        $event = Event::query()->where('slug', 'night-run')->firstOrFail();

        $this->assertSame('Night Run', $event->name['en']);
        $this->assertSame('Νυχτερινός', $event->name['el']);
        $this->assertSame('draft', $event->moderationState());
        $this->assertSame($organizer->id, $event->organizer_id);
    }

    public function test_english_urls_stay_in_english(): void
    {
        $this->actingAs($this->admin())
            ->post(route('en.admin.events.store'), $this->payload())
            ->assertRedirect(route('en.admin.events.edit', 'night-run'));
    }

    public function test_validation_rejects_bad_input(): void
    {
        $taken = $this->draft();

        $this->actingAs($this->admin())
            ->post(route('admin.events.store'), $this->payload([
                'slug' => $taken->slug,
                'name' => ['el' => '', 'en' => ''],
                'ends_on' => '2027-04-01',
                'status' => 'nope',
                'source_url' => 'javascript:alert(1)',
            ]))
            ->assertSessionHasErrors(['slug', 'name.el', 'name.en', 'ends_on', 'status', 'source_url']);
    }

    public function test_the_edit_page_and_update_work_and_keep_the_own_slug(): void
    {
        $event = $this->draft();

        $this->actingAs($this->admin())
            ->get(route('admin.events.edit', $event))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Events/Form')->where('record.slug', $event->slug)->where('record.state', 'draft'));

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload(['slug' => $event->slug, 'city' => 'Patras']))
            ->assertRedirect();

        $this->assertSame('Patras', $event->refresh()->city);
    }

    public function test_the_index_filters_by_state_region_and_search(): void
    {
        $region = Region::query()->first() ?? Region::factory()->create();
        $draft = $this->draft();
        $review = Event::factory()->create(['published_at' => null, 'submitted_at' => now(), 'region_id' => $region->id]);
        $live = Event::factory()->create();

        $count = fn (array $query) => $this->actingAs($this->admin())
            ->get(route('admin.events.index', $query))
            ->viewData('page')['props']['items']['total'];

        $this->assertSame(3, $count([]));
        $this->assertSame(1, $count(['state' => 'draft']));
        $this->assertSame(1, $count(['state' => 'in_review']));
        $this->assertSame(1, $count(['state' => 'published']));
        $this->assertSame(1, $count(['region_id' => $region->id]));
        $this->assertSame(1, $count(['search' => $draft->slug]));
        $this->assertNotNull($live->id);
        $this->assertNotNull($review->id);
    }

    public function test_the_workflow_goes_from_draft_to_review_to_published_and_back(): void
    {
        $event = $this->draft();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.events.submit', $event))->assertRedirect();
        $this->assertSame('in_review', $event->refresh()->moderationState());

        $this->actingAs($admin)->post(route('admin.events.publish', $event))->assertRedirect();
        $this->assertSame('published', $event->refresh()->moderationState());
        $this->assertContains($event->slug, Event::query()->published()->pluck('slug')->all());

        $this->actingAs($admin)->post(route('admin.events.unpublish', $event))->assertRedirect();
        $this->assertSame('draft', $event->refresh()->moderationState());
        $this->assertNotContains($event->slug, Event::query()->published()->pluck('slug')->all());
    }

    public function test_drafts_are_hidden_from_the_public_pages_and_the_sitemap(): void
    {
        $draft = $this->draft();

        $this->get(route('events.show', $draft))->assertNotFound();
        $this->get(route('events.index'))->assertDontSee($draft->slug);
        $this->get('/sitemap.xml')->assertDontSee($draft->slug);
    }

    public function test_deleting_an_event_is_a_soft_delete(): void
    {
        $event = $this->draft();

        $this->actingAs($this->admin())
            ->delete(route('admin.events.destroy', $event))
            ->assertRedirect(route('admin.events.index'));

        $this->assertSoftDeleted($event);
    }

    public function test_an_organizer_sees_and_edits_only_their_own_events(): void
    {
        $organizer = Organizer::factory()->create();
        $own = $this->draft($organizer);
        $other = $this->draft(Organizer::factory()->create());
        $user = $this->organizerUser($organizer);

        $this->actingAs($user)
            ->get(route('admin.events.index'))
            ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.slug', $own->slug));

        $this->actingAs($user)->get(route('admin.events.edit', $own))->assertOk();
        $this->actingAs($user)->get(route('admin.events.edit', $other))->assertForbidden();
        $this->actingAs($user)->put(route('admin.events.update', $other), $this->payload(['slug' => $other->slug]))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.events.destroy', $other))->assertForbidden();
        $this->actingAs($user)->post(route('admin.events.submit', $other))->assertForbidden();
    }

    public function test_an_organizer_files_events_under_their_own_organizer_and_cannot_publish(): void
    {
        $organizer = Organizer::factory()->create();
        $user = $this->organizerUser($organizer);

        $this->actingAs($user)
            ->post(route('admin.events.store'), $this->payload(['organizer_id' => Organizer::factory()->create()->id]))
            ->assertRedirect();

        $event = Event::query()->where('slug', 'night-run')->firstOrFail();
        $this->assertSame($organizer->id, $event->organizer_id);

        $this->actingAs($user)->post(route('admin.events.submit', $event))->assertRedirect();
        $this->assertSame('in_review', $event->refresh()->moderationState());

        $this->actingAs($user)->post(route('admin.events.publish', $event))->assertForbidden();
        $this->actingAs($user)->post(route('admin.events.unpublish', $event))->assertForbidden();
        $this->assertSame('in_review', $event->refresh()->moderationState());
    }

    public function test_an_organizer_without_an_organizer_link_cannot_create_events(): void
    {
        $user = User::factory()->create()->assignRole('organizer');

        $this->actingAs($user)->post(route('admin.events.store'), $this->payload())->assertForbidden();
    }

    public function test_regular_users_get_a_404(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.events.index'))->assertNotFound();
    }

    public function test_races_prices_and_links_are_saved_with_the_event(): void
    {
        $this->actingAs($this->admin())->post(route('admin.events.store'), $this->payload([
            'races' => [
                $this->racePayload(),
                $this->racePayload(['slug' => 'five-k', 'name' => ['el' => '5 χλμ', 'en' => '5K'], 'is_main' => false, 'distance_meters' => 5000, 'prices' => []]),
            ],
            'links' => [['type' => 'website', 'url' => 'https://example.com', 'label' => ['en' => 'Site', 'el' => '']]],
        ]))->assertRedirect(route('admin.events.edit', 'night-run'));

        $event = Event::query()->where('slug', 'night-run')->firstOrFail();

        $this->assertSame(['ten-k', 'five-k'], $event->races->pluck('slug')->all());
        $this->assertSame(2500, $event->races[0]->prices->first()->amount_cents);
        $this->assertSame(['en' => 'Early'], $event->races[0]->prices->first()->label);
        $this->assertSame('5k', $event->races[1]->distance_class->value);
        $this->assertSame('https://example.com', $event->links->first()->url);
    }

    public function test_updating_removes_races_missing_from_the_form_and_replaces_prices_and_links(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.events.store'), $this->payload([
            'races' => [$this->racePayload(), $this->racePayload(['slug' => 'five-k', 'is_main' => false, 'distance_meters' => 5000])],
            'links' => [['type' => 'website', 'url' => 'https://example.com']],
        ]));
        $event = Event::query()->where('slug', 'night-run')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload([
            'races' => [$this->racePayload(['prices' => []])],
            'links' => [],
        ]))->assertRedirect();

        $this->assertSame(['ten-k'], $event->races()->pluck('slug')->all());
        $this->assertSame(0, $event->races()->first()->prices()->count());
        $this->assertSame(0, $event->links()->count());
    }

    public function test_race_errors_point_at_the_race_and_nothing_is_saved(): void
    {
        $this->actingAs($this->admin())->post(route('admin.events.store'), $this->payload([
            'races' => [$this->racePayload(), $this->racePayload(['slug' => 'five-k', 'is_main' => false, 'distance_meters' => 0, 'prices' => [['amount_cents' => -1]]])],
            'links' => [['type' => 'website', 'url' => 'not a url']],
        ]))->assertSessionHasErrors(['races.1.distance_meters', 'races.1.prices.0.amount_cents', 'links.0.url']);

        $this->assertDatabaseMissing('events', ['slug' => 'night-run']);
    }

    public function test_exactly_one_race_has_to_be_the_main_race(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.events.store'), $this->payload(['races' => [$this->racePayload(['is_main' => false])]]))
            ->assertSessionHasErrors('races');

        $this->actingAs($this->admin())
            ->post(route('admin.events.store'), $this->payload(['races' => []]))
            ->assertSessionHasErrors('races');
    }

    public function test_the_edit_page_carries_races_and_links(): void
    {
        $event = $this->draft();

        $this->actingAs($this->admin())
            ->get(route('admin.events.edit', $event))
            ->assertInertia(fn (Assert $page) => $page->has('record.races', 1)->where('record.races.0.is_main', true)->has('record.links', 0)->has('raceOptions.disciplines'));
    }

    public function test_the_details_of_a_race_are_saved_and_come_back_to_the_form(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.events.store'), $this->payload([
            'races' => [$this->racePayload([
                'start_location' => ['el' => 'Μαραθώνας', 'en' => 'Marathon'],
                'finish_location' => ['el' => '', 'en' => 'Panathenaic Stadium'],
                'time_limit_minutes' => 480,
                'min_age' => 20,
                'participant_limit' => 20000,
                'aid_stations' => 15,
                'medical_certificate_required' => true,
                'route_url' => 'https://example.com/route',
            ])],
        ]))->assertRedirect();

        $race = Event::query()->where('slug', 'night-run')->firstOrFail()->races->first();

        $this->assertSame(['el' => 'Μαραθώνας', 'en' => 'Marathon'], $race->start_location);
        $this->assertSame(['en' => 'Panathenaic Stadium'], $race->finish_location);
        $this->assertSame(480, $race->time_limit_minutes);
        $this->assertSame(20, $race->min_age);
        $this->assertSame(20000, $race->participant_limit);
        $this->assertSame(15, $race->aid_stations);
        $this->assertTrue($race->medical_certificate_required);
        $this->assertSame('https://example.com/route', $race->route_url);

        $this->actingAs($admin)
            ->get(route('admin.events.edit', $race->event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.races.0.aid_stations', 15)
                ->where('record.races.0.medical_certificate_required', true)
                ->where('record.races.0.start_location', ['el' => 'Μαραθώνας', 'en' => 'Marathon']));
    }

    public function test_empty_race_details_are_stored_as_null(): void
    {
        $this->actingAs($this->admin())->post(route('admin.events.store'), $this->payload([
            'races' => [$this->racePayload([
                'start_location' => ['el' => '', 'en' => ''],
                'aid_stations' => null,
                'medical_certificate_required' => null,
            ])],
        ]))->assertRedirect();

        $race = Event::query()->where('slug', 'night-run')->firstOrFail()->races->first();

        $this->assertNull($race->start_location);
        $this->assertNull($race->aid_stations);
        $this->assertNull($race->medical_certificate_required);
    }

    public function test_race_details_are_validated(): void
    {
        $this->actingAs($this->admin())->post(route('admin.events.store'), $this->payload([
            'races' => [$this->racePayload([
                'time_limit_minutes' => 0,
                'min_age' => 200,
                'aid_stations' => -1,
                'route_url' => 'javascript:alert(1)',
            ])],
        ]))->assertSessionHasErrors(['races.0.time_limit_minutes', 'races.0.min_age', 'races.0.aid_stations', 'races.0.route_url']);
    }
}
