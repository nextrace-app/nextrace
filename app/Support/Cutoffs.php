<?php

namespace App\Support;

use App\Models\Race;
use App\Models\RaceCheckpoint;

/**
 * The cut-off table of a race: the checkpoints entered for it, nearest to the
 * start first, each with the wall-clock moment it closes.
 *
 * A checkpoint at (or past) the full distance is the finish. When checkpoints
 * were entered but none is the finish, and the race has a known start time and
 * a time limit, the finish is added from them. A race without checkpoints has
 * no table: its time limit is shown with the other facts.
 */
class Cutoffs
{
    /**
     * @return array<int, array{distance_meters: int, closes_at: string, is_finish: bool}>
     */
    public static function for(Race $race): array
    {
        $rows = $race->checkpoints->map(fn (RaceCheckpoint $checkpoint) => [
            'distance_meters' => $checkpoint->distance_meters,
            'closes_at' => $checkpoint->closes_at->format('Y-m-d H:i'),
            'is_finish' => $checkpoint->distance_meters >= $race->distance_meters,
        ]);

        if ($rows->isEmpty()) {
            return [];
        }

        if (! $rows->contains('is_finish', true) && $race->start_time_known && $race->time_limit_minutes !== null) {
            $rows->push([
                'distance_meters' => $race->distance_meters,
                'closes_at' => $race->starts_at->copy()->addMinutes($race->time_limit_minutes)->format('Y-m-d H:i'),
                'is_finish' => true,
            ]);
        }

        return $rows->values()->all();
    }
}
