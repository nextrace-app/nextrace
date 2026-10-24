<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Locale as Intl;

/**
 * An event in the shape the events pages render it: text in the locale of the
 * request, and measurements as the strings the components print.
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
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'description' => $this->translated('description'),
            'date' => $this->starts_at->toDateString(),
            'time' => $this->starts_at->format('H:i') === '00:00' ? null : $this->starts_at->format('H:i'),
            'city' => $this->city,
            'location' => $this->location(),
            'category' => $this->category,
            'distance' => $this->distance(),
            'distance_meters' => $this->distance_meters,
            'participants' => $this->participants,
            'image' => $this->image_url,
            'latitude' => $this->latitude === null ? null : (float) $this->latitude,
            'longitude' => $this->longitude === null ? null : (float) $this->longitude,
            'registration_url' => $this->registration_url,
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

    /**
     * The distance of the race in kilometres, without a trailing zero, e.g. '42.2 km' or '5 km'.
     */
    private function distance(): string
    {
        $kilometres = rtrim(rtrim(number_format($this->distance_meters / 1000, 1, '.', ''), '0'), '.');

        return "{$kilometres} km";
    }
}
