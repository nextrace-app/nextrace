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
     * Safe to run again. The organizer has no permissions until organizers get
     * their own part of the admin area.
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
    }
}
