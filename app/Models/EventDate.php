<?php

namespace App\Models;

use App\Enums\KeyDateType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\EventDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An important date of an event: the expo, bib pickup, the last day for
 * changes. A day, or a span of days when it has an end date.
 *
 * @property int $id
 * @property int $event_id
 * @property KeyDateType $type
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property array<string, string>|null $label
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['event_id', 'type', 'starts_on', 'ends_on', 'label', 'sort_order'])]
class EventDate extends Model
{
    /** @use HasFactory<EventDateFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * The event the date belongs to.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => KeyDateType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'label' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
