<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the features.* permissions and give them to the admin role, by
     * running the role seeder, which is safe to run again.
     */
    public function up(): void
    {
        (new RoleSeeder)->run();
    }

    /**
     * The permission stays: the seeder owns the permissions.
     */
    public function down(): void
    {
        //
    }
};
