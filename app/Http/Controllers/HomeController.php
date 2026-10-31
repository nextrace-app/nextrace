<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * How many upcoming events the home page shows.
     */
    private const UPCOMING_LIMIT = 3;

    /**
     * The home page with the next published events.
     */
    public function __invoke(Request $request): Response
    {
        $events = Event::query()
            ->published()
            ->upcoming()
            ->chronological()
            ->withDetails()
            ->limit(self::UPCOMING_LIMIT)
            ->get();

        return Inertia::render('Home', [
            'events' => EventResource::collection($events)->resolve(),
            'favorites' => $request->user()?->favoriteEventIds() ?? [],
        ]);
    }
}
