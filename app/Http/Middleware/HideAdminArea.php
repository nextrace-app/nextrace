<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HideAdminArea
{
    /**
     * Answer 404 to anyone who may not open the admin area, guests included,
     * so the page gives no hint that it exists.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->can(Permissions::ADMIN_ACCESS), 404);

        return $next($request);
    }
}
