import { createInertiaApp, router } from '@inertiajs/vue3';
import { createAppI18n } from '@/i18n';
import type { Translations } from '@/i18n';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

let i18n: ReturnType<typeof createAppI18n> | undefined;

createInertiaApp({
    // Not setup(), which would have to mount the app itself and so breaks server
    // side rendering, where there is no element to mount onto.
    withApp: (app, { page }) => {
        i18n = createAppI18n(page.props.locale, page.props.translations);

        app.use(i18n);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Home':
            case name === 'Events/Index':
            case name === 'Favorites/Index':
            case name === 'Profile':
            case name === 'Dashboard':
            case name === 'Error':
            case name === 'Events/Show':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return SettingsLayout;
            default:
                return null;
        }
    },
    progress: {
        color: '#0F7A5C',
    },
});

// This will keep the client-side locale and its strings in sync with the
// server on every visit...
router.on('navigate', (event) => {
    if (!i18n) {
        return;
    }

    const { locale, translations } = event.detail.page.props as {
        locale: string;
        translations: Translations;
    };

    i18n.global.setLocaleMessage(locale, translations);
    i18n.global.locale.value = locale;
});

// This will listen for flash toast data from the server...
initializeFlashToast();
