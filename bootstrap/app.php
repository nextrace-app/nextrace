<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RememberLocale;
use App\Http\Middleware\SetLocale;
use App\Support\Locale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // First in the group: everything after it may translate or build URLs.
        $middleware->prependToGroup('web', SetLocale::class);

        // Keep guests on the locale they were browsing when they hit a protected
        // page. Read from the request rather than from the application's locale,
        // because 'auth' can run before the locale has been applied.
        $middleware->redirectGuestsTo(fn (Request $request) => route(
            Locale::routeName('login', Locale::current($request))
        ));

        $middleware->web(append: [
            // After the session, so it can read the cookie and the current user.
            RememberLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Branded, localized error pages in place of the bare framework text.
        // A 500 keeps Laravel's own debug page while debugging.
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if (! in_array($status, [403, 404, 419, 500, 503], true)
                || $response->request->expectsJson()
                || ($status === 500 && config('app.debug'))) {
                return null;
            }

            // A request that matched no route never went through the web
            // middleware, so the language of the URL has to be applied here.
            app()->setLocale(Locale::current($response->request));

            return $response->render('Error', ['status' => $status])->withSharedData();
        });
    })->create();
