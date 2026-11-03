<?php

namespace App\Support;

use App\Enums\DistanceClass;
use App\Models\Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Writes the races, prices and links of an event from the admin form, the way
 * the importer does: races are matched by slug, the ones missing from the form
 * are removed, and prices and links are replaced.
 */
class EventContent
{
    /**
     * @param  list<array<string, mixed>>  $races
     * @param  list<array<string, mixed>>  $links
     */
    public static function sync(Event $event, array $races, array $links): void
    {
        foreach ($races as $position => $data) {
            $startsAt = Carbon::parse($data['starts_at'], 'Europe/Athens');

            $attributes = Arr::only($data, [
                'name', 'discipline', 'terrain', 'distance_meters', 'elevation_gain_m', 'status', 'registration_url',
            ]);
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
        }

        $event->races()->whereNotIn('slug', array_column($races, 'slug'))->get()->each->delete();

        $event->links()->delete();

        foreach ($links as $position => $link) {
            $event->links()->create([...Arr::only($link, ['type', 'url', 'label']), 'sort_order' => $position]);
        }
    }
}
