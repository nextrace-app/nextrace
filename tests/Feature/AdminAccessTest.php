<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Race;
use App\Models\Region;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * A user whose role holds exactly the given permissions.
     *
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::findOrCreate('manager', 'web');
        $role->syncPermissions($permissions);

        return User::factory()->create()->assignRole($role);
    }

    private function registerSectionRoutes(): void
    {
        foreach (['events', 'regions', 'series'] as $section) {
            Route::middleware(['web', 'auth', 'can:'.Permissions::ADMIN_ACCESS])
                ->get("admin/{$section}", fn () => 'ok')
                ->name("admin.{$section}.index");
        }

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_the_seeder_creates_every_permission_and_can_run_twice(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertEqualsCanonicalizing(
            Permissions::all(),
            Permission::query()->pluck('name')->all(),
        );
    }

    public function test_the_admin_role_holds_every_permission_and_the_organizer_none(): void
    {
        $this->assertEqualsCanonicalizing(
            Permissions::all(),
            Role::findByName('admin')->permissions->pluck('name')->all(),
        );
        $this->assertCount(0, Role::findByName('organizer')->permissions);
    }

    public function test_the_admin_overview_renders_for_an_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Overview'));
    }

    public function test_the_admin_area_is_localized(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('en.admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Overview')->where('locale', 'en'));
    }

    public function test_a_user_without_the_permission_gets_the_forbidden_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403));
    }

    public function test_a_role_with_only_the_admin_permission_can_open_the_overview_without_a_code_change(): void
    {
        $this->actingAs($this->userWithPermissions([Permissions::ADMIN_ACCESS]))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminMenu', [
                ['key' => 'overview', 'href' => '/admin'],
            ]));
    }

    public function test_a_section_permission_alone_does_not_open_the_admin_area(): void
    {
        $this->actingAs($this->userWithPermissions(['events.viewAny']))
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_the_menu_lists_only_the_sections_the_user_may_open(): void
    {
        $this->registerSectionRoutes();

        $this->actingAs($this->userWithPermissions([Permissions::ADMIN_ACCESS, 'events.viewAny', 'series.viewAny']))
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('adminMenu', [
                ['key' => 'overview', 'href' => '/admin'],
                ['key' => 'events', 'href' => '/admin/events'],
                ['key' => 'series', 'href' => '/admin/series'],
            ]));
    }

    public function test_the_admin_menu_lists_every_built_section(): void
    {
        $this->registerSectionRoutes();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('adminMenu', fn ($menu) => collect($menu)->pluck('key')->all() === ['overview', 'events', 'regions', 'series']));
    }

    public function test_the_menu_links_stay_in_the_current_locale(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('en.admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('adminMenu.0.href', '/en/admin'));
    }

    public function test_public_pages_share_no_admin_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('adminMenu', []));
    }

    public function test_admins_pass_every_policy_ability(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([Event::class, Race::class, EventSeries::class, Region::class] as $model) {
            $this->assertTrue($admin->can('viewAny', $model), "{$model} viewAny");
            $this->assertTrue($admin->can('create', $model), "{$model} create");
            $this->assertTrue($admin->can('update', new $model), "{$model} update");
            $this->assertTrue($admin->can('delete', new $model), "{$model} delete");
        }

        $this->assertTrue($admin->can('publish', Event::class));
    }

    public function test_users_without_permissions_fail_every_policy_ability(): void
    {
        foreach ([User::factory()->organizer()->create(), User::factory()->create()] as $user) {
            foreach ([Event::class, Race::class, EventSeries::class, Region::class] as $model) {
                $this->assertFalse($user->can('viewAny', $model), "{$model} viewAny");
                $this->assertFalse($user->can('create', $model), "{$model} create");
                $this->assertFalse($user->can('update', new $model), "{$model} update");
                $this->assertFalse($user->can('delete', new $model), "{$model} delete");
            }

            $this->assertFalse($user->can('publish', Event::class));
        }
    }

    public function test_a_permission_grants_only_its_own_ability(): void
    {
        $user = $this->userWithPermissions(['events.update']);

        $this->assertTrue($user->can('update', new Event));
        $this->assertFalse($user->can('delete', new Event));
        $this->assertFalse($user->can('update', new Region));
        $this->assertFalse($user->can('publish', Event::class));
    }
}
