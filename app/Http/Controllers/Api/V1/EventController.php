<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListEventsRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\StoredEventResource;
use App\Models\Event;
use App\Support\EventImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * Create or update one event, in the format of events:import, as a draft.
     *
     * The event is found by its slug (or one of its previous_slugs). A
     * published event is not changed here, because that would change the site
     * without review: it is edited in the admin. Publishing is refused too.
     */
    public function store(Request $request, EventImport $import): JsonResponse
    {
        /** @var array<string, mixed> $row */
        $row = $request->all();
        $existing = $import->existingEvent($row);

        if ($existing?->isPublished()) {
            return response()->json([
                'message' => 'This event is published; edit it in the admin.',
                'admin_url' => route('admin.events.edit', $existing),
            ], 409);
        }

        /** @var array<string, mixed> $data */
        $data = $import->validator($row, $existing === null, mayPublish: false)->validate();

        $event = $import->store($data, $existing);

        return StoredEventResource::make($event)->response()->setStatusCode($existing === null ? 201 : 200);
    }
}
