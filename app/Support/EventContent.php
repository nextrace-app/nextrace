<?php

namespace App\Support;

use App\Enums\DistanceClass;
use App\Models\Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Writes the races, prices, links and key dates of an event from the admin
 * form, the way the importer does: races are matched by slug, the ones missing
 * from the form are removed, and prices, links and dates are replaced.
 */
class EventContent
{
    /**
     * @param  list<array<string, mixed>>  $races
     * @param  list<array<string, mixed>>  $links
     * @param  list<array<string, mixed>>  $dates
     */
    public static function sync(Event $event, array $races, array $links, array $dates = []): void
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
        }

        $event->races()->whereNotIn('slug', array_column($races, 'slug'))->get()->each->delete();

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
    }
}
