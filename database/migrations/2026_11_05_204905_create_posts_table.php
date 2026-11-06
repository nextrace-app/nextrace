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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Translated text, keyed by locale, e.g. {"el": "...", "en": "..."}. The body is Markdown.
            $table->jsonb('title');
            $table->jsonb('excerpt')->nullable();
            $table->jsonb('body');

            $table->string('category');
            $table->string('status')->default('draft');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('cover_image_url')->nullable();
            $table->jsonb('cover_image_alt')->nullable();

            // Overrides of the title and the excerpt in the page head.
            $table->jsonb('meta_title')->nullable();
            $table->jsonb('meta_description')->nullable();

            $table->string('source_url')->nullable();

            // A date in the future schedules the post.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
