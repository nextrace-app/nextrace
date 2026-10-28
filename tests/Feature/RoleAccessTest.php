<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_the_roles_and_can_run_twice(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(
            ['admin', 'organizer'],
            Role::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_a_role_can_be_assigned_and_checked(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();

        $user->assignRole(UserRole::Organizer->value);

        $this->assertTrue($user->hasRole('organizer'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_factory_states_assign_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();
        $regular = User::factory()->create();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($organizer->hasRole('organizer'));
        $this->assertCount(0, $regular->roles);
    }

    public function test_admins_can_open_the_admin_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_organizers_and_regular_users_are_denied_the_admin_page(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_login_from_the_admin_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_roles_are_shared_with_the_frontend(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.roles', ['organizer']));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.roles', []));
    }

    public function test_guests_share_no_roles(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.roles', []));
    }
}
