<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KeepOutOfSearchResults
{
    /**
     * Tell search engines not to index any response while the site is not
     * indexable (see config/site.php).
     *
     * robots.txt only asks crawlers to stay away; this header is what keeps a
     * page that was linked from elsewhere out of the results.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config()->boolean('site.indexable')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
