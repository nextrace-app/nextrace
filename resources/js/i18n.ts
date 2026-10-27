import { createI18n } from 'vue-i18n';

export type Translations = { [key: string]: string | Translations };

/**
 * A fresh instance per application, carrying the translations the server sent
 * for the current locale: the SSR server renders every request from the same
 * process, so a shared instance would leak one request's language into the next.
 *
 * Only the current locale is ever loaded - Laravel's language files are the
 * single source of truth and resolve the fallback before sharing them.
 */
export function createAppI18n(locale: string, messages: Translations) {
    return createI18n({
        legacy: false,
        locale,
        messages: { [locale]: messages },
    });
}
