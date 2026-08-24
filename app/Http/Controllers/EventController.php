<?php

namespace App\Http\Controllers;

use App\Constants\Events;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'events' => Events::LIST,
            'favorites' => $request->user()?->favoriteEventIds() ?? [],
        ]);
    }

    public function show(Request $request, int $event): Response
    {
        $event = Events::find($event);

        abort_if($event === null, 404);

        return Inertia::render('Events/Show', [
            'event' => $event,
            'isFavorite' => in_array($event['id'], $request->user()?->favoriteEventIds() ?? [], true),
        ]);
    }
}
