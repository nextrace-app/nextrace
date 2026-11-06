<?php

namespace Database\Factories;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'title' => ['el' => $title, 'en' => $title],
            'excerpt' => ['el' => fake()->sentence(), 'en' => fake()->sentence()],
            'body' => ['el' => fake()->paragraphs(3, true), 'en' => fake()->paragraphs(3, true)],
            'category' => fake()->randomElement(PostCategory::cases()),
            'status' => PostStatus::Published,
            'author_id' => null,
            'cover_image_url' => null,
            'cover_image_alt' => null,
            'meta_title' => null,
            'meta_description' => null,
            'source_url' => null,
            'published_at' => now()->subDay(),
        ];
    }

    /**
     * A post that is not published yet.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * A published post whose publication date has not come.
     */
    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Published,
            'published_at' => now()->addWeek(),
        ]);
    }

    /**
     * A post taken off the listings.
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Archived,
        ]);
    }
}
