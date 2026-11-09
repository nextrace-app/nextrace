<?php

namespace App\Models;

use Database\Factories\RaceCheckpointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A cut-off of a race: the point along the course, and the wall-clock moment
 * after which runners who have not reached it are stopped. A checkpoint at the
 * full distance of the race is the finish.
 *
 * @property int $id
 * @property int $race_id
 * @property int $distance_meters
 * @property Carbon $closes_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['race_id', 'distance_meters', 'closes_at'])]
class RaceCheckpoint extends Model
{
    /** @use HasFactory<RaceCheckpointFactory> */
    use HasFactory;

    /**
     * The race the checkpoint belongs to.
     *
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distance_meters' => 'integer',
            'closes_at' => 'datetime',
        ];
    }
}
