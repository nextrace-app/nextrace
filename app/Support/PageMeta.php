<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Enums\RaceStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Race;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
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

    /** @var list<array<string, mixed>> */
    private array $jsonLd = [];

    /**
     * Describe the page of a single event from its translated attributes.
     */
    public function forEvent(Event $event): static
    {
        $locale = app()->getLocale();
        $name = $event->translated('name', $locale);
        $start = $event->mainRace->starts_at ?? $event->starts_on;
        $distance = $event->mainRace?->formattedDistance() ?? '';
        $date = $start->copy()->settings(['locale' => $locale])->isoFormat('LL');
        $country = Intl::getDisplayRegion("-{$event->country}", $locale) ?: $event->country;
        $details = Str::of($event->translated('description', $locale))->squish()->limit(110, '…', true)->toString();

        $this->title = __('seo.event.title', ['name' => $name, 'distance' => $distance, 'city' => $event->city]);
        $this->description = $details === ''
            ? __('seo.event.description', ['name' => $name, 'distance' => $distance, 'city' => $event->city, 'country' => $country, 'date' => $date])
            : __('seo.event.description_with_details', ['name' => $name, 'distance' => $distance, 'city' => $event->city, 'date' => $date, 'details' => $details]);

        if ($event->races->count() > 1) {
            $this->description .= ' '.trans_choice('seo.event.distances', $event->races->count(), ['count' => $event->races->count()]);
        }

        $this->type = 'article';
        $this->image = $event->image_url;
        $this->jsonLd = [
            $this->eventJsonLd($event, $name, $country, $start),
            $this->breadcrumbs([
                [__('seo.breadcrumbs.home'), route(Locale::routeName('home'))],
                [__('seo.breadcrumbs.events'), route(Locale::routeName('events.index'))],
                [$name, Locale::alternates(request())[$locale]],
            ]),
        ];

        return $this;
    }

    /**
     * Everything the root template needs, with defaults for pages that set nothing.
     *
     * @return array{title: string|null, description: string, type: string, image: string|null, robots: string, locale: string, localeAlternates: list<string>, siteName: string, twitterCard: string, jsonLd: list<string>}
     */
    public function toArray(): array
    {
        $key = $this->pageKey();
        $locale = app()->getLocale();

        return [
            'title' => $this->title ?? ($key ? Lang::string("seo.{$key}.title") : null),
            'description' => $this->description ?? ($key ? Lang::string("seo.{$key}.description") : Lang::string('seo.default_description')),
            'type' => $this->type,
            'image' => $this->image,
            'robots' => $this->robots(),
            'locale' => $this->openGraphLocale($locale),
            'localeAlternates' => array_values(array_map(
                fn (string $other) => $this->openGraphLocale($other),
                array_diff(config()->array('app.supported_locales'), [$locale]),
            )),
            'siteName' => config()->string('app.name'),
            'twitterCard' => $this->image ? 'summary_large_image' : 'summary',
            'jsonLd' => array_map(self::encode(...), $this->jsonLd ?: $this->pageJsonLd()),
        ];
    }

    /**
     * The Open Graph locale code of an application locale, e.g. 'en' becomes 'en_US'.
     */
    private function openGraphLocale(string $locale): string
    {
        $key = "seo.og_locales.{$locale}";

        return Lang::has($key) ? Lang::string($key) : $locale;
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

    /**
     * The robots directive: closed and account pages stay out of search results
     * entirely, and so do search results of the events list, whose number is
     * unbounded, though the events they link to may still be followed.
     */
    private function robots(): string
    {
        if (! config()->boolean('site.indexable') || $this->isPrivate()) {
            return 'noindex, nofollow';
        }

        return $this->isEventSearch() ? 'noindex, follow' : 'index, follow';
    }

    private function isEventSearch(): bool
    {
        return Locale::unprefixedPath(request()) === '/events' && trim((string) request()->query('q')) !== '';
    }

    private function isPrivate(): bool
    {
        return in_array(explode('/', trim(Locale::unprefixedPath(request()), '/'))[0], self::PRIVATE_SEGMENTS, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventJsonLd(Event $event, string $name, string $country, CarbonInterface $start): array
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
            'startDate' => self::schemaDate($start),
            'endDate' => $event->ends_on ? $event->ends_on->toDateString() : null,
            'eventStatus' => self::eventStatus($event->status),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $location,
            'image' => $event->image_url,
            'url' => $url,
            'inLanguage' => $locale,
            'offers' => $this->eventOffers($event),
            'organizer' => $this->organizerJsonLd($event),
            'subEvent' => $event->races->isEmpty() ? null : $event->races->map(fn (Race $race) => $this->raceJsonLd($event, $race, $name, $location, $url))->values()->all(),
        ];

        return array_filter($data, fn (mixed $value) => $value !== null);
    }

    /**
     * The structured data of a page with fixed copy: the site itself on the
     * home page and the trail of crumbs on the events list.
     *
     * @return list<array<string, mixed>>
     */
    private function pageJsonLd(): array
    {
        $home = route(Locale::routeName('home'));

        return match ($this->pageKey()) {
            'home' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => config()->string('app.name'),
                    'url' => $home,
                    'inLanguage' => app()->getLocale(),
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => config()->string('app.name'),
                    'url' => $home,
                    'logo' => url('/apple-touch-icon.png'),
                ],
            ],
            'events' => [
                $this->breadcrumbs([
                    [__('seo.breadcrumbs.home'), $home],
                    [__('seo.breadcrumbs.events'), route(Locale::routeName('events.index'))],
                ]),
            ],
            default => [],
        };
    }

    /**
     * A schema.org trail of crumbs, the last of which is the page itself.
     *
     * @param  list<array{0: string, 1: string}>  $crumbs  Name and absolute URL of each step
     * @return array<string, mixed>
     */
    private function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /**
     * What it costs to take part in the event and whether registration is open.
     *
     * The prices of the races are summed up into a range; each race lists its own
     * prices in its sub event. Without prices only the registration link and the
     * state of registration are known.
     *
     * @return array<string, mixed>|null
     */
    private function eventOffers(Event $event): ?array
    {
        $url = $event->registration_url ?? $event->mainRace?->registration_url;
        $prices = $event->races->flatMap(fn (Race $race) => $race->prices);
        $currency = $prices->first()?->currency;
        $prices = $prices->where('currency', $currency)->pluck('amount_cents');

        $availability = match ($event->registration_status) {
            RegistrationStatus::Open => 'https://schema.org/InStock',
            RegistrationStatus::NotOpen => 'https://schema.org/PreOrder',
            RegistrationStatus::SoldOut => 'https://schema.org/SoldOut',
            default => null,
        };

        $offer = [
            '@type' => $prices->isEmpty() ? 'Offer' : 'AggregateOffer',
            'url' => $url,
            'availability' => $availability,
            'validFrom' => $event->registration_opens_at?->toIso8601String(),
            'validThrough' => $event->registration_closes_at?->toIso8601String(),
        ];

        if ($prices->isNotEmpty()) {
            $offer += [
                'priceCurrency' => $currency,
                'lowPrice' => $prices->min() / 100,
                'highPrice' => $prices->max() / 100,
                'offerCount' => $prices->count(),
            ];
        }

        $offer = array_filter($offer, fn (mixed $value) => $value !== null && $value !== '');

        return count($offer) > 1 ? $offer : null;
    }

    /**
     * The schema.org event status of an event.
     */
    public static function eventStatus(EventStatus|RaceStatus $status): string
    {
        return match ($status->value) {
            'postponed' => 'https://schema.org/EventPostponed',
            'cancelled' => 'https://schema.org/EventCancelled',
            default => 'https://schema.org/EventScheduled',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function organizerJsonLd(Event $event): ?array
    {
        if ($event->organizer === null) {
            return null;
        }

        return array_filter([
            '@type' => 'Organization',
            'name' => $event->organizer->translated('name'),
            'url' => $event->organizer->website,
        ], fn (mixed $value) => $value !== null && $value !== '');
    }

    /**
     * One race of the event as a schema.org sub event.
     *
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    private function raceJsonLd(Event $event, Race $race, string $eventName, array $location, string $url): array
    {
        $locale = app()->getLocale();
        $registrationUrl = $race->registration_url ?? $event->registration_url;

        $offers = $race->prices->map(fn ($price) => array_filter([
            '@type' => 'Offer',
            'name' => $price->translatedOrNull('label', $locale),
            'price' => $price->amount_cents / 100,
            'priceCurrency' => $price->currency,
            'validFrom' => $price->valid_from?->toDateString(),
            'validThrough' => $price->valid_until?->toDateString(),
            'url' => $registrationUrl,
        ], fn (mixed $value) => $value !== null && $value !== ''))->values()->all();

        if ($offers === [] && $registrationUrl !== null) {
            $offers = [['@type' => 'Offer', 'url' => $registrationUrl]];
        }

        $data = [
            '@type' => 'SportsEvent',
            'name' => $race->translated('name', $locale),
            'description' => "{$eventName}: {$race->formattedDistance()}",
            'startDate' => self::schemaDate($race->starts_at),
            'eventStatus' => self::eventStatus($event->status === EventStatus::Scheduled ? $race->status : $event->status),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $location,
            'url' => $url,
            'offers' => $offers === [] ? null : $offers,
        ];

        return array_filter($data, fn (mixed $value) => $value !== null);
    }
}
