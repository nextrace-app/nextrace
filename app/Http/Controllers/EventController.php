<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\PageMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * The published events that are not over yet, the one coming up first.
     */
    public function index(Request $request): Response
    {
        $events = Event::query()->published()->upcoming()->chronological()->withDetails()->get();

        return Inertia::render('Events/Index', [
            'events' => EventResource::collection($events)->resolve(),
            'favorites' => $request->user()?->favoriteEventIds() ?? [],
            'perPage' => config('events.per_page'),
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
