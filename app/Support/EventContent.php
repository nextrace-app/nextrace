<?php

namespace App\Support;

use App\Enums\DistanceClass;
use App\Models\Event;
use App\Models\Race;
use App\Models\RaceCourse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes the races, prices, checkpoints, links, key dates and practical
 * information of an event from the admin form, the way the importer does: races
 * are matched by slug, the ones missing from the form are removed, and the rest
 * is replaced.
 */
class EventContent
{
    /**
     * @param  list<array<string, mixed>>  $races
     * @param  list<array<string, mixed>>  $links
     * @param  list<array<string, mixed>>  $dates
     * @param  list<array<string, mixed>>  $sections
     */
    public static function sync(Event $event, array $races, array $links, array $dates = [], array $sections = []): void
    {
        foreach ($races as $position => $data) {
            $startsAt = Carbon::parse($data['starts_at'], 'Europe/Athens');

            $attributes = Arr::only($data, [
                'name', 'discipline', 'terrain', 'distance_meters', 'elevation_gain_m', 'status', 'registration_url',
                'start_location', 'finish_location', 'time_limit_minutes', 'min_age', 'participant_limit',
                'aid_stations', 'medical_certificate_required', 'route_url',
            ]);
            $attributes['sold_out'] = (bool) ($data['sold_out'] ?? false);
            $attributes['is_main'] = (bool) ($data['is_main'] ?? false);
            $attributes['sort_order'] = $position;
            $attributes['starts_at'] = $startsAt;
            $attributes['start_time_known'] = $startsAt->format('H:i:s') !== '00:00:00';
            $attributes['distance_class'] = DistanceClass::forMeters((int) $data['distance_meters'])->value;

            $race = $event->races()->firstOrNew(['slug' => $data['slug']]);
            $race->fill($attributes)->save();

            $race->prices()->delete();

            foreach ($data['prices'] ?? [] as $price) {
                $race->prices()->create(Arr::only($price, ['label', 'amount_cents', 'valid_from', 'valid_until']));
            }

            self::syncCourse($race, $data);

            $race->checkpoints()->delete();

            foreach ($data['checkpoints'] ?? [] as $checkpoint) {
                $race->checkpoints()->create([
                    'distance_meters' => $checkpoint['distance_meters'],
                    'closes_at' => Carbon::parse($checkpoint['closes_at'], 'Europe/Athens'),
                ]);
            }
        }

        $event->races()->whereNotIn('slug', array_column($races, 'slug'))->with('course')->get()->each(function (Race $race): void {
            self::deleteCourse($race);
            $race->delete();
        });

        $event->links()->delete();

        foreach ($links as $position => $link) {
            $event->links()->create([...Arr::only($link, ['type', 'url', 'label']), 'sort_order' => $position]);
        }

        $event->dates()->delete();

        foreach ($dates as $position => $date) {
            $event->dates()->create([
                ...Arr::only($date, ['type', 'starts_on', 'label']),
                'ends_on' => ($date['ends_on'] ?? null) ?: null,
                'sort_order' => $position,
            ]);
        }

        $event->sections()->delete();

        foreach ($sections as $position => $section) {
            $event->sections()->create([...Arr::only($section, ['type', 'title', 'body']), 'sort_order' => $position]);
        }
    }

    /**
     * Store a newly uploaded GPX file with the course read from it, replacing
     * the previous one, or remove the course when the form asks to.
     *
     * @param  array<string, mixed>  $data
     */
    private static function syncCourse(Race $race, array $data): void
    {
        $file = $data['gpx'] ?? null;
        $course = $data['course'] ?? null;

        if ($file instanceof UploadedFile && is_array($course)) {
            $previous = $race->course;
            $path = $file->storeAs('gpx', "{$race->id}-".Str::lower(Str::random(12)).'.gpx', RaceCourse::DISK);

            $race->course()->updateOrCreate([], [
                ...$course,
                'gpx_path' => $path,
                'gpx_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            ]);

            if ($previous !== null && $previous->gpx_path !== $path) {
                Storage::disk(RaceCourse::DISK)->delete($previous->gpx_path);
            }

            return;
        }

        if (filter_var($data['remove_gpx'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            self::deleteCourse($race);
        }
    }

    /**
     * Remove the course of a race together with its file.
     */
    private static function deleteCourse(Race $race): void
    {
        if ($race->course !== null) {
            Storage::disk(RaceCourse::DISK)->delete($race->course->gpx_path);
            $race->course->delete();
            $race->unsetRelation('course');
        }
    }
}
