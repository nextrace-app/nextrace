<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A post in the shape the posts pages render it: text in the locale of the
 * request, and the publication date as a plain day.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translated('title'),
            'excerpt' => $this->translatedOrNull('excerpt'),
            'category' => $this->category->value,
            'date' => $this->published_at?->toDateString(),
            'image' => $this->cover_image_url,
            'image_alt' => $this->translatedOrNull('cover_image_alt'),
            'source_url' => $this->safeSourceUrl(),
        ];
    }

    /**
     * The source of the post, but only when it is a web address a visitor can
     * safely follow; anything else is left out rather than linked.
     */
    private function safeSourceUrl(): ?string
    {
        $url = $this->source_url;

        return $url !== null && Str::startsWith(Str::lower($url), ['http://', 'https://']) ? $url : null;
    }
}
