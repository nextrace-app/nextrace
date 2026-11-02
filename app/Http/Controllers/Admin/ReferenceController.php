<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The shared admin CRUD of the translated reference lists (regions, series):
 * a searchable table, one form for create and edit, and a delete that is
 * refused while events still point at the record.
 */
abstract class ReferenceController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * The section key, which is also the route name segment: `regions`.
     */
    abstract protected function section(): string;

    /**
     * The records that stop a delete, as a count to compare with zero.
     */
    abstract protected function usageCount(Model $record): int;

    /**
     * The attributes of a record the edit form needs besides slug and name.
     *
     * @return array<string, mixed>
     */
    abstract protected function formValues(Model $record): array;

    /**
     * Extra props for the form page, e.g. the choices of a select.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(?Model $record): array
    {
        return [];
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function withUsage(Builder $query): Builder
    {
        return $query->withCount('events');
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', $this->modelClass());

        $search = trim($request->string('search')->toString());

        $query = $this->withUsage($this->modelClass()::query())->orderBy('slug');

        if ($search !== '') {
            $like = '%'.$search.'%';

            $query->where(fn (Builder $inner) => $inner
                ->whereLike('slug', $like)
                ->orWhereLike('name->el', $like)
                ->orWhereLike('name->en', $like));
        }

        $items = $query->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('Admin/Reference/Index', [
            'section' => $this->section(),
            'search' => $search,
            'items' => $this->paginated($items),
            'canCreate' => $request->user()?->can('create', $this->modelClass()) ?? false,
            'urls' => ['index' => $this->url('index'), 'create' => $this->url('create')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', $this->modelClass());

        return Inertia::render('Admin/Reference/Form', [
            'section' => $this->section(),
            'record' => null,
            'urls' => ['index' => $this->url('index'), 'submit' => $this->url('store')],
            ...$this->formOptions(null),
        ]);
    }

    protected function editRecord(Model $record): Response
    {
        Gate::authorize('update', $record);

        return Inertia::render('Admin/Reference/Form', [
            'section' => $this->section(),
            'record' => [
                'id' => $record->getKey(),
                'slug' => $record->getAttribute('slug'),
                'name' => $record->getAttribute('name'),
                ...$this->formValues($record),
            ],
            'urls' => ['index' => $this->url('index'), 'submit' => $this->url('update', $record)],
            ...$this->formOptions($record),
        ]);
    }

    protected function destroyRecord(Model $record): RedirectResponse
    {
        Gate::authorize('delete', $record);

        $count = $this->usageCount($record);

        if ($count > 0) {
            throw ValidationException::withMessages([
                'delete' => trans_choice('ui.admin.reference.delete_blocked', $count, ['count' => $count]),
            ]);
        }

        $record->delete();

        return to_route(Locale::routeName("admin.{$this->section()}.index"))
            ->with('status', 'deleted');
    }

    /**
     * @param  array<string, string|null>  $translations
     * @return array<string, string>
     */
    protected function translations(array $translations): array
    {
        return array_filter($translations, fn ($value) => is_string($value) && $value !== '');
    }

    protected function url(string $action, ?Model $record = null): string
    {
        $route = Locale::routeName("admin.{$this->section()}.{$action}");

        return route($route, $record === null ? [] : [$this->section() === 'series' ? 'series' : 'region' => $record], absolute: false);
    }

    /**
     * @param  LengthAwarePaginator<int, Model>  $paginator
     * @return array<string, mixed>
     */
    private function paginated(LengthAwarePaginator $paginator): array
    {
        $user = request()->user();

        return [
            'data' => $paginator->getCollection()->map(fn (Model $record) => [
                'id' => $record->getKey(),
                'slug' => $record->getAttribute('slug'),
                'name' => method_exists($record, 'translated') ? $record->translated('name') : '',
                'events_count' => $this->usageCount($record),
                'edit_url' => $user?->can('update', $record) ? $this->url('edit', $record) : null,
                'delete_url' => $user?->can('delete', $record) ? $this->url('destroy', $record) : null,
            ])->all(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'prev_url' => $paginator->previousPageUrl(),
            'next_url' => $paginator->nextPageUrl(),
        ];
    }
}
