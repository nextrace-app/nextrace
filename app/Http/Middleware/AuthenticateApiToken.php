<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through when it carries a valid bearer token that may do what
 * the route needs: api.token:events:read.
 *
 * No token, an unknown one or an expired one is a 401; a valid token without
 * the ability is a 403. The token is kept on the request as 'api_token', and
 * the user it acts as, if any, becomes the user of the request.
 */
class AuthenticateApiToken
{
    /**
     * A token's last use is written at most this often, so reading does not write on every request.
     */
    private const TOUCH_AFTER_SECONDS = 60;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $plain = $request->bearerToken();
        $token = $plain === null || $plain === '' ? null : ApiToken::findByPlainText($plain);

        if ($token === null || $token->isExpired()) {
            abort(401, 'Unauthenticated.', ['WWW-Authenticate' => 'Bearer']);
        }

        abort_unless($token->can($ability), 403, 'This token may not do that.');

        if ($token->last_used_at === null || $token->last_used_at->diffInSeconds(now(), true) >= self::TOUCH_AFTER_SECONDS) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set('api_token', $token);

        if ($token->user !== null) {
            $request->setUserResolver(fn () => $token->user);
        }

        return $next($request);
    }
}
