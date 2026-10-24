<?php

namespace App\Models;

use App\Enums\LinkType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\EventLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A page elsewhere about an event: its website, regulations, results.
 *
 * @property int $id
 * @property int $event_id
 * @property LinkType $type
 * @property string $url
 * @property array<string, string>|null $label
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['event_id', 'type', 'url', 'label', 'sort_order'])]
class EventLink extends Model
{
    /** @use HasFactory<EventLinkFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * The event the link belongs to.
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
            'type' => LinkType::class,
            'label' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
