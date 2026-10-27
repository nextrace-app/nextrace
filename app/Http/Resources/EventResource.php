<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Locale as Intl;

/**
 * An event in the shape the events pages render it: text in the locale of the
 * request, and measurements as the strings the components print.
 *
 * The keys the first pages read (date, time, category, distance, ...) come from
 * the main race of the event; the races, links and the rest are added to them.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $main = $this->mainRace;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'description' => $this->translated('description'),
            'date' => $main?->starts_at->toDateString() ?? $this->starts_on->toDateString(),
            'time' => $main?->start_time_known ? $main->starts_at->format('H:i') : null,
            'city' => $this->city,
            'location' => $this->location(),
            'category' => $main?->category(),
            'distance' => $main?->formattedDistance(),
            'distance_meters' => $main?->distance_meters,
            'participants' => $this->participants,
            'image' => $this->image_url,
            'latitude' => $this->latitude === null ? null : (float) $this->latitude,
            'longitude' => $this->longitude === null ? null : (float) $this->longitude,
            'registration_url' => $this->registration_url ?? $main?->registration_url,

            'edition_number' => $this->edition_number,
            'ends_on' => $this->ends_on?->toDateString(),
            'status' => $this->status->value,
            'date_confirmed' => $this->date_confirmed,
            'venue' => $this->translatedOrNull('venue'),
            'registration' => [
                'status' => $this->registration_status->value,
                'opens_at' => $this->registration_opens_at?->format('Y-m-d H:i:s'),
                'closes_at' => $this->registration_closes_at?->format('Y-m-d H:i:s'),
                'url' => $this->registration_url,
            ],
            'contact' => [
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ],
            'series' => $this->series === null ? null : [
                'slug' => $this->series->slug,
                'name' => $this->series->translated('name'),
            ],
            'organizer' => $this->organizer === null ? null : [
                'name' => $this->organizer->translated('name'),
                'email' => $this->organizer->email,
                'phone' => $this->organizer->phone,
                'website' => $this->organizer->website,
            ],
            'region' => $this->region === null ? null : [
                'slug' => $this->region->slug,
                'name' => $this->region->translated('name'),
            ],
            'races' => $this->races->map(fn (Race $race) => $this->race($race))->all(),
            'links' => $this->links->map(fn ($link) => [
                'type' => $link->type->value,
                'url' => $link->url,
                'label' => $link->translatedOrNull('label'),
            ])->all(),
        ];
    }

    /**
     * One race of the event.
     *
     * @return array<string, mixed>
     */
    private function race(Race $race): array
    {
        return [
            'id' => $race->id,
            'slug' => $race->slug,
            'name' => $race->translated('name'),
            'discipline' => $race->discipline->value,
            'terrain' => $race->terrain?->value,
            'distance_class' => $race->distance_class?->value,
            'distance' => $race->formattedDistance(),
            'distance_meters' => $race->distance_meters,
            'elevation_gain_m' => $race->elevation_gain_m,
            'team_size' => $race->team_size,
            'date' => $race->starts_at->toDateString(),
            'time' => $race->start_time_known ? $race->starts_at->format('H:i') : null,
            'start_location' => $race->translatedOrNull('start_location'),
            'finish_location' => $race->translatedOrNull('finish_location'),
            'time_limit_minutes' => $race->time_limit_minutes,
            'min_age' => $race->min_age,
            'participant_limit' => $race->participant_limit,
            'is_competitive' => $race->is_competitive,
            'certification' => $race->certification?->value,
            'awards' => $race->awards,
            'route_url' => $race->route_url,
            'registration_url' => $race->registration_url,
            'status' => $race->status->value,
            'is_main' => $race->is_main,
            'prices' => $race->prices->map(fn ($price) => [
                'label' => $price->translatedOrNull('label'),
                'amount' => $price->amount_cents / 100,
                'currency' => $price->currency,
                'valid_from' => $price->valid_from?->toDateString(),
                'valid_until' => $price->valid_until?->toDateString(),
            ])->all(),
        ];
    }

    /**
     * The place of the race, with the country named in the locale of the request.
     */
    private function location(): string
    {
        $country = Intl::getDisplayRegion("-{$this->country}", app()->getLocale());

        return "{$this->city}, {$country}";
    }
}
