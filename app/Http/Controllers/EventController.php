<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\PageMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * The distance filters the events list understands; 'kids_other' is every
     * race that is not one of the named classes.
     *
     * @var list<string>
     */
    public const DISTANCE_FILTERS = ['5k', '10k', 'half_marathon', 'marathon', 'ultra', 'kids_other'];

    public const MAX_SEARCH_LENGTH = 100;

    /**
     * One page of the published events that are not over yet, the one coming up first.
     *
     * Every page has an address of its own (?page=N) so that crawlers can reach
     * all of them. The first page lives at the bare URL, and a page that does
     * not exist is a 404.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $page = $request->query('page');

        if ($page !== null && (! is_string($page) || ! ctype_digit($page) || (int) $page < 1)) {
            abort(404);
        }

        if ($page !== null && (int) $page === 1) {
            return redirect()->to($request->fullUrlWithoutQuery('page'), 301);
        }

        $distance = $request->query('distance');
        $distance = is_string($distance) && in_array($distance, self::DISTANCE_FILTERS, true) ? $distance : null;

        $search = $request->query('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, self::MAX_SEARCH_LENGTH) : '';

        $perPage = (int) config('events.per_page');

        $events = Event::query()
            ->published()
            ->upcoming()
            ->when($distance !== null, fn (Builder $query) => $query->withDistanceFilter($distance))
            ->when($search !== '', fn (Builder $query) => $query->matching($search))
            ->chronological()
            ->withDetails()
            ->paginate($perPage, page: (int) ($page ?? 1));

        abort_if($events->currentPage() > max(1, $events->lastPage()), 404);

        return Inertia::render('Events/Index', [
            'events' => EventResource::collection($events->getCollection())->resolve(),
            'favorites' => $request->user()?->favoriteEventIds() ?? [],
            'pagination' => [
                'current' => $events->currentPage(),
                'last' => max(1, $events->lastPage()),
                'total' => $events->total(),
                'perPage' => $perPage,
            ],
            'filters' => [
                'distance' => $distance,
                'q' => $search,
            ],
        ]);
    }

    /**
     * The page of a single event.
     */
    public function show(Request $request, Event $event, PageMeta $meta): Response
    {
        // A draft has no page of its own until it is published.
        abort_unless($event->isPublished(), 404);

        $event->load(['mainRace', 'races.prices', 'links', 'series', 'organizer', 'region']);

        $meta->forEvent($event);

        return Inertia::render('Events/Show', [
            'event' => (new EventResource($event))->resolve(),
            'isFavorite' => $request->user()?->favorites()->where('event_id', $event->id)->exists() ?? false,
        ]);
    }
}
