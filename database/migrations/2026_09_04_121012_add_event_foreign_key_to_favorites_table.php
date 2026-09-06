<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Favorites were made while the catalogue was a constant, so some of
        // them can point at an event the table never received. The pages
        // already skipped those, and the key will not be created while they
        // are around.
        DB::table('favorites')
            ->whereNotIn('event_id', DB::table('events')->select('id'))
            ->delete();

        Schema::table('favorites', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
        });
    }
};
