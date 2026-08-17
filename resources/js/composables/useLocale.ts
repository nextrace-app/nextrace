import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useLocale() {
    const page = usePage();

    const locale = computed(() => page.props.locale);
    const defaultLocale = computed(() => page.props.defaultLocale);
    const supportedLocales = computed(() => page.props.supportedLocales);

    // Only non-default locales carry a URL prefix, so drop it before rebuilding...
    const stripLocale = (url: string) => {
        const match = url.match(/^\/([^/?#]*)(.*)$/);

        if (!match || !supportedLocales.value.includes(match[1])) {
            return url;
        }

        return match[2] || '/';
    };

    const localeUrl = (url: string, targetLocale?: string) => {
        const target = targetLocale || locale.value;
        const path = stripLocale(url);

        if (target === defaultLocale.value) {
            return path;
        }

        return `/${target}${path === '/' ? '' : path}`;
    };

    // Wayfinder only knows the default locale's URLs, so a <Link> target or a <Form>
    // action has to be moved onto the current locale to stay in the same language.
    // The method is carried over untouched, which POST targets such as logout rely on.
    const localeRoute = <T extends { url: string } | { action: string }>(
        route: T,
    ): T => ({
        ...route,
        ...('url' in route
            ? { url: localeUrl(route.url) }
            : { action: localeUrl(route.action) }),
    });

    return {
        locale,
        defaultLocale,
        supportedLocales,
        localeUrl,
        localeRoute,
    };
}
