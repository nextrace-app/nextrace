<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Features section of the admin: feature flags by key, which the code
 * checks with App\Support\Features. A flag is created off; its key never
 * changes, because code refers to it.
 */
class FeatureController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FeatureFlag::class);

        $user = $request->user();

        return Inertia::render('Admin/Features/Index', [
            'flags' => FeatureFlag::query()->with('editor:id,name')->orderBy('key')->get()
                ->map(fn (FeatureFlag $flag) => [
                    'key' => $flag->key,
                    'description' => $flag->description,
                    'is_active' => $flag->is_active,
                    'updated_at' => $flag->updated_at?->toIso8601String(),
                    'updated_by' => $flag->editor?->name,
                    'edit_url' => $user?->can('update', $flag) ? $this->url('edit', $flag) : null,
                    'activate_url' => $user?->can('update', $flag) ? $this->url('activate', $flag) : null,
                    'delete_url' => $user?->can('delete', $flag) ? $this->url('destroy', $flag) : null,
                ])->all(),
            'canCreate' => $user?->can('create', FeatureFlag::class) ?? false,
            'urls' => ['create' => $this->url('create')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', FeatureFlag::class);

        return Inertia::render('Admin/Features/Form', [
            'flag' => null,
            'urls' => ['index' => $this->url('index'), 'submit' => $this->url('store')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', FeatureFlag::class);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:64', 'regex:'.FeatureFlag::KEY_PATTERN, Rule::unique('feature_flags', 'key')],
            'description' => ['required', 'string', 'max:255'],
        ]);

        FeatureFlag::query()->create([...$data, 'is_active' => false, 'updated_by' => $request->user()?->id]);

        return to_route(Locale::routeName('admin.features.index'))->with('status', 'created');
    }

    public function edit(FeatureFlag $feature): Response
    {
        Gate::authorize('update', $feature);

        return Inertia::render('Admin/Features/Form', [
            'flag' => ['key' => $feature->key, 'description' => $feature->description],
            'urls' => ['index' => $this->url('index'), 'submit' => $this->url('update', $feature)],
        ]);
    }

    /**
     * Change the description. The key stays: it is not read from the request.
     */
    public function update(Request $request, FeatureFlag $feature): RedirectResponse
    {
        Gate::authorize('update', $feature);

        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
        ]);

        $feature->update([...$data, 'updated_by' => $request->user()?->id]);

        return to_route(Locale::routeName('admin.features.index'))->with('status', 'updated');
    }

    /**
     * Switch the flag on or off, from the list.
     */
    public function activate(Request $request, FeatureFlag $feature): RedirectResponse
    {
        Gate::authorize('update', $feature);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $feature->update(['is_active' => $data['is_active'], 'updated_by' => $request->user()?->id]);

        return back()->with('status', 'updated');
    }

    public function destroy(FeatureFlag $feature): RedirectResponse
    {
        Gate::authorize('delete', $feature);

        $feature->delete();

        return to_route(Locale::routeName('admin.features.index'))->with('status', 'deleted');
    }

    private function url(string $action, ?FeatureFlag $flag = null): string
    {
        return route(Locale::routeName("admin.features.{$action}"), $flag === null ? [] : ['feature' => $flag], absolute: false);
    }
}
