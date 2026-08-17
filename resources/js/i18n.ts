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
    },
    en: {
        nav: {
            dashboard: 'Dashboard',
            login: 'Log in',
            register: 'Register',
            events: 'Events',
            language: 'Language',
        },
    },
};

export type SupportedLocale = keyof typeof messages;

export default createI18n({
    legacy: false,
    locale: 'el',
    fallbackLocale: 'el',
    messages,
});
