<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Concerns\HasTranslations;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A yearly edition of a race event: it holds one or several parallel races.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property array<string, string> $description
 * @property int|null $series_id
 * @property int|null $organizer_id
 * @property int|null $region_id
 * @property int|null $edition_number
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property string $timezone
 * @property EventStatus $status
 * @property bool $date_confirmed
 * @property string $city
 * @property string $country
 * @property array<string, string>|null $venue
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $participants
 * @property string|null $image_url
 * @property string|null $registration_url
 * @property RegistrationStatus $registration_status
 * @property Carbon|null $registration_opens_at
 * @property Carbon|null $registration_closes_at
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $results_url
 * @property array<string, string>|null $package_description
 * @property array<string, string>|null $cancellation_policy
 * @property bool $medical_certificate_required
 * @property string|null $source_url
 * @property Carbon|null $source_verified_at
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'slug',
    'name',
    'description',
    'series_id',
    'organizer_id',
    'region_id',
    'edition_number',
    'starts_on',
    'ends_on',
    'timezone',
    'status',
    'date_confirmed',
    'city',
    'country',
    'venue',
    'address',
    'latitude',
    'longitude',
    'participants',
    'image_url',
    'registration_url',
    'registration_status',
    'registration_opens_at',
    'registration_closes_at',
    'contact_email',
    'contact_phone',
    'results_url',
    'package_description',
    'cancellation_policy',
    'medical_certificate_required',
    'source_url',
    'source_verified_at',
    'published_at',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'Europe/Athens',
        'status' => 'scheduled',
        'date_confirmed' => true,
        'registration_status' => 'unknown',
        'medical_certificate_required' => false,
    ];

    /**
     * Events are addressed by their slug in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The series this event is an edition of.
     *
     * @return BelongsTo<EventSeries, $this>
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(EventSeries::class, 'series_id');
    }

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * The races of the event in the order they are shown.
     *
     * @return HasMany<Race, $this>
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The race the event is known by: the longest or most important one.
     *
     * @return HasOne<Race, $this>
     */
    public function mainRace(): HasOne
    {
        return $this->hasOne(Race::class)->where('is_main', true);
    }

    /**
     * @return HasMany<EventLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(EventLink::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * What entering the races of the event costs.
     *
     * @return HasManyThrough<RacePrice, Race, $this>
     */
    public function prices(): HasManyThrough
    {
        return $this->hasManyThrough(RacePrice::class, Race::class);
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
     * Restrict the query to the events whose last day is today or later in
     * Greece, so an event stays listed until it is over, today included.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->whereRaw('coalesce(events.ends_on, events.starts_on) >= ?', [
            Carbon::now('Europe/Athens')->toDateString(),
        ]);
    }

    /**
     * Restrict the query to the events held in a region or anywhere inside it.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function inRegion(Builder $query, Region|int $region): void
    {
        $region = $region instanceof Region ? $region : Region::query()->findOrFail($region);

        $query->whereIn('events.region_id', $region->withDescendantIds());
    }

    /**
     * Order by the day of the event, then by the start of its main race.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function chronological(Builder $query): void
    {
        $query->orderBy('events.starts_on')
            ->orderBy(
                Race::query()->select('starts_at')
                    ->whereColumn('races.event_id', 'events.id')
                    ->where('is_main', true)
                    ->limit(1)
            )
            ->orderBy('events.id');
    }

    /**
     * Load what EventResource renders.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function withDetails(Builder $query): void
    {
        $query->with(['mainRace', 'races.prices', 'links', 'series', 'organizer', 'region']);
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
            'edition_number' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => EventStatus::class,
            'date_confirmed' => 'boolean',
            'venue' => 'array',
            'participants' => 'integer',
            'registration_status' => RegistrationStatus::class,
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'package_description' => 'array',
            'cancellation_policy' => 'array',
            'medical_certificate_required' => 'boolean',
            'source_verified_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
