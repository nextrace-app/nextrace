<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // A migration seeds the regions of Greece; the tests count their own.
        Region::query()->delete();
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::findOrCreate('limited', 'web');
        $role->syncPermissions($permissions);

        return User::factory()->create()->assignRole($role);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sections(): array
    {
        return ['regions' => ['regions'], 'series' => ['series']];
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(string $section, string $slug = 'new-one'): array
    {
        return [
            'slug' => $slug,
            'name' => ['el' => 'Νέο', 'en' => 'New'],
        ] + ($section === 'series' ? ['website' => 'https://example.com'] : []);
    }

    private function createRecord(string $section, string $slug = 'existing'): Region|EventSeries
    {
        $attributes = ['slug' => $slug, 'name' => ['el' => 'Υπάρχον', 'en' => 'Existing']];

        return $section === 'regions'
            ? Region::query()->create($attributes)
            : EventSeries::factory()->create($attributes);
    }

    #[DataProvider('sections')]
    public function test_guests_get_a_404(string $section): void
    {
        $this->get(route("admin.{$section}.index"))->assertNotFound();
    }

    #[DataProvider('sections')]
    public function test_the_index_lists_records_and_searches_in_both_languages(string $section): void
    {
        $this->createRecord($section, 'alpha');
        $other = $this->createRecord($section, 'beta');
        $other->update(['name' => ['el' => 'Βήτα', 'en' => 'Betaland']]);

        $this->actingAs($this->admin())
            ->get(route("admin.{$section}.index"))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reference/Index')
                ->has('items.data', 2)
                ->where('canCreate', true));

        $this->actingAs($this->admin())
            ->get(route("admin.{$section}.index", ['search' => 'Betaland']))
            ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.slug', 'beta'));

        $this->actingAs($this->admin())
            ->get(route("admin.{$section}.index", ['search' => 'alpha']))
            ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.slug', 'alpha'));
    }

    #[DataProvider('sections')]
    public function test_the_index_is_paginated(string $section): void
    {
        foreach (range(1, 17) as $number) {
            $this->createRecord($section, "record-{$number}");
        }

        $this->actingAs($this->admin())
            ->get(route("admin.{$section}.index"))
            ->assertInertia(fn (Assert $page) => $page->has('items.data', 15)->where('items.total', 17)->whereNot('items.next_url', null));
    }

    #[DataProvider('sections')]
    public function test_a_record_can_be_created(string $section): void
    {
        $this->actingAs($this->admin())
            ->post(route("admin.{$section}.store"), $this->validPayload($section))
            ->assertRedirect(route("admin.{$section}.index"));

        $model = $section === 'regions' ? Region::class : EventSeries::class;
        $record = $model::query()->where('slug', 'new-one')->firstOrFail();

        $this->assertSame(['el' => 'Νέο', 'en' => 'New'], $record->name);
    }

    #[DataProvider('sections')]
    public function test_english_urls_redirect_back_to_english(string $section): void
    {
        $this->actingAs($this->admin())
            ->post(route("en.admin.{$section}.store"), $this->validPayload($section))
            ->assertRedirect(route("en.admin.{$section}.index"));
    }

    #[DataProvider('sections')]
    public function test_a_record_can_be_updated_keeping_its_own_slug(string $section): void
    {
        $record = $this->createRecord($section);
        $param = $section === 'regions' ? 'region' : 'series';

        $this->actingAs($this->admin())
            ->put(route("admin.{$section}.update", [$param => $record]), ['slug' => 'existing', 'name' => ['el' => 'Άλλο', 'en' => 'Other']])
            ->assertRedirect(route("admin.{$section}.index"));

        $this->assertSame('Other', $record->refresh()->name['en']);
    }

    #[DataProvider('sections')]
    public function test_the_edit_page_carries_the_record(string $section): void
    {
        $record = $this->createRecord($section);
        $param = $section === 'regions' ? 'region' : 'series';

        $this->actingAs($this->admin())
            ->get(route("admin.{$section}.edit", [$param => $record]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reference/Form')
                ->where('record.slug', 'existing')
                ->where('record.name.el', 'Υπάρχον'));
    }

    #[DataProvider('sections')]
    public function test_validation_rejects_bad_input(string $section): void
    {
        $this->createRecord($section, 'taken');

        $this->actingAs($this->admin())
            ->post(route("admin.{$section}.store"), ['slug' => 'taken', 'name' => ['el' => '', 'en' => '']])
            ->assertSessionHasErrors(['slug', 'name.el', 'name.en']);

        $this->actingAs($this->admin())
            ->post(route("admin.{$section}.store"), ['slug' => 'Not A Slug', 'name' => ['el' => 'a', 'en' => 'b']])
            ->assertSessionHasErrors(['slug']);
    }

    public function test_a_series_rejects_a_bad_website(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.series.store'), $this->validPayload('series') + ['logo_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors(['logo_url']);
    }

    public function test_a_region_cannot_be_its_own_ancestor(): void
    {
        $parent = Region::factory()->create();
        $child = Region::factory()->within($parent)->create();

        $this->actingAs($this->admin())
            ->put(route('admin.regions.update', $parent), ['slug' => $parent->slug, 'name' => ['el' => 'a', 'en' => 'b'], 'parent_id' => $child->id])
            ->assertSessionHasErrors(['parent_id']);
    }

    #[DataProvider('sections')]
    public function test_an_unused_record_can_be_deleted(string $section): void
    {
        $record = $this->createRecord($section);
        $param = $section === 'regions' ? 'region' : 'series';

        $this->actingAs($this->admin())
            ->delete(route("admin.{$section}.destroy", [$param => $record]))
            ->assertRedirect(route("admin.{$section}.index"));

        $this->assertModelMissing($record);
    }

    #[DataProvider('sections')]
    public function test_a_record_used_by_events_cannot_be_deleted(string $section): void
    {
        $record = $this->createRecord($section);
        $param = $section === 'regions' ? 'region' : 'series';
        Event::factory()->create($section === 'regions' ? ['region_id' => $record->id] : ['series_id' => $record->id]);

        $this->actingAs($this->admin())
            ->delete(route("admin.{$section}.destroy", [$param => $record]))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($record);
    }

    public function test_a_region_with_child_regions_cannot_be_deleted(): void
    {
        $parent = Region::factory()->create();
        Region::factory()->within($parent)->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.regions.destroy', $parent))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($parent);
    }

    #[DataProvider('sections')]
    public function test_a_user_without_the_permission_gets_a_403(string $section): void
    {
        $record = $this->createRecord($section);
        $param = $section === 'regions' ? 'region' : 'series';
        $user = $this->userWithPermissions([Permissions::ADMIN_ACCESS]);

        $this->actingAs($user)->get(route("admin.{$section}.index"))->assertForbidden();
        $this->actingAs($user)->get(route("admin.{$section}.create"))->assertForbidden();
        $this->actingAs($user)->post(route("admin.{$section}.store"), $this->validPayload($section))->assertForbidden();
        $this->actingAs($user)->get(route("admin.{$section}.edit", [$param => $record]))->assertForbidden();
        $this->actingAs($user)->put(route("admin.{$section}.update", [$param => $record]), $this->validPayload($section))->assertForbidden();
        $this->actingAs($user)->delete(route("admin.{$section}.destroy", [$param => $record]))->assertForbidden();
    }

    #[DataProvider('sections')]
    public function test_a_viewer_sees_the_list_without_the_action_links(string $section): void
    {
        $this->createRecord($section);
        $user = $this->userWithPermissions([Permissions::ADMIN_ACCESS, "{$section}.viewAny"]);

        $this->actingAs($user)
            ->get(route("admin.{$section}.index"))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canCreate', false)
                ->where('items.data.0.edit_url', null)
                ->where('items.data.0.delete_url', null));
    }

    #[DataProvider('sections')]
    public function test_the_admin_menu_links_the_new_sections(string $section): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('adminMenu', fn ($menu) => collect($menu)->contains('key', $section)));
    }
}
