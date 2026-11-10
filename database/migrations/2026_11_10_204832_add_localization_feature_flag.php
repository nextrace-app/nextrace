<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The flag that serves the site in every supported language. It starts off, so
 * the site runs in Greek only until an admin switches it on under Features.
 * A flag that is already there, with whatever state it has, is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('feature_flags')->insertOrIgnore([
            'key' => 'localization',
            'description' => 'English version of the site (/en) and the language switcher',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
