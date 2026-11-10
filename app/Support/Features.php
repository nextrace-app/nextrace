<?php

namespace App\Support;

use App\Models\FeatureFlag;

/**
 * Whether a feature flag of the admin (Features) is on. A key nobody has
 * added, or that was deleted, is off, so checking it never breaks a page.
 */
class Features
{
    /**
     * Whether the flag with that key is on.
     */
    public static function active(string $key): bool
    {
        return in_array($key, self::activeKeys(), true);
    }

    /**
     * The keys of the flags that are on, read once per request.
     *
     * @return list<string>
     */
    public static function activeKeys(): array
    {
        $request = request();

        if (! $request->attributes->has('features.active')) {
            $request->attributes->set('features.active', FeatureFlag::query()->where('is_active', true)->orderBy('key')->pluck('key')->all());
        }

        /** @var list<string> */
        return $request->attributes->get('features.active');
    }
}
