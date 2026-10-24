<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A place events are filed under, nested: Greece, Crete, Heraklion.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string> $name
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'parent_id'])]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * The region this one lies in.
     *
     * @return BelongsTo<Region, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * The regions directly inside this one.
     *
     * @return HasMany<Region, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * The events held directly in this region.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * The ids of this region and of every region nested inside it.
     *
     * @return list<int>
     */
    public function withDescendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];

        while ($level !== []) {
            $level = self::query()->whereIn('parent_id', $level)->pluck('id')->all();
            $ids = [...$ids, ...$level];
        }

        return array_values($ids);
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
            'parent_id' => 'integer',
        ];
    }
}
