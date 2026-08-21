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
