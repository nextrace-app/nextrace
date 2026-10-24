<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use App\Support\PageMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            'defaultLocale' => Locale::default(),
            'supportedLocales' => config('app.supported_locales'),
            // The interface strings for this request's locale, rendered by
            // vue-i18n on the client. Language files stay the single source.
            // The title the server put in the page head, so the client keeps it.
            'seo' => fn () => ['title' => app(PageMeta::class)->toArray()['title']],
            'translations' => Lang::get('ui'),
        ];
    }
}
