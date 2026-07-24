<?php

namespace App\Http\Controllers;

use App\Constants\Events;
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
}
