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
}
