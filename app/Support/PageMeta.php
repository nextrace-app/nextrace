<?php

namespace App\Support;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Locale as Intl;

/**
 * What search engines and link previews read about the current page: title,
 * description, robots directive, Open Graph image and schema.org JSON-LD.
 *
 * Rendered by the root template on the server, so it is present in the first
 * response whether or not the page is also server rendered by Inertia.
 */
class PageMeta
{
    /**
     * The first path segments of pages that belong to a visitor's account or to
     * authentication, which have nothing to offer a search engine.
     *
     * @var list<string>
     */
    public const PRIVATE_SEGMENTS = [
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'profile',
        'settings',
        'dashboard',
        'favorites',
        'two-factor-challenge',
        'user',
        'email',
        'passkeys',
    ];

    private ?string $title = null;

    private ?string $description = null;

    private string $type = 'website';

    private ?string $image = null;

    /** @var array<string, mixed>|null */
    private ?array $jsonLd = null;

    /**
     * Describe the page of a single event from its translated attributes.
     */
    public function forEvent(Event $event): static
    {
        $locale = app()->getLocale();
        $name = $event->translated('name', $locale);
        $distance = $this->distance($event);
        $date = $event->starts_at->locale($locale)->isoFormat('LL');
        $country = Intl::getDisplayRegion("-{$event->country}", $locale);
        $details = Str::of($event->translated('description', $locale))->squish()->limit(110, '…', true)->toString();

        $this->title = __('seo.event.title', ['name' => $name, 'distance' => $distance, 'city' => $event->city]);
        $this->description = $details === ''
            ? __('seo.event.description', ['name' => $name, 'distance' => $distance, 'city' => $event->city, 'country' => $country, 'date' => $date])
            : __('seo.event.description_with_details', ['name' => $name, 'distance' => $distance, 'city' => $event->city, 'date' => $date, 'details' => $details]);
        $this->type = 'article';
        $this->image = $event->image_url;
        $this->jsonLd = $this->eventJsonLd($event, $name, $country);

        return $this;
    }

    /**
     * Everything the root template needs, with defaults for pages that set nothing.
     *
     * @return array{title: string|null, description: string, type: string, image: string|null, robots: string, locale: string, localeAlternates: list<string>, siteName: string, twitterCard: string, jsonLd: string|null}
     */
    public function toArray(): array
    {
        $key = $this->pageKey();
        $locale = app()->getLocale();
        $ogLocales = __('seo.og_locales');

        return [
            'title' => $this->title ?? ($key ? __("seo.{$key}.title") : null),
            'description' => $this->description ?? ($key ? __("seo.{$key}.description") : __('seo.default_description')),
            'type' => $this->type,
            'image' => $this->image,
            'robots' => $this->isPrivate() ? 'noindex, nofollow' : 'index, follow',
            'locale' => $ogLocales[$locale] ?? $locale,
            'localeAlternates' => array_values(array_map(
                fn (string $other) => $ogLocales[$other] ?? $other,
                array_diff(config('app.supported_locales'), [$locale]),
            )),
            'siteName' => config('app.name'),
            'twitterCard' => $this->image ? 'summary_large_image' : 'summary',
            'jsonLd' => $this->jsonLd === null ? null : self::encode($this->jsonLd),
        ];
    }

    /**
     * Encode JSON-LD so it is safe inside a script element: the angle brackets
     * and ampersands are escaped, so no value can close the tag.
     *
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    /**
     * A race start or end as schema.org reads it: a plain date when no time of
     * day is known, otherwise ISO 8601 with the Europe/Athens offset.
     *
     * Event times are stored as the wall-clock time at the race.
     */
    public static function schemaDate(CarbonInterface $date): string
    {
        if ($date->format('H:i:s') === '00:00:00') {
            return $date->toDateString();
        }

        return Carbon::parse($date->format('Y-m-d H:i:s'), 'Europe/Athens')->toIso8601String();
    }

    /**
     * The translation key of a page with fixed copy, if it is one.
     */
    private function pageKey(): ?string
    {
        $name = request()->route()?->getName() ?? '';
        $name = Str::after($name, Locale::routeName('', app()->getLocale()));

        return match ($name) {
            'home' => 'home',
            'events.index' => 'events',
            default => null,
        };
    }

    private function isPrivate(): bool
    {
        return in_array(explode('/', trim(Locale::unprefixedPath(request()), '/'))[0], self::PRIVATE_SEGMENTS, true);
    }

    private function distance(Event $event): string
    {
        $kilometres = rtrim(rtrim(number_format($event->distance_meters / 1000, 1, '.', ''), '0'), '.');

        return "{$kilometres} km";
    }

    /**
     * @return array<string, mixed>
     */
    private function eventJsonLd(Event $event, string $name, string $country): array
    {
        $locale = app()->getLocale();
        $url = Locale::alternates(request())[$locale];

        $address = array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $event->city,
            'addressCountry' => $event->country,
        ]);

        $location = [
            '@type' => 'Place',
            'name' => "{$event->city}, {$country}",
            'address' => $address,
        ];

        if ($event->latitude !== null && $event->longitude !== null) {
            $location['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $event->latitude,
                'longitude' => (float) $event->longitude,
            ];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => $name,
            'description' => $this->description,
            'startDate' => self::schemaDate($event->starts_at),
            'endDate' => $event->ends_at ? self::schemaDate($event->ends_at) : null,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $location,
            'image' => $event->image_url,
            'url' => $url,
            'inLanguage' => $locale,
            'offers' => $event->registration_url ? [
                '@type' => 'Offer',
                'url' => $event->registration_url,
            ] : null,
        ];

        return array_filter($data, fn (mixed $value) => $value !== null);
    }
}
