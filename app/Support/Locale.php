<?php

namespace App\Support;

use Illuminate\Http\Request;

class Locale
{
    /**
     * The application's default locale, which is never shown in the URL.
     *
     * Deliberately not config('app.locale'): Application::setLocale() overwrites
     * that key, so it holds the current locale rather than the default one.
     */
    public static function default(): string
    {
        return config('app.default_locale');
    }

    /**
     * The locales that carry a URL prefix, i.e. every supported locale but the default one.
     *
     * @return list<string>
     */
    public static function prefixed(): array
    {
        return array_values(array_diff(config('app.supported_locales'), [self::default()]));
    }

    /**
     * The locale requested through the URL prefix, or null when the default locale applies.
     */
    public static function fromRequest(?Request $request = null): ?string
    {
        $segment = ($request ?? request())->segment(1);

        return in_array($segment, self::prefixed(), true) ? $segment : null;
    }

    /**
     * The locale that applies to the given request.
     */
    public static function current(?Request $request = null): string
    {
        return self::fromRequest($request) ?? self::default();
    }

    /**
     * The path of the given request with any locale prefix taken off, so it can
     * be rebuilt in another locale.
     */
    public static function unprefixedPath(?Request $request = null): string
    {
        $request ??= request();

        return self::stripPrefix($request->path());
    }

    /**
     * The current page's absolute URL in every locale the application serves,
     * for the alternate links search engines read.
     *
     * @return array<string, string>
     */
    public static function alternates(?Request $request = null): array
    {
        $request ??= request();

        $path = self::unprefixedPath($request);

        $query = $request->query();
        unset($query['locale']);

        $alternates = [];

        foreach (config('app.supported_locales') as $locale) {
            $alternates[$locale] = url()->query(self::path($path, $locale), $query);
        }

        return $alternates;
    }

    /**
     * The name a route carries under the given locale, e.g. 'login' becomes 'en.login'.
     *
     * Defaults to the locale the application is currently running in, which is
     * the URL's locale during a request and the recipient's locale while a
     * notification is being formatted.
     */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return in_array($locale, self::prefixed(), true) ? "{$locale}.{$name}" : $name;
    }

    /**
     * Whether the given locale is one the application serves.
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, config('app.supported_locales'), true);
    }

    /**
     * The given path without a locale prefix, e.g. '/en/dashboard' becomes '/dashboard'.
     */
    public static function stripPrefix(string $path): string
    {
        $segments = explode('/', trim($path, '/'));

        if (in_array($segments[0], self::prefixed(), true)) {
            array_shift($segments);
        }

        return '/'.implode('/', $segments);
    }

    /**
     * The path a URL carries under the given locale (the application's current
     * locale by default), e.g. '/dashboard' becomes '/en/dashboard'.
     */
    public static function path(string $path, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if (! in_array($locale, self::prefixed(), true)) {
            return $path;
        }

        return rtrim("/{$locale}/".ltrim($path, '/'), '/');
    }
}
