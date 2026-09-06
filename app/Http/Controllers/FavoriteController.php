<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
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
        return Inertia::render('Favorites/Index', [
            'events' => EventResource::collection($request->user()->favoriteEvents)->resolve(),
        ]);
    }

    /**
     * Mark an event as a favorite of the signed in user.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        // firstOrCreate keeps a second click on the heart from failing on the
        // unique index, e.g. when the page was left open in two tabs.
        $request->user()->favorites()->firstOrCreate(['event_id' => $event->id]);

        return back();
    }

    /**
     * Take an event off the signed in user's favorites.
     */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $request->user()->favorites()->where('event_id', $event->id)->delete();

        return back();
    }
}
