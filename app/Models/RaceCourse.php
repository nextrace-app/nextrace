<?php

namespace App\Models;

use Database\Factories\RaceCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The course of a race from its GPX file: the file itself on the public disk,
 * and what the event page draws from it, worked out once on upload.
 *
 * @property int $id
 * @property int $race_id
 * @property string $gpx_path
 * @property string $gpx_name
 * @property int $distance_meters
 * @property int|null $elevation_gain_m
 * @property int|null $elevation_loss_m
 * @property int|null $highest_point_m
 * @property list<array{0: float, 1: float}> $track
 * @property list<array{0: float, 1: int}>|null $profile
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['race_id', 'gpx_path', 'gpx_name', 'distance_meters', 'elevation_gain_m', 'elevation_loss_m', 'highest_point_m', 'track', 'profile'])]
class RaceCourse extends Model
{
    /** @use HasFactory<RaceCourseFactory> */
    use HasFactory;

    /** The disk the GPX files live on. */
    public const DISK = 'public';

    /**
     * The race the course belongs to.
     *
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * The public address of the GPX file.
     */
    public function gpxUrl(): string
    {
        return Storage::disk(self::DISK)->url($this->gpx_path);
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
            'elevation_gain_m' => 'integer',
            'elevation_loss_m' => 'integer',
            'highest_point_m' => 'integer',
            'track' => 'array',
            'profile' => 'array',
        ];
    }
}
