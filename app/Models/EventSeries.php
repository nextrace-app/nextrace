<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\EventSeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A recurring race whose yearly editions are events.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property array<string, string>|null $description
 * @property int|null $organizer_id
 * @property string|null $logo_url
 * @property string|null $website
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'description', 'organizer_id', 'logo_url', 'website'])]
class EventSeries extends Model
{
    /** @use HasFactory<EventSeriesFactory> */
    use HasFactory;

    use HasTranslations;

    protected $table = 'event_series';

    /**
     * The organizer of the series.
     *
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * The editions of the series, the latest first.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'series_id')->orderByDesc('starts_on');
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
        ];
    }
}
