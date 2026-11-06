<?php

namespace App\Models;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An editorial post: news, a guide, results or an interview.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $title
 * @property array<string, string>|null $excerpt
 * @property array<string, string> $body
 * @property PostCategory $category
 * @property PostStatus $status
 * @property int|null $author_id
 * @property string|null $cover_image_url
 * @property array<string, string>|null $cover_image_alt
 * @property array<string, string>|null $meta_title
 * @property array<string, string>|null $meta_description
 * @property string|null $source_url
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug',
    'title',
    'excerpt',
    'body',
    'category',
    'status',
    'author_id',
    'cover_image_url',
    'cover_image_alt',
    'meta_title',
    'meta_description',
    'source_url',
    'published_at',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Posts are addressed by their slug in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The events the post is about.
     *
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }

    /**
     * Restrict the query to the posts visitors are allowed to see: published
     * ones whose publication date has come.
     *
     * @param  Builder<Post>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('posts.status', PostStatus::Published)
            ->where('posts.published_at', '<=', now());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'body' => 'array',
            'category' => PostCategory::class,
            'status' => PostStatus::class,
            'cover_image_alt' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
