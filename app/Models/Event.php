<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A race runners can find on the events pages.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property array<string, string> $description
 * @property string $category
 * @property int $distance_meters
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property string $city
 * @property string $country
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $participants
 * @property string|null $image_url
 * @property string|null $registration_url
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'slug',
    'name',
    'description',
    'category',
    'distance_meters',
    'starts_at',
    'ends_at',
    'city',
    'country',
    'latitude',
    'longitude',
    'participants',
    'image_url',
    'registration_url',
    'published_at',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Events are addressed by their slug in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The value of a translated attribute in the given locale, which defaults to
     * the one the application is running in, falling back to the fallback locale.
     */
    public function translated(string $attribute, ?string $locale = null): string
    {
        /** @var array<string, string> $values */
        $values = $this->getAttribute($attribute) ?? [];

        $locale ??= app()->getLocale();

        return $values[$locale] ?? $values[config('app.fallback_locale')] ?? '';
    }

    /**
     * Restrict the query to the events visitors are allowed to see.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /**
     * Whether the event is out of draft and shown on the events pages.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'distance_meters' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'participants' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
