<?php

namespace App\Http\Controllers;

use App\Constants\Events;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
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
