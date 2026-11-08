<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePostRequest;
use App\Http\Resources\StoredPostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Store a post, in the locale of the request, as a draft.
     *
     * The status is fixed here and not read from the request, so there is no
     * way to publish through the API. The author is the owner of the token.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $locale = $data['locale'];

        $post = Post::query()->create([
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['title']),
            'title' => [$locale => $data['title']],
            'excerpt' => $this->inLocale($locale, $data['excerpt'] ?? null),
            'body' => [$locale => $data['body']],
            'category' => $data['category'],
            'status' => PostStatus::Draft,
            'author_id' => $request->user()?->id,
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'cover_image_alt' => $this->inLocale($locale, $data['cover_image_alt'] ?? null),
            'meta_title' => $this->inLocale($locale, $data['meta_title'] ?? null),
            'meta_description' => $this->inLocale($locale, $data['meta_description'] ?? null),
            'source_url' => $data['source_url'] ?? null,
            'published_at' => null,
        ]);

        if (! empty($data['event_ids'])) {
            $post->events()->sync($data['event_ids']);
        }

        return StoredPostResource::make($post)->response()->setStatusCode(201);
    }

    /**
     * A text under its locale, or null when there is no text.
     *
     * @return array<string, string>|null
     */
    private function inLocale(string $locale, ?string $text): ?array
    {
        return $text === null || $text === '' ? null : [$locale => $text];
    }

    /**
     * The slug of a title, with a number added when it is taken, soft-deleted posts included.
     */
    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 200, '') ?: 'post';
        $slug = $base;

        for ($number = 2; Post::withTrashed()->where('slug', $slug)->exists(); $number++) {
            $slug = "{$base}-{$number}";
        }

        return $slug;
    }
}
