import { createI18n } from 'vue-i18n';

const messages = {
    el: {
        nav: {
            dashboard: 'Πίνακας ελέγχου',
            login: 'Σύνδεση',
            register: 'Εγγραφή',
            events: 'Εκδηλώσεις',
            language: 'Γλώσσα',
        },
        home: {
            title: 'Βρες τον επόμενο αγώνα σου',
            subtitle:
                'Δες τις επερχόμενες εκδηλώσεις και δήλωσε συμμετοχή σε λίγα λεπτά.',
            cta: 'Δες τις εκδηλώσεις',
        },
    },
    en: {
        nav: {
            dashboard: 'Dashboard',
            login: 'Log in',
            register: 'Register',
            events: 'Events',
            language: 'Language',
        },
        home: {
            title: 'Find your next race',
            subtitle:
                'Browse the upcoming events and sign up in a couple of minutes.',
            cta: 'Browse events',
        },
    },
};

export const defaultLocale = 'el';

export type SupportedLocale = keyof typeof messages;

/**
 * A fresh instance per application: the SSR server renders every request from
 * the same process, so a shared instance would leak one request's locale into
 * the next one.
 */
export function createAppI18n(locale: SupportedLocale = defaultLocale) {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: defaultLocale,
        messages,
    });
}
