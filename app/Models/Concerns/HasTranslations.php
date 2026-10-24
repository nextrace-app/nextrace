<?php

namespace App\Models\Concerns;

/**
 * Text stored per locale in a jsonb column, e.g. {"en": "...", "el": "..."}.
 */
trait HasTranslations
{
    /**
     * The value of a translated attribute in the given locale, which defaults to
     * the one the application is running in, falling back to the fallback locale.
     */
    public function translated(string $attribute, ?string $locale = null): string
    {
        return $this->translatedOrNull($attribute, $locale) ?? '';
    }

    /**
     * Like translated(), but null when the attribute holds no text at all.
     */
    public function translatedOrNull(string $attribute, ?string $locale = null): ?string
    {
        /** @var array<string, string>|null $values */
        $values = $this->getAttribute($attribute);

        $locale ??= app()->getLocale();

        return $values[$locale] ?? $values[config('app.fallback_locale')] ?? null;
    }
}
