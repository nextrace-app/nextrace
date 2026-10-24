<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->jsonb('name');
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_main')->default(false);
            $table->string('discipline')->default('run');
            $table->string('terrain')->nullable();
            $table->string('distance_class')->nullable();
            $table->unsignedInteger('distance_meters');
            $table->unsignedInteger('elevation_gain_m')->nullable();
            $table->unsignedTinyInteger('team_size')->nullable();

            // The local wall-clock time of the start; midnight with start_time_known false means the time is not announced.
            // A datetime, so the session time zone does not shift it the way it would a timestamp.
            $table->dateTime('starts_at');
            $table->boolean('start_time_known')->default(true);
            $table->jsonb('start_location')->nullable();
            $table->jsonb('finish_location')->nullable();
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedInteger('participant_limit')->nullable();
            $table->boolean('is_competitive')->default(true);
            $table->string('certification')->nullable();
            $table->string('timing_provider')->nullable();
            $table->jsonb('awards')->nullable();
            $table->string('route_url')->nullable();
            $table->string('registration_url')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'sort_order']);
            $table->index(['distance_class', 'starts_at']);
            $table->index('starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
