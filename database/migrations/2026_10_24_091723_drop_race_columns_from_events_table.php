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
        Schema::table('events', function (Blueprint $table) {
            $table->date('starts_on')->nullable(false)->change();
            $table->index('starts_on');
            $table->index('region_id');

            $table->dropIndex(['category', 'starts_at']);
            $table->dropIndex(['starts_at']);
            $table->dropColumn(['category', 'distance_meters', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
        });

        DB::statement('
            update events set
                category = coalesce(main.name ->> \'en\', \'\'),
                distance_meters = coalesce(main.distance_meters, 0),
                starts_at = coalesce(main.starts_at, events.starts_on),
                ends_at = events.ends_on
            from events as e
            left join races as main on main.event_id = e.id and main.is_main
            where events.id = e.id
        ');

        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->unsignedInteger('distance_meters')->nullable(false)->change();
            $table->timestamp('starts_at')->nullable(false)->change();

            $table->index('starts_at');
            $table->index(['category', 'starts_at']);
            $table->dropIndex(['starts_on']);
            $table->dropIndex(['region_id']);
        });
    }
};
