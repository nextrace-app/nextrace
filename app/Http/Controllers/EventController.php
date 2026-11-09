<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\PostResource;
use App\Models\Event;
use App\Support\EventCalendar;
use App\Support\PageMeta;
use App\Support\RaceRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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

        $openOnly = $request->query('open') === '1';

        $perPage = (int) config('events.per_page');

        $events = Event::query()
            ->published()
            ->upcoming()
            ->when($distance !== null, fn (Builder $query) => $query->withDistanceFilter($distance))
            ->when($search !== '', fn (Builder $query) => $query->matching($search))
            ->when($openOnly, fn (Builder $query) => $query->registrationOpen())
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
                'open' => $openOnly,
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

        $event->load(['mainRace', 'races.prices', 'races.checkpoints', 'races.course', 'links', 'dates', 'sections', 'series', 'organizer', 'region']);

        $meta->forEvent($event);

        return Inertia::render('Events/Show', [
            'event' => (new EventResource($event))->resolve(),
            'news' => $this->news($event),
            'editions' => $this->editions($event),
            'countdown' => EventCalendar::countdown($event, RaceRegistration::today()),
            'isFavorite' => $request->user()?->favorites()->where('event_id', $event->id)->exists() ?? false,
        ]);
    }

    /**
     * The event as a calendar file, for Apple Calendar and Outlook.
     */
    public function calendar(Event $event): HttpResponse
    {
        abort_unless($event->isPublished(), 404);

        $event->load(['mainRace', 'races']);

        return response(EventCalendar::ics($event), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }

    /**
     * The published editions of the series the event belongs to, the oldest
     * first, so the page can switch between them. A single edition has
     * nothing to switch to, so the list is empty then.
     *
     * @return array<int, array{slug: string, year: int, edition_number: int|null, current: bool}>
     */
    private function editions(Event $event): array
    {
        if ($event->series_id === null) {
            return [];
        }

        $editions = Event::query()
            ->published()
            ->where('series_id', $event->series_id)
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get(['id', 'slug', 'starts_on', 'edition_number']);

        if ($editions->count() < 2) {
            return [];
        }

        return $editions->map(fn (Event $edition) => [
            'slug' => $edition->slug,
            'year' => $edition->starts_on->year,
            'edition_number' => $edition->edition_number,
            'current' => $edition->is($event),
        ])->all();
    }

    /**
     * The latest published posts about the event, and how many there are in
     * all, so the page links to the full list only when it shows less.
     *
     * @return array{posts: array<int, mixed>, total: int}
     */
    private function news(Event $event): array
    {
        $posts = $event->posts()->published();

        return [
            'posts' => PostResource::collection(
                (clone $posts)->latest('posts.published_at')->latest('posts.id')->limit(3)->get()
            )->resolve(),
            'total' => $posts->count(),
        ];
    }
}
