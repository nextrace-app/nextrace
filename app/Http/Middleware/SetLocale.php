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

        // Fortify reads this when it redirects after login, registration and password
        // confirmation, so it has to follow the locale the request came in on.
        config(['fortify.home' => Locale::path(config('fortify.home'), $request)]);

        return $next($request);
    }
}
