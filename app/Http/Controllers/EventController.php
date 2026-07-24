<?php

namespace App\Http\Controllers;

use App\Constants\Events;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Events/Index', [
            'events' => Events::LIST,
        ]);
    }

    public function show(int $event): Response
    {
        $event = Arr::first(Events::LIST, fn (array $item) => $item['id'] === $event);

        abort_if($event === null, 404);

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }
}
