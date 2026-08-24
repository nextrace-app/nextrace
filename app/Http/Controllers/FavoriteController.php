<?php

namespace App\Http\Controllers;

use App\Constants\Events;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * The events the signed in user marked with the heart, newest first.
     */
    public function index(Request $request): Response
    {
        $events = collect($request->user()->favoriteEventIds())
            ->map(fn (int $id) => Events::find($id))
            // An event can disappear from the catalogue while it is still
            // someone's favorite, so the gaps are dropped rather than rendered.
            ->filter()
            ->values();

        return Inertia::render('Favorites/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Mark an event as a favorite of the signed in user.
     */
    public function store(Request $request, int $event): RedirectResponse
    {
        abort_if(Events::find($event) === null, 404);

        // firstOrCreate keeps a second click on the heart from failing on the
        // unique index, e.g. when the page was left open in two tabs.
        $request->user()->favorites()->firstOrCreate(['event_id' => $event]);

        return back();
    }

    /**
     * Take an event off the signed in user's favorites.
     */
    public function destroy(Request $request, int $event): RedirectResponse
    {
        $request->user()->favorites()->where('event_id', $event)->delete();

        return back();
    }
}
