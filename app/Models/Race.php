<?php

namespace App\Models;

use App\Enums\Certification;
use App\Enums\DistanceClass;
use App\Enums\RaceDiscipline;
use App\Enums\RaceStatus;
use App\Enums\RaceTerrain;
use App\Models\Concerns\HasTranslations;
use Database\Factories\RaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One of the parallel races (distances) held within an event.
 *
 * Exactly one race of an event is its main race. The model keeps it so: the
 * first race of an event becomes the main one, marking another race as main
 * demotes the previous one, and deleting the main race promotes the next.
 *
 * @property int $id
 * @property int $event_id
 * @property string $slug
 * @property array<string, string> $name
 * @property int $sort_order
 * @property bool $is_main
 * @property RaceDiscipline $discipline
 * @property RaceTerrain|null $terrain
 * @property DistanceClass|null $distance_class
 * @property int $distance_meters
 * @property int|null $elevation_gain_m
 * @property int|null $team_size
 * @property Carbon $starts_at
 * @property bool $start_time_known
 * @property array<string, string>|null $start_location
 * @property array<string, string>|null $finish_location
 * @property string|null $start_latitude
 * @property string|null $start_longitude
 * @property int|null $time_limit_minutes
 * @property int|null $min_age
 * @property int|null $participant_limit
 * @property bool $is_competitive
 * @property Certification|null $certification
 * @property string|null $timing_provider
 * @property array<string, mixed>|null $awards
 * @property string|null $route_url
 * @property string|null $registration_url
 * @property RaceStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'event_id',
    'slug',
    'name',
    'sort_order',
    'is_main',
    'discipline',
    'terrain',
    'distance_class',
    'distance_meters',
    'elevation_gain_m',
    'team_size',
    'starts_at',
    'start_time_known',
    'start_location',
    'finish_location',
    'start_latitude',
    'start_longitude',
    'time_limit_minutes',
    'min_age',
    'participant_limit',
    'is_competitive',
    'certification',
    'timing_provider',
    'awards',
    'route_url',
    'registration_url',
    'status',
])]
class Race extends Model
{
    /** @use HasFactory<RaceFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_main' => false,
        'discipline' => 'run',
        'start_time_known' => true,
        'is_competitive' => true,
        'status' => 'scheduled',
    ];

    protected static function booted(): void
    {
        static::creating(function (Race $race): void {
            if (! $race->is_main && ! self::mainRaceExists($race->event_id)) {
                $race->is_main = true;
            }
        });

        static::saved(function (Race $race): void {
            if ($race->is_main) {
                static::query()
                    ->where('event_id', $race->event_id)
                    ->whereKeyNot($race->getKey())
                    ->where('is_main', true)
                    ->update(['is_main' => false]);
            }
        });

        static::deleted(function (Race $race): void {
            if ($race->is_main && ! self::mainRaceExists($race->event_id)) {
                static::query()
                    ->where('event_id', $race->event_id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->limit(1)
                    ->get()
                    ->each(fn (Race $next) => $next->update(['is_main' => true]));
            }
        });
    }

    private static function mainRaceExists(int $eventId): bool
    {
        return static::query()->where('event_id', $eventId)->where('is_main', true)->exists();
    }

    /**
     * The event the race is part of.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * What entering the race costs, the cheapest period first.
     *
     * @return HasMany<RacePrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RacePrice::class)->orderBy('valid_from')->orderBy('id');
    }

    /**
     * The category the events pages group this race under: Trail, Ultra, 5K, ...
     */
    public function category(): string
    {
        if ($this->distance_class === DistanceClass::Ultra) {
            return 'Ultra';
        }

        if ($this->terrain === RaceTerrain::Trail) {
            return 'Trail';
        }

        return match ($this->distance_class) {
            DistanceClass::FiveK => '5K',
            DistanceClass::TenK => '10K',
            DistanceClass::HalfMarathon => 'Half Marathon',
            DistanceClass::Marathon => 'Marathon',
            DistanceClass::Kids => 'Kids',
            default => 'Other',
        };
    }

    /**
     * The distance in kilometres, without a trailing zero, e.g. '42.2 km' or '5 km'.
     */
    public function formattedDistance(): string
    {
        $kilometres = rtrim(rtrim(number_format($this->distance_meters / 1000, 1, '.', ''), '0'), '.');

        return "{$kilometres} km";
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
            'sort_order' => 'integer',
            'is_main' => 'boolean',
            'discipline' => RaceDiscipline::class,
            'terrain' => RaceTerrain::class,
            'distance_class' => DistanceClass::class,
            'distance_meters' => 'integer',
            'elevation_gain_m' => 'integer',
            'team_size' => 'integer',
            'starts_at' => 'datetime',
            'start_time_known' => 'boolean',
            'start_location' => 'array',
            'finish_location' => 'array',
            'time_limit_minutes' => 'integer',
            'min_age' => 'integer',
            'participant_limit' => 'integer',
            'is_competitive' => 'boolean',
            'certification' => Certification::class,
            'awards' => 'array',
            'status' => RaceStatus::class,
        ];
    }
}
