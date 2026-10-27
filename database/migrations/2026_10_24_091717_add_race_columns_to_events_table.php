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
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->constrained('event_series')->nullOnDelete();
            $table->foreignId('organizer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('edition_number')->nullable();

            // Added empty and filled from starts_at by the migration that moves the
            // race data; it is made required once every row has a value.
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();

            // Local wall-clock times are stored as they are at the race.
            $table->string('timezone')->default('Europe/Athens');
            $table->string('status')->default('scheduled');
            $table->boolean('date_confirmed')->default(true);
            $table->jsonb('venue')->nullable();
            $table->string('address')->nullable();
            $table->string('registration_status')->default('unknown');
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('results_url')->nullable();
            $table->jsonb('package_description')->nullable();
            $table->jsonb('cancellation_policy')->nullable();
            $table->boolean('medical_certificate_required')->default(false);
            $table->string('source_url')->nullable();
            $table->timestamp('source_verified_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropForeign(['organizer_id']);
            $table->dropForeign(['region_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'series_id', 'organizer_id', 'region_id', 'edition_number', 'starts_on', 'ends_on',
                'timezone', 'status', 'date_confirmed', 'venue', 'address', 'registration_status',
                'registration_opens_at', 'registration_closes_at', 'contact_email', 'contact_phone',
                'results_url', 'package_description', 'cancellation_policy', 'medical_certificate_required',
                'source_url', 'source_verified_at',
            ]);
        });
    }
};
