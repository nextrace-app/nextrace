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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Translated text, keyed by locale, e.g. {"el": "...", "en": "..."}.
            $table->jsonb('name');
            $table->jsonb('description');

            $table->string('category');
            $table->unsignedInteger('distance_meters');

            $table->timestamp('starts_at');

            // Only races that run past midnight, such as the Spartathlon, carry an end.
            $table->timestamp('ends_at')->nullable();

            $table->string('city');
            $table->char('country', 2)->default('GR');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedInteger('participants')->nullable();
            $table->string('image_url')->nullable();
            $table->string('registration_url')->nullable();

            // Events without a publication date stay out of the listings.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index('starts_at');
            $table->index(['category', 'starts_at']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
