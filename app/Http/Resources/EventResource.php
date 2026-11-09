<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\EventSection;
use App\Models\Race;
use App\Models\RacePrice;
use App\Support\Cutoffs;
use App\Support\KeyDates;
use App\Support\RaceRegistration;
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
            'dates' => $this->dateSpan(),
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
            'races' => $races = $this->races->map(fn (Race $race) => $this->race($race))->all(),
            'registration_state' => $this->registrationState($races),
            'key_dates' => KeyDates::for($this->resource, RaceRegistration::today()),
            'sections' => $this->sections(),
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
        $today = RaceRegistration::today();
        $state = RaceRegistration::status($race, $this->resource, $today);
        // A tier is only "current" while the race can actually be entered.
        $current = in_array($state, [RaceRegistration::OPEN, RaceRegistration::FREE], true)
            ? RaceRegistration::currentPrice($race, $today)
            : null;

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
            'aid_stations' => $race->aid_stations,
            // A race without its own answer follows the event.
            'medical_certificate_required' => $race->medical_certificate_required ?? $this->medical_certificate_required,
            'participant_limit' => $race->participant_limit,
            'is_competitive' => $race->is_competitive,
            'certification' => $race->certification?->value,
            'awards' => $race->awards,
            'route_url' => $race->route_url,
            'registration_url' => $race->registration_url,
            'status' => $race->status->value,
            'is_main' => $race->is_main,
            'sold_out' => $race->sold_out,
            'registration_state' => $state,
            'price_rises_after' => $state === RaceRegistration::OPEN ? RaceRegistration::priceRisesAfter($race, $today) : null,
            // Only the event page loads the checkpoints; the lists have no table.
            'cutoffs' => $race->relationLoaded('checkpoints') ? Cutoffs::for($race) : [],
            'prices' => $race->prices->map(fn (RacePrice $price) => [
                'label' => $price->translatedOrNull('label'),
                'amount' => $price->amount_cents / 100,
                'currency' => $price->currency,
                'valid_from' => $price->valid_from?->toDateString(),
                'valid_until' => $price->valid_until?->toDateString(),
                'state' => RaceRegistration::priceState($price, $current, $today),
            ])->all(),
        ];
    }

    /**
     * The practical information, in its order, without the sections that have
     * no text in any language. Only the event page loads it.
     *
     * @return array<int, array{type: string, title: string|null, body_html: string}>
     */
    private function sections(): array
    {
        if (! $this->relationLoaded('sections')) {
            return [];
        }

        return $this->sections
            ->map(fn (EventSection $section) => [
                'type' => $section->type->value,
                'title' => $section->translatedOrNull('title'),
                'body_html' => $section->bodyHtml(),
            ])
            ->filter(fn (array $section) => $section['body_html'] !== null)
            ->values()
            ->all();
    }

    /**
     * Where the registration of the event stands: the most useful state any of
     * its races is in, and whether one of the open ones gets dearer soon.
     *
     * @param  array<int, array<string, mixed>>  $races
     * @return array{state: string, price_rises_soon: bool}
     */
    private function registrationState(array $races): array
    {
        $states = array_column($races, 'registration_state');
        $state = collect(RaceRegistration::PRIORITY)->first(fn (string $state) => in_array($state, $states, true)) ?? RaceRegistration::UNKNOWN;

        return [
            'state' => $state,
            'price_rises_soon' => collect($races)->contains(fn (array $race) => $race['registration_state'] === RaceRegistration::OPEN && $race['price_rises_after'] !== null),
        ];
    }

    /**
     * The days the event takes: from its first race to its last race or end
     * date. "to" is null when everything happens on one day.
     *
     * @return array{from: string, to: string|null}
     */
    private function dateSpan(): array
    {
        $days = $this->races->map(fn (Race $race) => $race->starts_at->toDateString())
            ->push($this->starts_on->toDateString())
            ->when($this->ends_on !== null, fn ($days) => $days->push($this->ends_on->toDateString()))
            ->sort()
            ->values();

        $from = $days->first();
        $to = $days->last();

        return ['from' => $from, 'to' => $to === $from ? null : $to];
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
