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
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedSmallInteger('aid_stations')->nullable()->after('time_limit_minutes');
            // Null means "as the event says": the event keeps its own flag.
            $table->boolean('medical_certificate_required')->nullable()->after('min_age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['aid_stations', 'medical_certificate_required']);
        });
    }
};
