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
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const { formatDate, formatDateRange, formatDistance } = useEventFormat();

const maxChips = 3;

const day = computed(() => formatDate(props.event.date, { day: 'numeric' }));
const month = computed(() =>
    formatDate(props.event.date, { month: 'short' }).replace('.', ''),
);
const fullDate = computed(() =>
    formatDateRange(props.event.date, props.event.ends_on),
);
const otherRaces = computed(() =>
    props.event.races.filter((race) => !race.is_main),
);
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
        class="group relative flex flex-col overflow-hidden rounded-xl border bg-card text-card-foreground shadow-xs transition-[box-shadow,transform,border-color] focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary hover:shadow-lg motion-safe:hover:-translate-y-0.5"
        data-test="event-card"
    >
        <div class="relative aspect-[16/10] overflow-hidden bg-sand">
            <img
                v-if="event.image"
                :src="event.image"
                alt=""
                loading="lazy"
                class="size-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
            />
            <ContourArt v-else :seed="event.slug" :width="480" :height="300" />

            <div
                class="absolute top-3 left-3 flex flex-col items-center rounded-md bg-card px-2.5 py-1 text-card-foreground shadow-xs"
                aria-hidden="true"
            >
                <span class="text-lg leading-tight font-extrabold">{{
                    day
                }}</span>
                <span
                    class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                    >{{ month }}</span
                >
            </div>

            <FavoriteButton
                :event-slug="event.slug"
                :event-name="event.name"
                :favorited="favorited"
                class="absolute top-3 right-3 z-10"
            />

            <Badge
                v-if="statusLabel"
                variant="destructive"
                class="absolute bottom-3 left-3"
                data-test="event-status"
                >{{ statusLabel }}</Badge
            >
        </div>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <div class="min-w-0">
                <h3 class="text-lg leading-snug font-bold">
                    <Link
                        :href="localeRoute(eventsShow(event.slug))"
                        class="rounded-sm outline-none after:absolute after:inset-0 after:content-['']"
                    >
                        {{ event.name }}
                    </Link>
                </h3>
                <ul class="mt-1.5 space-y-0.5 text-sm text-muted-foreground">
                    <li class="flex items-center gap-1.5">
                        <MapPin class="size-4 shrink-0" aria-hidden="true" />
                        <span class="sr-only"
                            >{{ t('events.fields.city') }}:</span
                        >
                        {{ event.city }}
                    </li>
                    <li class="flex items-center gap-1.5">
                        <CalendarDays
                            class="size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <time :datetime="event.date">{{ fullDate }}</time>
                        <span aria-hidden="true">·</span>
                        <span class="sr-only"
                            >{{ t('events.fields.time') }}:</span
                        >
                        {{ event.time ?? t('events.time_tba') }}
                    </li>
                </ul>
            </div>

            <div class="mt-auto flex flex-wrap items-center gap-1.5">
                <Badge
                    class="border-transparent bg-foreground font-bold text-background"
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
                    data-test="race-chip"
                    >{{ formatDistance(race.distance_meters) }}</Badge
                >
                <Badge
                    v-if="hiddenRaces > 0"
                    variant="outline"
                    :aria-label="t('events.more_races', { count: hiddenRaces })"
                    >{{ t('events.more_races', { count: hiddenRaces }) }}</Badge
                >
                <Badge
                    v-if="event.registration.status === 'open'"
                    variant="outline"
                    class="border-olive font-semibold text-olive"
                    >{{ t('events.registration_status.open') }}</Badge
                >
                <Badge
                    v-if="!event.date_confirmed"
                    variant="outline"
                    class="border-amber-600/60 text-amber-800 dark:text-amber-400"
                    data-test="date-unconfirmed"
                    >{{ t('events.status.date_unconfirmed') }}</Badge
                >
            </div>
        </div>
    </article>
</template>
