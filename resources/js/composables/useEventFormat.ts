import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import type { Event } from '@/types';

/**
 * Locale aware formatting of the values an event shows. The date comes as a
 * plain day, so it is read in UTC to never slip to a neighbouring day.
 */
export function useEventFormat() {
    const { t, te } = useI18n();
    const { locale } = useLocale();

    const formatDate = (
        date: string,
        options: Intl.DateTimeFormatOptions = {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        },
    ): string =>
        new Intl.DateTimeFormat(locale.value, {
            ...options,
            timeZone: 'UTC',
        }).format(new Date(`${date}T00:00:00Z`));

    const formatDistance = (meters: number): string =>
        `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 1 }).format(meters / 1000)} km`;

    const formatNumber = (count: number): string =>
        new Intl.NumberFormat(locale.value).format(count);

    const categoryLabel = (category: string): string => {
        const key = `events.categories.${category.toLowerCase().replace(/\s+/g, '_')}`;

        return te(key) ? t(key) : category;
    };

    const mapUrl = (event: Event): string | null =>
        event.latitude !== null && event.longitude !== null
            ? `https://www.google.com/maps/search/?api=1&query=${event.latitude},${event.longitude}`
            : null;

    return { formatDate, formatDistance, formatNumber, categoryLabel, mapUrl };
}
