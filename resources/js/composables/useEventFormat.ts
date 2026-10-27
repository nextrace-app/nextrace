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
        `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 3 }).format(meters / 1000)} ${t('events.distance_unit')}`;

    const formatNumber = (count: number): string =>
        new Intl.NumberFormat(locale.value).format(count);

    const categoryLabel = (category: string): string => {
        const key = `events.categories.${category.toLowerCase().replace(/\s+/g, '_')}`;

        return te(key) ? t(key) : category;
    };

    const formatTime = (date: string, time: string): string =>
        new Intl.DateTimeFormat(locale.value, {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'UTC',
        }).format(new Date(`${date}T${time}:00Z`));

    const formatDateTimeString = (
        value: string,
        options: Intl.DateTimeFormatOptions,
    ): string =>
        new Intl.DateTimeFormat(locale.value, {
            ...options,
            timeZone: 'UTC',
        }).format(new Date(`${value.replace(' ', 'T')}Z`));

    const formatDateRange = (start: string, end: string | null): string => {
        if (end === null || end === start) {
            return formatDate(start);
        }

        return new Intl.DateTimeFormat(locale.value, {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: 'UTC',
        }).formatRange(
            new Date(`${start}T00:00:00Z`),
            new Date(`${end}T00:00:00Z`),
        );
    };

    const formatPrice = (amount: number, currency: string): string =>
        new Intl.NumberFormat(locale.value, {
            style: 'currency',
            currency,
            minimumFractionDigits: Number.isInteger(amount) ? 0 : 2,
        }).format(amount);

    const formatDuration = (minutes: number): string => {
        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;

        return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
    };

    const mapUrl = (event: Event): string | null =>
        event.latitude !== null && event.longitude !== null
            ? `https://www.google.com/maps/search/?api=1&query=${event.latitude},${event.longitude}`
            : null;

    return {
        formatDate,
        formatTime,
        formatDateTimeString,
        formatDateRange,
        formatPrice,
        formatDuration,
        formatDistance,
        formatNumber,
        categoryLabel,
        mapUrl,
    };
}
