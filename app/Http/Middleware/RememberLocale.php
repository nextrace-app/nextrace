<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RememberLocale
{
    /**
     * The cookie the visitor's language choice is kept in.
     */
    public const COOKIE = 'locale';

    private const LIFETIME_IN_MINUTES = 60 * 24 * 365;

    /**
     * Keep returning visitors on the language they last chose.
     *
     * The default locale is served without a prefix, so a visitor who picked
     * English would land back on Greek every time they enter the site through
     * a bare URL. Their choice is remembered and applied here instead of being
     * guessed from Accept-Language, which search engines advise against.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($chosen = $this->chosenLocale($request)) {
            $this->remember($request, $chosen);

            return redirect()->to($this->urlIn($request, $chosen));
        }

        $requested = Locale::fromRequest($request);

        if ($requested !== null) {
            $this->remember($request, $requested);
        } elseif ($this->mayRedirect($request)) {
            $preferred = $this->preferredLocale($request);

            if ($preferred !== null && $preferred !== Locale::default()) {
                return redirect()->to($this->urlIn($request, $preferred));
            }
        }

        $response = $next($request);

        // The same URL answers in different languages depending on the cookie,
        // which shared caches have to be told about, without dropping whatever
        // the response already varies on.
        $response->headers->set('Vary', collect($response->headers->all('Vary'))
            ->flatMap(fn (?string $value) => preg_split('/\s*,\s*/', (string) $value))
            ->filter()
            ->push('Cookie')
            ->unique()
            ->implode(', '));

        return $response;
    }

    /**
     * The locale the visitor just picked through the language switcher.
     */
    private function chosenLocale(Request $request): ?string
    {
        $locale = $request->query(self::COOKIE);

        return is_string($locale) && Locale::isSupported($locale) ? $locale : null;
    }

    /**
     * The locale this visitor was last seen using.
     */
    private function preferredLocale(Request $request): ?string
    {
        $user = $request->user();

        $locale = ($user instanceof User ? $user->locale : null)
            ?? $request->cookie(self::COOKIE);

        return is_string($locale) && Locale::isSupported($locale) ? $locale : null;
    }

    /**
     * Store the choice for the next visit, on the account when there is one.
     */
    private function remember(Request $request, string $locale): void
    {
        Cookie::queue(self::COOKIE, $locale, self::LIFETIME_IN_MINUTES);

        $user = $request->user();

        if ($user instanceof User && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->save();
        }
    }

    /**
     * Only plain page visits are moved: a redirect would drop a form submission
     * and would invalidate the signature of a link sent by email.
     */
    private function mayRedirect(Request $request): bool
    {
        return $request->isMethodCacheable()
            && ! $request->has('signature')
            && ! $request->hasHeader('X-Inertia-Partial-Component')
            && ! $request->expectsJson();
    }

    /**
     * The current URL as it looks in the given locale, without the switcher's
     * own query parameter.
     *
     * The path is taken unprefixed: the switcher links to a URL that already
     * carries the target locale, which would otherwise be prefixed twice.
     */
    private function urlIn(Request $request, string $locale): string
    {
        $query = $request->query();

        unset($query[self::COOKIE]);

        return url()->query(Locale::path(Locale::unprefixedPath($request), $locale), $query);
    }
}
