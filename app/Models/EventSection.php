<?php

namespace App\Models;

use App\Enums\SectionType;
use App\Models\Concerns\HasTranslations;
use App\Support\SafeMarkdown;
use Database\Factories\EventSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One section of the practical information of an event: getting to the start,
 * bag drop, road closures. The body is Markdown; the title is optional unless
 * the type is "other", since the type names the section.
 *
 * @property int $id
 * @property int $event_id
 * @property SectionType $type
 * @property array<string, string>|null $title
 * @property array<string, string> $body
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['event_id', 'type', 'title', 'body', 'sort_order'])]
class EventSection extends Model
{
    /** @use HasFactory<EventSectionFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * The event the section belongs to.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The body in the current locale rendered to safe HTML, or null when the
     * section has no text.
     */
    public function bodyHtml(): ?string
    {
        $body = trim($this->translated('body'));

        return $body === '' ? null : SafeMarkdown::toHtml($body);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'title' => 'array',
            'body' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
