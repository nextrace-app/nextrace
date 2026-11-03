<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Create the permissions and the roles, and give the admin every permission.
     * Safe to run again. The organizer may manage their own events, not publish them.
     */
    public function run(): void
    {
        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        // The permissions were just created, so drop the cached list before using it.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName(UserRole::Admin->value, 'web')->syncPermissions(Permissions::all());
        Role::findByName(UserRole::Organizer->value, 'web')->syncPermissions(Permissions::forOrganizer());
    }
}
