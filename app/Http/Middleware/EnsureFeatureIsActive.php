<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides a route behind a feature flag: while the flag is off, or was never
 * added, the page does not exist. Used as 'feature:<key>'.
 */
class EnsureFeatureIsActive
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless(Features::active($key), 404);

        return $next($request);
    }
}
