<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\LinkType;
use App\Enums\RaceDiscipline;
use App\Enums\RaceStatus;
use App\Enums\RaceTerrain;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Organizer;
use App\Models\Region;
use App\Models\User;
use App\Support\EventContent;
use App\Support\Locale;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin CRUD of events and their moderation: an organizer files a draft and
 * submits it for review, and only someone who may publish puts it on the site.
 */
class EventController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Event::class);

        $user = $this->user($request);
        $search = trim($request->string('search')->toString());
        $state = $request->string('state')->toString();
        $regionId = $request->integer('region_id') ?: null;
        $organizerId = $request->integer('organizer_id') ?: null;

        $events = Event::query()
            ->with(['region', 'organizer'])
            ->when(! $this->canModerate($user), fn (Builder $query) => $query->where('organizer_id', $user->organizer_id ?? 0))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereLike('slug', "%{$search}%")
                ->orWhereLike('name->el', "%{$search}%")
                ->orWhereLike('name->en', "%{$search}%")))
            ->when($state === 'published', fn (Builder $query) => $query->whereNotNull('published_at'))
            ->when($state === 'in_review', fn (Builder $query) => $query->whereNull('published_at')->whereNotNull('submitted_at'))
            ->when($state === 'draft', fn (Builder $query) => $query->whereNull('published_at')->whereNull('submitted_at'))
            ->when($regionId, fn (Builder $query) => $query->where('region_id', $regionId))
            ->when($organizerId, fn (Builder $query) => $query->where('organizer_id', $organizerId))
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Admin/Events/Index', [
            'filters' => ['search' => $search, 'state' => $state, 'region_id' => $regionId, 'organizer_id' => $organizerId],
            'items' => [
                'data' => $events->getCollection()->map(fn (Event $event) => [
                    'id' => $event->id,
                    'slug' => $event->slug,
                    'name' => $event->translated('name'),
                    'starts_on' => $event->starts_on->toDateString(),
                    'region' => $event->region?->translated('name'),
                    'organizer' => $event->organizer?->translated('name'),
                    'state' => $event->moderationState(),
                    'edit_url' => $user->can('update', $event) ? $this->url('edit', $event) : null,
                    'delete_url' => $user->can('delete', $event) ? $this->url('destroy', $event) : null,
                ])->all(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
                'total' => $events->total(),
                'prev_url' => $events->previousPageUrl(),
                'next_url' => $events->nextPageUrl(),
            ],
            'regions' => $this->regions(),
            'organizers' => $this->canModerate($user) ? $this->organizers(null) : [],
            'canCreate' => $user->can('create', Event::class),
            'urls' => ['index' => $this->url('index'), 'create' => $this->url('create')],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Event::class);

        return $this->form($request, null);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $user = $this->user($request);
        $attributes = $this->withOwnOrganizer($request->attributesForModel(), $user);

        abort_if(! $this->canModerate($user) && $user->organizer_id === null, 403);

        $event = DB::transaction(function () use ($attributes) {
            $event = Event::create(Arr::except($attributes, ['races', 'links']));
            EventContent::sync($event, $attributes['races'], $attributes['links']);

            return $event;
        });

        return to_route(Locale::routeName('admin.events.edit'), $event)->with('status', 'created');
    }

    public function edit(Request $request, Event $event): Response
    {
        Gate::authorize('update', $event);

        return $this->form($request, $event);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $attributes = $this->withOwnOrganizer($request->attributesForModel(), $this->user($request));

        DB::transaction(function () use ($event, $attributes) {
            $event->update(Arr::except($attributes, ['races', 'links']));
            EventContent::sync($event, $attributes['races'], $attributes['links']);
        });

        return to_route(Locale::routeName('admin.events.edit'), $event)->with('status', 'updated');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return to_route(Locale::routeName('admin.events.index'))->with('status', 'deleted');
    }

    /**
     * Hand a draft in for review.
     */
    public function submit(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        if (! $event->isPublished() && $event->submitted_at === null) {
            $event->update(['submitted_at' => now()]);
        }

        return to_route(Locale::routeName('admin.events.edit'), $event)->with('status', 'submitted');
    }

    public function publish(Event $event): RedirectResponse
    {
        Gate::authorize('publish', Event::class);

        if (! $event->isPublished()) {
            $event->update(['published_at' => now()]);
        }

        return to_route(Locale::routeName('admin.events.edit'), $event)->with('status', 'published');
    }

    public function unpublish(Event $event): RedirectResponse
    {
        Gate::authorize('publish', Event::class);

        $event->update(['published_at' => null, 'submitted_at' => null]);

        return to_route(Locale::routeName('admin.events.edit'), $event)->with('status', 'unpublished');
    }

    private function form(Request $request, ?Event $event): Response
    {
        $user = $this->user($request);

        return Inertia::render('Admin/Events/Form', [
            'record' => $event === null ? null : [
                'id' => $event->id,
                'slug' => $event->slug,
                'name' => $event->name,
                'description' => $event->description,
                'venue' => $event->venue,
                'package_description' => $event->package_description,
                'cancellation_policy' => $event->cancellation_policy,
                'starts_on' => $event->starts_on->toDateString(),
                'ends_on' => $event->ends_on?->toDateString(),
                'city' => $event->city,
                'address' => $event->address,
                'region_id' => $event->region_id,
                'organizer_id' => $event->organizer_id,
                'series_id' => $event->series_id,
                'edition_number' => $event->edition_number,
                'status' => $event->status->value,
                'date_confirmed' => $event->date_confirmed,
                'registration_status' => $event->registration_status->value,
                'registration_url' => $event->registration_url,
                'registration_opens_at' => $event->registration_opens_at?->format('Y-m-d\TH:i'),
                'registration_closes_at' => $event->registration_closes_at?->format('Y-m-d\TH:i'),
                'contact_email' => $event->contact_email,
                'contact_phone' => $event->contact_phone,
                'source_url' => $event->source_url,
                'image_url' => $event->image_url,
                'medical_certificate_required' => $event->medical_certificate_required,
                'state' => $event->moderationState(),
                'races' => $this->races($event),
                'links' => $this->links($event),
                'urls' => [
                    'submit' => $this->url('submit', $event),
                    'publish' => $this->url('publish', $event),
                    'unpublish' => $this->url('unpublish', $event),
                    'destroy' => $this->url('destroy', $event),
                ],
            ],
            'urls' => ['index' => $this->url('index'), 'submit' => $this->url($event === null ? 'store' : 'update', $event)],
            'regions' => $this->regions(),
            'organizers' => $this->organizers($this->canModerate($user) ? null : $user->organizer_id),
            'series' => EventSeries::query()->orderBy('slug')->get()->map(fn (EventSeries $series) => ['id' => $series->id, 'name' => $series->translated('name')])->all(),
            'statuses' => array_column(EventStatus::cases(), 'value'),
            'registrationStatuses' => array_column(RegistrationStatus::cases(), 'value'),
            'raceOptions' => [
                'disciplines' => array_column(RaceDiscipline::cases(), 'value'),
                'terrains' => array_column(RaceTerrain::cases(), 'value'),
                'statuses' => array_column(RaceStatus::cases(), 'value'),
                'linkTypes' => array_column(LinkType::cases(), 'value'),
            ],
            'canPublish' => $user->can('publish', Event::class),
            'canDelete' => $event !== null && $user->can('delete', $event),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function races(Event $event): array
    {
        $races = [];

        foreach ($event->races()->with('prices')->get() as $race) {
            $prices = [];

            foreach ($race->prices as $price) {
                $prices[] = [
                    'label' => $price->label,
                    'amount_cents' => $price->amount_cents,
                    'valid_from' => $price->valid_from?->toDateString(),
                    'valid_until' => $price->valid_until?->toDateString(),
                ];
            }

            $races[] = [
                'slug' => $race->slug,
                'name' => $race->name,
                'is_main' => $race->is_main,
                'discipline' => $race->discipline->value,
                'terrain' => $race->terrain?->value,
                'distance_meters' => $race->distance_meters,
                'elevation_gain_m' => $race->elevation_gain_m,
                'starts_at' => $race->starts_at->format('Y-m-d\TH:i'),
                'status' => $race->status->value,
                'registration_url' => $race->registration_url,
                'prices' => $prices,
            ];
        }

        return $races;
    }

    /**
     * @return list<array{type: string, url: string, label: array<string, string>|null}>
     */
    private function links(Event $event): array
    {
        $links = [];

        foreach ($event->links as $link) {
            $links[] = ['type' => $link->type->value, 'url' => $link->url, 'label' => $link->label];
        }

        return $links;
    }

    /**
     * Someone who cannot moderate always files events under their own organizer.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withOwnOrganizer(array $attributes, User $user): array
    {
        if (! $this->canModerate($user)) {
            $attributes['organizer_id'] = $user->organizer_id;
        }

        return $attributes;
    }

    private function canModerate(User $user): bool
    {
        return $user->can(Permissions::EVENTS_PUBLISH);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function regions(): array
    {
        return array_values(Region::query()->orderBy('slug')->get()
            ->map(fn (Region $region) => ['id' => $region->id, 'name' => $region->translated('name')])->all());
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function organizers(?int $only): array
    {
        return array_values(Organizer::query()
            ->when($only !== null, fn (Builder $query) => $query->whereKey($only))
            ->orderBy('slug')->get()
            ->map(fn (Organizer $organizer) => ['id' => $organizer->id, 'name' => $organizer->translated('name')])->all());
    }

    private function url(string $action, ?Event $event = null): string
    {
        return route(Locale::routeName("admin.events.{$action}"), $event === null ? [] : ['event' => $event], absolute: false);
    }
}
