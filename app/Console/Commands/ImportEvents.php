<?php

namespace App\Console\Commands;

use App\Enums\Certification;
use App\Enums\DistanceClass;
use App\Enums\EventStatus;
use App\Enums\LinkType;
use App\Enums\RaceDiscipline;
use App\Enums\RaceStatus;
use App\Enums\RaceTerrain;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Organizer;
use App\Models\Region;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;
use RuntimeException;
use Throwable;

/**
 * Imports events with their races, prices and links from a nested JSON file.
 *
 * Every level is upserted by slug, so re-running it updates what it imported
 * before: series and organizers by slug, events by slug (or by one of their
 * previous_slugs, which renames them), races by event and slug. The races of an
 * event that are missing from the file are removed, and its links and prices
 * are replaced. Each event is written in one transaction and skipped with a
 * warning when it is invalid, so nothing is ever half written.
 *
 * New events are drafts. `published: true` publishes an event that is not yet
 * published; the importer never unpublishes one or changes its publication date.
 */
#[Signature('events:import {file=database/data/events.json : JSON file with the events to import}')]
#[Description('Import events and their races from a JSON file; new events are unpublished unless the file publishes them')]
class ImportEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->error('The file does not contain a JSON list of events.');

            return self::FAILURE;
        }

        $created = $updated = $skipped = 0;

        foreach ($rows as $index => $row) {
            $label = 'Skipped #'.($index + 1).' ('.(is_array($row) ? ($row['slug'] ?? 'no slug') : 'not an object').')';

            if (! is_array($row)) {
                $skipped++;
                $this->warn("{$label}: not an object.");

                continue;
            }

            $existing = $this->existingEvent($row);
            $validator = $this->validator($row, $existing === null);

            if ($validator->fails()) {
                $skipped++;
                $this->warn("{$label}: ".$validator->errors()->first());

                continue;
            }

            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            $this->warnAboutMissingTranslations($data);

            try {
                DB::transaction(fn () => $this->store($data, $existing));
            } catch (Throwable $exception) {
                $skipped++;
                $this->warn("{$label}: {$exception->getMessage()}");

                continue;
            }

            $existing === null ? $created++ : $updated++;
        }

        $this->info("Created {$created}, updated {$updated}, skipped {$skipped}. New events are unpublished unless the file publishes them.");

        return self::SUCCESS;
    }

    /**
     * The event already in the database for a row: under its slug, or else under one of its previous slugs.
     *
     * @param  array<string, mixed>  $row
     */
    private function existingEvent(array $row): ?Event
    {
        $slug = $row['slug'] ?? null;
        $previous = is_array($row['previous_slugs'] ?? null) ? array_filter($row['previous_slugs'], 'is_string') : [];

        if (! is_string($slug)) {
            return null;
        }

        return Event::query()->where('slug', $slug)->first()
            ?? Event::query()->whereIn('slug', $previous)->orderBy('id')->first();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validator(array $row, bool $isNew): ValidatorInstance
    {
        $validator = Validator::make($row, $this->rules());

        $validator->after(function (ValidatorInstance $validator) use ($row, $isNew): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var list<array<string, mixed>> $races */
            $races = $row['races'];

            $mains = array_filter($races, fn (array $race) => ($race['is_main'] ?? false) === true);

            if (count($mains) !== 1) {
                $validator->errors()->add('races', 'Exactly one race has to be the main race.');

                return;
            }

            if (count(array_unique(array_column($races, 'slug'))) !== count($races)) {
                $validator->errors()->add('races', 'Race slugs have to be unique within the event.');
            }

            // A new event has to be ahead of us; an existing one may keep a date that has passed.
            $main = array_values($mains)[0];

            if ($isNew && ! Carbon::parse($main['starts_at'], 'Europe/Athens')->isFuture()) {
                $validator->errors()->add('races', 'The main race has to start in the future.');
            }
        });

        return $validator;
    }

    /**
     * The rules every imported event has to satisfy.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255'],
            'previous_slugs' => ['nullable', 'array'],
            'previous_slugs.*' => ['string', 'max:255'],

            'series' => ['nullable', 'array'],
            'series.slug' => ['required_with:series', 'string', 'max:255'],
            'series.name' => ['required_with:series', 'array'],
            'series.name.en' => ['required_with:series', 'string'],
            'series.name.el' => ['nullable', 'string'],
            'series.organizer_slug' => ['nullable', 'string'],

            'organizer' => ['nullable', 'array'],
            'organizer.slug' => ['required_with:organizer', 'string', 'max:255'],
            'organizer.name' => ['required_with:organizer', 'array'],
            'organizer.name.en' => ['required_with:organizer', 'string'],
            'organizer.name.el' => ['nullable', 'string'],
            'organizer.email' => ['nullable', 'email'],
            'organizer.phone' => ['nullable', 'string', 'max:255'],
            'organizer.website' => ['nullable', 'url'],

            'region' => ['nullable', 'string', Rule::exists('regions', 'slug')],
            'edition_number' => ['nullable', 'integer', 'min:1', 'max:65535'],

            'name' => ['required', 'array'],
            'name.en' => ['required', 'string'],
            'name.el' => ['nullable', 'string'],
            'description' => ['required', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.el' => ['nullable', 'string'],

            'city' => ['required', 'string', 'max:255'],
            'venue' => ['nullable', 'array'],
            'venue.en' => ['nullable', 'string'],
            'venue.el' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:34,42'],
            'longitude' => ['nullable', 'numeric', 'between:19,30'],
            'date_confirmed' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(EventStatus::class)],

            'registration' => ['nullable', 'array'],
            'registration.status' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'registration.opens_at' => ['nullable', 'date'],
            'registration.closes_at' => ['nullable', 'date'],
            'registration.url' => ['nullable', 'url'],

            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'source_url' => ['required', 'url'],
            'published' => ['nullable', 'boolean'],

            'links' => ['nullable', 'array'],
            'links.*.type' => ['required', Rule::enum(LinkType::class)],
            'links.*.url' => ['required', 'url'],
            'links.*.label' => ['nullable', 'array'],

            'races' => ['required', 'array', 'min:1'],
            'races.*.slug' => ['required', 'string', 'max:255'],
            'races.*.name' => ['required', 'array'],
            'races.*.name.en' => ['required', 'string'],
            'races.*.name.el' => ['nullable', 'string'],
            'races.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'races.*.is_main' => ['nullable', 'boolean'],
            'races.*.discipline' => ['nullable', Rule::enum(RaceDiscipline::class)],
            'races.*.terrain' => ['nullable', Rule::enum(RaceTerrain::class)],
            'races.*.distance_class' => ['nullable', Rule::enum(DistanceClass::class)],
            'races.*.distance_meters' => ['required', 'integer', 'min:1'],
            'races.*.elevation_gain_m' => ['nullable', 'integer', 'min:0'],
            'races.*.team_size' => ['nullable', 'integer', 'min:1', 'max:255'],
            'races.*.starts_at' => ['required', 'date'],
            'races.*.start_time_known' => ['nullable', 'boolean'],
            'races.*.start_location' => ['nullable', 'array'],
            'races.*.finish_location' => ['nullable', 'array'],
            'races.*.time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'races.*.min_age' => ['nullable', 'integer', 'min:0', 'max:255'],
            'races.*.participant_limit' => ['nullable', 'integer', 'min:1'],
            'races.*.is_competitive' => ['nullable', 'boolean'],
            'races.*.certification' => ['nullable', Rule::enum(Certification::class)],
            'races.*.route_url' => ['nullable', 'url'],
            'races.*.registration_url' => ['nullable', 'url'],
            'races.*.status' => ['nullable', Rule::enum(RaceStatus::class)],
            'races.*.prices' => ['nullable', 'array'],
            'races.*.prices.*.label' => ['nullable', 'array'],
            'races.*.prices.*.amount_cents' => ['required', 'integer', 'min:0'],
            'races.*.prices.*.currency' => ['nullable', 'string', 'size:3'],
            'races.*.prices.*.valid_from' => ['nullable', 'date'],
            'races.*.prices.*.valid_until' => ['nullable', 'date'],
        ];
    }

    /**
     * Tell which translations the file still lacks.
     *
     * @param  array<string, mixed>  $data
     */
    private function warnAboutMissingTranslations(array $data): void
    {
        foreach (config('app.supported_locales') as $locale) {
            foreach (['name', 'description'] as $attribute) {
                if (! isset($data[$attribute][$locale])) {
                    $this->line("  {$data['slug']}: no {$locale} {$attribute}, needs a translation.");
                }
            }

            foreach ($data['races'] as $race) {
                if (! isset($race['name'][$locale])) {
                    $this->line("  {$data['slug']}/{$race['slug']}: no {$locale} race name, needs a translation.");
                }
            }
        }
    }

    /**
     * Write one validated event with everything under it.
     *
     * @param  array<string, mixed>  $data
     */
    private function store(array $data, ?Event $existing): void
    {
        $event = $existing ?? new Event;

        $attributes = Arr::only($data, [
            'slug', 'name', 'description', 'edition_number', 'city', 'venue', 'latitude', 'longitude',
            'date_confirmed', 'status', 'contact_email', 'contact_phone', 'source_url',
        ]);

        $attributes = $this->withoutNulls($attributes, ['status', 'date_confirmed']);

        if (isset($data['registration'])) {
            $registration = $data['registration'];

            foreach (['status' => 'registration_status', 'opens_at' => 'registration_opens_at', 'closes_at' => 'registration_closes_at', 'url' => 'registration_url'] as $key => $column) {
                if (($registration[$key] ?? null) !== null || ($key !== 'status' && array_key_exists($key, $registration))) {
                    $attributes[$column] = $registration[$key];
                }
            }
        }

        if (isset($data['organizer'])) {
            $attributes['organizer_id'] = $this->storeOrganizer($data['organizer'])->id;
        }

        if (isset($data['series'])) {
            $attributes['series_id'] = $this->storeSeries($data['series'], $attributes['organizer_id'] ?? null)->id;
        }

        if (isset($data['region'])) {
            $attributes['region_id'] = Region::query()->where('slug', $data['region'])->value('id');
        }

        $dates = array_map(fn (array $race) => Carbon::parse($race['starts_at'])->toDateString(), $data['races']);
        $firstDate = collect($dates)->min();
        $lastDate = collect($dates)->max();
        $attributes['starts_on'] = $firstDate;
        $attributes['ends_on'] = $lastDate > $firstDate ? $lastDate : null;

        $event->fill($attributes);

        // Publishing is one way: a published event keeps its date.
        if (($data['published'] ?? false) === true && $event->published_at === null) {
            $event->published_at = now();
        }

        $event->save();

        $this->storeRaces($event, $data['races']);

        $event->links()->delete();

        foreach (array_values($data['links'] ?? []) as $position => $link) {
            $event->links()->create([...$link, 'sort_order' => $position]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeOrganizer(array $data): Organizer
    {
        return Organizer::query()->updateOrCreate(
            ['slug' => $data['slug']],
            Arr::only($data, ['name', 'email', 'phone', 'website']),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeSeries(array $data, ?int $organizerId): EventSeries
    {
        $organizerSlug = $data['organizer_slug'] ?? null;

        $attributes = ['name' => $data['name']];

        if ($organizerSlug !== null) {
            $attributes['organizer_id'] = Organizer::query()->where('slug', $organizerSlug)->value('id')
                ?? throw new RuntimeException("Unknown organizer {$organizerSlug} for series {$data['slug']}.");
        } elseif ($organizerId !== null) {
            $attributes['organizer_id'] = $organizerId;
        }

        return EventSeries::query()->updateOrCreate(['slug' => $data['slug']], $attributes);
    }

    /**
     * Upsert the races of the event by slug and drop the ones the file no longer lists.
     *
     * @param  list<array<string, mixed>>  $races
     */
    private function storeRaces(Event $event, array $races): void
    {
        foreach ($races as $position => $data) {
            $startsAt = Carbon::parse($data['starts_at']);

            $attributes = Arr::only($data, [
                'name', 'discipline', 'terrain', 'distance_meters', 'elevation_gain_m', 'team_size',
                'start_location', 'finish_location', 'time_limit_minutes', 'min_age', 'participant_limit',
                'is_competitive', 'certification', 'route_url', 'registration_url', 'status',
            ]);

            $attributes = $this->withoutNulls($attributes, ['discipline', 'is_competitive', 'status']);
            $attributes['is_main'] = $data['is_main'] ?? false;
            $attributes['sort_order'] = $data['sort_order'] ?? $position;
            $attributes['starts_at'] = $startsAt;
            $attributes['start_time_known'] = $data['start_time_known'] ?? $startsAt->format('H:i:s') !== '00:00:00';
            $attributes['distance_class'] = $data['distance_class'] ?? $this->distanceClass($data['distance_meters'])->value;

            $race = $event->races()->firstOrNew(['slug' => $data['slug']]);
            $race->fill($attributes)->save();

            $race->prices()->delete();

            foreach ($data['prices'] ?? [] as $price) {
                $race->prices()->create($price);
            }
        }

        $event->races()->whereNotIn('slug', array_column($races, 'slug'))->get()->each->delete();
    }

    /**
     * Drop the listed keys when they are null, so a column that is never null keeps its default.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function withoutNulls(array $attributes, array $keys): array
    {
        return array_filter($attributes, fn (mixed $value, string $key) => $value !== null || ! in_array($key, $keys, true), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * The class a distance falls in, when the file does not say.
     */
    private function distanceClass(int $metres): DistanceClass
    {
        return match (true) {
            $metres === 5000 => DistanceClass::FiveK,
            $metres === 10000 => DistanceClass::TenK,
            in_array($metres, [21097, 21098, 21100], true) => DistanceClass::HalfMarathon,
            in_array($metres, [42195, 42200], true) => DistanceClass::Marathon,
            $metres > 42200 => DistanceClass::Ultra,
            default => DistanceClass::Other,
        };
    }
}
