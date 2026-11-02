<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\RegionRequest;
use App\Models\Region;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class RegionController extends ReferenceController
{
    protected function modelClass(): string
    {
        return Region::class;
    }

    protected function section(): string
    {
        return 'regions';
    }

    protected function withUsage(Builder $query): Builder
    {
        return $query->withCount(['events', 'children']);
    }

    protected function usageCount(Model $record): int
    {
        /** @var Region $record */
        return (int) ($record->events_count ?? $record->events()->count())
            + (int) ($record->children_count ?? $record->children()->count());
    }

    protected function formValues(Model $record): array
    {
        /** @var Region $record */
        return ['parent_id' => $record->parent_id];
    }

    protected function formOptions(?Model $record): array
    {
        $excluded = $record instanceof Region ? $record->withDescendantIds() : [];

        return [
            'parents' => Region::query()
                ->whereNotIn('id', $excluded)
                ->orderBy('slug')
                ->get()
                ->map(fn (Region $region) => ['id' => $region->id, 'name' => $region->translated('name')])
                ->all(),
        ];
    }

    public function store(RegionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Region::class);

        Region::create($request->attributesForModel());

        return to_route(Locale::routeName('admin.regions.index'))->with('status', 'created');
    }

    public function edit(Region $region): Response
    {
        return $this->editRecord($region);
    }

    public function update(RegionRequest $request, Region $region): RedirectResponse
    {
        Gate::authorize('update', $region);

        $region->update($request->attributesForModel());

        return to_route(Locale::routeName('admin.regions.index'))->with('status', 'updated');
    }

    public function destroy(Region $region): RedirectResponse
    {
        return $this->destroyRecord($region);
    }
}
