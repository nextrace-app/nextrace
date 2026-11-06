<?php

namespace App\Providers;

use App\Support\Locale;
use App\Support\PageMeta;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PageMeta::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureLocaleAlternates();
        $this->configureRateLimiting();
    }

    /**
     * Limit the API per token, and per address for requests that carry none.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->bearerToken() !== null ? hash('sha256', $request->bearerToken()) : $request->ip()));
    }

    /**
     * Hand the root template the current page's URL in every locale.
     */
    protected function configureLocaleAlternates(): void
    {
        View::composer('app', fn (ViewInstance $view) => $view->with([
            'localeAlternates' => Locale::alternates(),
            'defaultLocale' => Locale::default(),
            'seo' => app(PageMeta::class)->toArray(),
        ]));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
