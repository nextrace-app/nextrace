<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FeatureFlag;
use App\Models\User;
use App\Support\Features;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Ada Admin'])->assignRole(UserRole::Admin->value);
        $this->organizer = User::factory()->create()->assignRole(UserRole::Organizer->value);
    }

    /**
     * The feature keys the pages receive, for whoever makes the request.
     *
     * @return list<string>
     */
    private function sharedFeatures(?User $user): array
    {
        $this->app->forgetScopedInstances();
        auth()->logout();

        if ($user !== null) {
            $this->actingAs($user);
        }

        $features = [];

        $this->get(route('en.events.index'))->assertInertia(function (Assert $page) use (&$features) {
            $features = $page->toArray()['props']['features'];
        });

        return $features;
    }

    public function test_the_admin_sees_the_flags_by_key_with_their_actions(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        FeatureFlag::factory()->active()->create(['key' => 'posts', 'description' => 'Race news.', 'updated_by' => $this->admin->id]);
        FeatureFlag::factory()->create(['key' => 'calendar-export']);

        $this->actingAs($this->admin)->get(route('en.admin.features.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Features/Index')
            ->has('flags', 2)
            ->where('flags.0.key', 'calendar-export')
            ->where('flags.0.is_active', false)
            ->where('flags.0.updated_by', null)
            ->where('flags.1.key', 'posts')
            ->where('flags.1.description', 'Race news.')
            ->where('flags.1.is_active', true)
            ->where('flags.1.updated_by', 'Ada Admin')
            ->where('flags.1.updated_at', fn (string $date) => str_starts_with($date, '2026-11-10'))
            ->where('flags.1.edit_url', '/en/admin/features/posts/edit')
            ->where('flags.1.activate_url', '/en/admin/features/posts/active')
            ->where('flags.1.delete_url', '/en/admin/features/posts')
            ->where('canCreate', true)
            ->where('urls.create', '/en/admin/features/create')
            ->where('adminMenu', fn ($menu) => collect($menu)->pluck('key')->contains('features')));
    }

    public function test_the_list_can_be_empty(): void
    {
        $this->actingAs($this->admin)->get(route('admin.features.index'))->assertInertia(fn (Assert $page) => $page
            ->where('flags', []));
    }

    public function test_the_organizer_and_guests_cannot_reach_the_section(): void
    {
        $flag = FeatureFlag::factory()->create(['key' => 'posts']);
        $payload = ['key' => 'new-flag', 'description' => 'New.'];

        $this->get(route('admin.features.index'))->assertNotFound();
        $this->post(route('admin.features.store'), $payload)->assertNotFound();

        $this->actingAs($this->organizer);
        $this->get(route('admin.features.index'))->assertForbidden();
        $this->get(route('admin.features.create'))->assertForbidden();
        $this->post(route('admin.features.store'), $payload)->assertForbidden();
        $this->get(route('admin.features.edit', $flag))->assertForbidden();
        $this->put(route('admin.features.update', $flag), ['description' => 'Changed.'])->assertForbidden();
        $this->put(route('admin.features.activate', $flag), ['is_active' => true])->assertForbidden();
        $this->delete(route('admin.features.destroy', $flag))->assertForbidden();

        $this->get(route('admin.events.index'))->assertInertia(fn (Assert $page) => $page
            ->where('adminMenu', fn ($menu) => ! collect($menu)->pluck('key')->contains('features')));

        $this->assertDatabaseCount('feature_flags', 1);
        $this->assertFalse($flag->fresh()->is_active);
    }

    public function test_the_admin_creates_a_flag_that_starts_off(): void
    {
        $this->actingAs($this->admin)->get(route('admin.features.create'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Features/Form')
            ->where('flag', null)
            ->where('urls.submit', '/admin/features'));

        $this->post(route('admin.features.store'), ['key' => 'race-news', 'description' => 'Race news on the site.', 'is_active' => true])
            ->assertRedirect(route('admin.features.index'))
            ->assertSessionHasNoErrors();

        $flag = FeatureFlag::query()->sole();

        $this->assertSame('race-news', $flag->key);
        $this->assertSame('Race news on the site.', $flag->description);
        $this->assertFalse($flag->is_active);
        $this->assertSame($this->admin->id, $flag->updated_by);
    }

    public function test_a_key_has_to_be_well_formed_and_free(): void
    {
        FeatureFlag::factory()->create(['key' => 'posts']);
        $this->actingAs($this->admin);

        foreach (['', 'Posts', 'race news', 'race_news', '-posts', 'posts-', 'ποστ', str_repeat('a', 65), 'posts'] as $key) {
            $this->post(route('admin.features.store'), ['key' => $key, 'description' => 'Something.'])->assertSessionHasErrors('key');
        }

        $this->post(route('admin.features.store'), ['key' => 'race-news-2'])->assertSessionHasErrors('description');

        $this->assertDatabaseCount('feature_flags', 1);
    }

    public function test_the_admin_edits_the_description_but_never_the_key(): void
    {
        $flag = FeatureFlag::factory()->create(['key' => 'posts', 'description' => 'Old.']);

        $this->actingAs($this->admin)->get(route('admin.features.edit', $flag))->assertInertia(fn (Assert $page) => $page
            ->where('flag', ['key' => 'posts', 'description' => 'Old.'])
            ->where('urls.submit', '/admin/features/posts'));

        $this->put(route('admin.features.update', $flag), ['key' => 'renamed', 'description' => 'New.'])
            ->assertRedirect(route('admin.features.index'))
            ->assertSessionHasNoErrors();

        $flag->refresh();
        $this->assertSame('posts', $flag->key);
        $this->assertSame('New.', $flag->description);
        $this->assertSame($this->admin->id, $flag->updated_by);

        $this->put(route('admin.features.update', $flag), ['description' => ''])->assertSessionHasErrors('description');
    }

    public function test_the_admin_switches_a_flag_on_and_off(): void
    {
        $flag = FeatureFlag::factory()->create(['key' => 'posts']);
        $this->actingAs($this->admin);

        $this->from(route('admin.features.index'))
            ->put(route('admin.features.activate', $flag), ['is_active' => true])
            ->assertRedirect(route('admin.features.index'));
        $this->assertTrue($flag->fresh()->is_active);
        $this->assertSame($this->admin->id, $flag->fresh()->updated_by);

        $this->put(route('admin.features.activate', $flag), ['is_active' => false]);
        $this->assertFalse($flag->fresh()->is_active);

        $this->put(route('admin.features.activate', $flag), ['is_active' => 'maybe'])->assertSessionHasErrors('is_active');
        $this->put(route('admin.features.activate', $flag), [])->assertSessionHasErrors('is_active');
    }

    public function test_the_admin_deletes_a_flag(): void
    {
        $flag = FeatureFlag::factory()->active()->create(['key' => 'posts']);

        $this->actingAs($this->admin)->delete(route('admin.features.destroy', $flag))
            ->assertRedirect(route('admin.features.index'));

        $this->assertDatabaseCount('feature_flags', 0);
        $this->get(route('admin.features.edit', 'posts'))->assertNotFound();
    }

    public function test_the_pages_receive_the_keys_of_the_flags_that_are_on_for_everyone(): void
    {
        $this->assertSame([], $this->sharedFeatures(null));

        FeatureFlag::factory()->active()->create(['key' => 'posts']);
        FeatureFlag::factory()->active()->create(['key' => 'calendar-export']);
        FeatureFlag::factory()->create(['key' => 'similar-races']);

        $this->assertSame(['calendar-export', 'posts'], $this->sharedFeatures(null));
        $this->assertSame(['calendar-export', 'posts'], $this->sharedFeatures($this->organizer));
    }

    public function test_code_checks_a_flag_by_key_and_an_unknown_key_is_off(): void
    {
        FeatureFlag::factory()->active()->create(['key' => 'posts']);
        FeatureFlag::factory()->create(['key' => 'similar-races']);

        $this->assertTrue(Features::active('posts'));
        $this->assertFalse(Features::active('similar-races'));
        $this->assertFalse(Features::active('never-added'));
    }
}
