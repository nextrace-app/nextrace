<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(Locale::current($request));

        // Fortify reads these when it redirects after login, registration, password
        // confirmation and logout, so they have to follow the locale the request
        // came in on. Logging out defaults to '/', which is the default locale.
        // The prefix is stripped first: config survives between requests inside a
        // single process, so a value rewritten for one locale must not be
        // prefixed again for the next request.
        config([
            'fortify.home' => Locale::path(Locale::stripPrefix(config('fortify.home'))),
            'fortify.redirects.login' => Locale::path(Locale::stripPrefix(config('fortify.redirects.login') ?? config('fortify.home'))),
            'fortify.redirects.logout' => Locale::path(Locale::stripPrefix(config('fortify.redirects.logout') ?? '/')),
        ]);

        return $next($request);
    }
}
