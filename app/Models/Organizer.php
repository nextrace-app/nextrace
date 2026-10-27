<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\OrganizerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The club or body that organises one or more events.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property array<string, string>|null $social
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'email', 'phone', 'website', 'social'])]
class Organizer extends Model
{
    /** @use HasFactory<OrganizerFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * The events this organizer puts on.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
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
            'social' => 'array',
        ];
    }
}
