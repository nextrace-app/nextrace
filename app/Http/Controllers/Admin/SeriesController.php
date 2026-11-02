<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SeriesRequest;
use App\Models\EventSeries;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class SeriesController extends ReferenceController
{
    protected function modelClass(): string
    {
        return EventSeries::class;
    }

    protected function section(): string
    {
        return 'series';
    }

    protected function usageCount(Model $record): int
    {
        /** @var EventSeries $record */
        return (int) ($record->events_count ?? $record->events()->count());
    }

    protected function formValues(Model $record): array
    {
        /** @var EventSeries $record */
        return [
            'description' => $record->description,
            'logo_url' => $record->logo_url,
            'website' => $record->website,
        ];
    }

    public function store(SeriesRequest $request): RedirectResponse
    {
        Gate::authorize('create', EventSeries::class);

        EventSeries::create($request->attributesForModel());

        return to_route(Locale::routeName('admin.series.index'))->with('status', 'created');
    }

    public function edit(EventSeries $series): Response
    {
        return $this->editRecord($series);
    }

    public function update(SeriesRequest $request, EventSeries $series): RedirectResponse
    {
        Gate::authorize('update', $series);

        $series->update($request->attributesForModel());

        return to_route(Locale::routeName('admin.series.index'))->with('status', 'updated');
    }

    public function destroy(EventSeries $series): RedirectResponse
    {
        return $this->destroyRecord($series);
    }
}
