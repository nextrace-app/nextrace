<?php

namespace App\Models;

use Database\Factories\FeatureFlagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A feature flag managed in the admin: a key the code checks, and whether it
 * is on for everyone. A key without a row is off. See App\Support\Features.
 *
 * @property int $id
 * @property string $key
 * @property string $description
 * @property bool $is_active
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'description', 'is_active', 'updated_by'])]
class FeatureFlag extends Model
{
    /** @use HasFactory<FeatureFlagFactory> */
    use HasFactory;

    /**
     * The key the code checks; it never changes after the flag is created.
     */
    public const KEY_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Flags are addressed by their key in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * The user who changed the flag last.
     *
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
