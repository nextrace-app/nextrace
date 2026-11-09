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
        Schema::create('race_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gpx_path');
            $table->string('gpx_name');
            $table->unsignedInteger('distance_meters');
            $table->unsignedInteger('elevation_gain_m')->nullable();
            $table->unsignedInteger('elevation_loss_m')->nullable();
            $table->integer('highest_point_m')->nullable();
            $table->json('track');
            $table->json('profile')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_courses');
    }
};
