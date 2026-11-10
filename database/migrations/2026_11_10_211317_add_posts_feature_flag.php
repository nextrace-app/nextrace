<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The flag that shows posts on the site. It starts off, so posts stay hidden
 * until an admin switches it on under Features; the API still takes them in.
 * A flag that is already there, with whatever state it has, is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('feature_flags')->insertOrIgnore([
            'key' => 'posts',
            'description' => 'Posts on the site: the Posts pages, the menu link and race news on event pages',
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
