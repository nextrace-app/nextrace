<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListEventsRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    /**
     * One page of the published events, past ones included, in the order they are held.
     *
     * Text comes in the requested locale, Greek unless told otherwise.
     * "from" keeps the events that are not over before that day, "to" those
     * that start no later than that day.
     */
    public function index(ListEventsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        app()->setLocale($filters['locale'] ?? config('app.default_locale'));

        $events = Event::query()
            ->published()
            ->when(isset($filters['from']), fn (Builder $query) => $query->whereRaw('coalesce(events.ends_on, events.starts_on) >= ?', [$filters['from']]))
            ->when(isset($filters['to']), fn (Builder $query) => $query->where('events.starts_on', '<=', $filters['to']))
            ->when(isset($filters['distance']), fn (Builder $query) => $query->withDistanceFilter($filters['distance']))
            ->when(isset($filters['q']), fn (Builder $query) => $query->matching($filters['q']))
            ->chronological()
            ->withDetails()
            ->paginate((int) ($filters['per_page'] ?? config('events.per_page')));

        return EventResource::collection($events);
    }
}
