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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Only the SHA-256 hash of the token is kept; the token itself is shown once, when it is created.
            $table->char('token_hash', 64)->unique();

            // What the token may do, e.g. ["events:read"], or ["*"] for everything.
            $table->json('abilities');

            // The user the token acts as, e.g. the author of the posts it creates.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
