import { createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import i18n from '@/i18n';
import type { SupportedLocale } from '@/i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    setup({ el, App, props, plugin }) {
        i18n.global.locale.value =
            (props.initialPage.props.locale as SupportedLocale) || 'el';
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el!);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'Events/Index':
            case name === 'Events/Show':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will keep the client-side locale in sync with the server on every visit...
router.on('navigate', (event) => {
    i18n.global.locale.value = event.detail.page.props
        .locale as SupportedLocale;
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
