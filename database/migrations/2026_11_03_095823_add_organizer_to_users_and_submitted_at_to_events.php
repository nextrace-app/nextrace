<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a user to the organizer they act for, and mark when an event was
     * handed in for review.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizer_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizer_id');
        });
    }
};
