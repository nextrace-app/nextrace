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

        DB::statement("
            update events
            left join races as main on main.event_id = events.id and main.is_main
            set
                events.category = coalesce(json_unquote(json_extract(main.name, '$.en')), ''),
                events.distance_meters = coalesce(main.distance_meters, 0),
                events.starts_at = coalesce(main.starts_at, events.starts_on),
                events.ends_at = events.ends_on
        ");

        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->unsignedInteger('distance_meters')->nullable(false)->change();
            $table->timestamp('starts_at')->nullable(false)->change();

            $table->index('starts_at');
            $table->index(['category', 'starts_at']);
            $table->dropIndex(['starts_on']);
        });
    }
};
