<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { show as eventsShow } from '@/routes/events';
import type { Event } from '@/types';

const props = defineProps<{
    event: Event;
    favorited: boolean;
    /** The heading level of the title, one below the heading of its section. */
    headingLevel?: 2 | 3;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const { formatDate, formatDateRange, formatDistance } = useEventFormat();

const maxChips = 3;

const day = computed(() => {
    const { from, to } = props.event.dates;
    const first = formatDate(from, { day: 'numeric' });

    // Two days of one month fit the badge as "7–8"; a span over months shows its first day.
    return to && to.slice(0, 7) === from.slice(0, 7)
        ? `${first}–${formatDate(to, { day: 'numeric' })}`
        : first;
});
const month = computed(() =>
    formatDate(props.event.dates.from, { month: 'short' }).replace('.', ''),
);
const fullDate = computed(() =>
    formatDateRange(props.event.dates.from, props.event.dates.to),
);
// One chip per distance: a weekend with two 5 km races shows "5 km" once.
const otherRaces = computed(() => {
    const seen = new Set([props.event.distance_meters]);

    return props.event.races.filter((race) => {
        if (race.is_main || seen.has(race.distance_meters)) {
            return false;
        }

        seen.add(race.distance_meters);

        return true;
    });
});
const chips = computed(() => otherRaces.value.slice(0, maxChips));
const hiddenRaces = computed(
    () => otherRaces.value.length - chips.value.length,
);
const statusLabel = computed(() =>
    props.event.status === 'postponed' ||
    props.event.status === 'cancelled' ||
    props.event.status === 'completed'
        ? t(`events.status.${props.event.status}`)
        : null,
);
</script>

<template>
    <article
        class="group relative flex flex-col overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs transition-[box-shadow,transform,border-color] focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary hover:shadow-lg motion-safe:hover:-translate-y-0.5"
        data-testid="event-card"
    >
        <div
            class="relative aspect-[16/10] overflow-hidden bg-secondary"
            data-testid="event-card__media"
        >
            <img
                v-if="event.image"
                :src="event.image"
                alt=""
                loading="lazy"
                data-testid="event-card__image"
                class="size-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
            />
            <ContourArt v-else :seed="event.slug" :width="480" :height="300" />

            <div
                class="absolute top-3 left-3 flex flex-col items-center rounded-lg bg-card px-2 py-1 text-card-foreground shadow-xs"
                aria-hidden="true"
                data-testid="event-card__date-badge"
            >
                <span class="text-lg leading-tight font-extrabold">{{
                    day
                }}</span>
                <span
                    class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                    >{{ month }}</span
                >
            </div>

            <FavoriteButton
                :event-slug="event.slug"
                :event-name="event.name"
                :favorited="favorited"
                class="absolute top-3 right-3 z-10"
                data-testid="event-card__favorite"
            />

            <Badge
                v-if="statusLabel"
                variant="destructive"
                class="absolute bottom-3 left-3"
                data-testid="event-card__status"
                >{{ statusLabel }}</Badge
            >
        </div>

        <div
            class="flex flex-1 flex-col gap-4 p-4"
            data-testid="event-card__body"
        >
            <div class="min-w-0">
                <component
                    :is="`h${headingLevel ?? 3}`"
                    class="text-lg leading-snug font-bold"
                    data-testid="event-card__title"
                >
                    <Link
                        :href="localeRoute(eventsShow(event.slug))"
                        class="rounded-lg outline-none after:absolute after:inset-0 after:content-['']"
                        data-testid="event-card__link"
                    >
                        {{ event.name }}
                    </Link>
                </component>
                <ul
                    class="mt-1.5 space-y-0.5 text-sm text-muted-foreground"
                    data-testid="event-card__details"
                >
                    <li
                        class="flex items-center gap-1.5"
                        data-testid="event-card__city"
                    >
                        <MapPin class="size-4 shrink-0" aria-hidden="true" />
                        <span class="sr-only"
                            >{{ t('events.fields.city') }}:</span
                        >
                        {{ event.city }}
                    </li>
                    <li
                        class="flex items-center gap-1.5"
                        data-testid="event-card__datetime"
                    >
                        <CalendarDays
                            class="size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <time :datetime="event.dates.from">{{ fullDate }}</time>
                        <span aria-hidden="true">·</span>
                        <span class="sr-only"
                            >{{ t('events.fields.time') }}:</span
                        >
                        {{ event.time ?? t('events.time_tba') }}
                    </li>
                </ul>
            </div>

            <div
                class="mt-auto flex flex-wrap items-center gap-1.5"
                data-testid="event-card__badges"
            >
                <Badge
                    class="border-transparent bg-foreground font-bold text-background"
                    data-testid="event-card__distance"
                >
                    <span class="sr-only"
                        >{{ t('events.fields.distance') }}:</span
                    >
                    {{ formatDistance(event.distance_meters) }}
                </Badge>
                <Badge
                    v-for="race in chips"
                    :key="race.id"
                    variant="outline"
                    data-testid="event-card__race-chip"
                    >{{ formatDistance(race.distance_meters) }}</Badge
                >
                <Badge
                    v-if="hiddenRaces > 0"
                    data-testid="event-card__more-races"
                    variant="outline"
                    :aria-label="t('events.more_races', { count: hiddenRaces })"
                    >{{ t('events.more_races', { count: hiddenRaces }) }}</Badge
                >
                <RegistrationBadge
                    :state="event.registration_state.state"
                    :price-rises-soon="
                        event.registration_state.price_rises_soon
                    "
                    data-testid="event-card__registration"
                />
                <Badge
                    v-if="!event.date_confirmed"
                    variant="outline"
                    class="border-transparent bg-warn font-bold text-warn-foreground"
                    data-testid="event-card__date-unconfirmed"
                    >{{ t('events.status.date_unconfirmed') }}</Badge
                >
            </div>
        </div>
    </article>
</template>
