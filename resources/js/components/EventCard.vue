<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, MapPin, Users } from '@lucide/vue';
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

const emit = defineEmits<{
    hover: [slug: string | null];
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const {
    formatDate,
    formatDateRange,
    formatDistance,
    formatNumber,
    categoryLabel,
} = useEventFormat();

const maxChips = 3;

const day = computed(() => formatDate(props.event.date, { day: 'numeric' }));
const weekday = computed(() =>
    formatDate(props.event.date, { weekday: 'short' }).replace('.', ''),
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
        class="group relative grid grid-cols-[4.5rem_1fr] gap-x-4 rounded-2xl border bg-card p-4 text-card-foreground shadow-xs transition-[box-shadow,transform,border-color] focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary hover:shadow-lg motion-safe:hover:-translate-y-0.5 sm:grid-cols-[4.5rem_1fr_9rem] sm:items-center"
        data-test="event-card"
        @mouseenter="emit('hover', event.slug)"
        @mouseleave="emit('hover', null)"
    >
        <div
            class="flex flex-col items-center self-start rounded-2xl bg-secondary px-1 py-2.5 text-secondary-foreground"
            aria-hidden="true"
        >
            <span class="text-3xl leading-none font-extrabold tracking-tight">{{
                day
            }}</span>
            <span
                class="mt-1 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                >{{ weekday }}</span
            >
        </div>

        <div class="flex min-w-0 flex-col gap-2.5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-lg leading-snug font-bold">
                        <Link
                            :href="localeRoute(eventsShow(event.slug))"
                            class="rounded-sm outline-none after:absolute after:inset-0 after:rounded-2xl after:content-['']"
                        >
                            {{ event.name }}
                        </Link>
                    </h3>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        <time :datetime="event.date">{{ fullDate }}</time>
                    </p>
                </div>
                <FavoriteButton
                    :event-slug="event.slug"
                    :event-name="event.name"
                    :favorited="favorited"
                    class="relative z-10 shrink-0"
                />
            </div>

            <ul
                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
            >
                <li class="flex items-center gap-1.5">
                    <MapPin class="size-4 shrink-0" aria-hidden="true" />
                    <span class="sr-only">{{ t('events.fields.city') }}:</span>
                    {{ event.city }}
                </li>
                <li class="flex items-center gap-1.5">
                    <Clock class="size-4 shrink-0" aria-hidden="true" />
                    <span class="sr-only">{{ t('events.fields.time') }}:</span>
                    {{ event.time ?? t('events.time_tba') }}
                </li>
                <li v-if="event.participants" class="flex items-center gap-1.5">
                    <Users class="size-4 shrink-0" aria-hidden="true" />
                    {{ formatNumber(event.participants) }}
                    {{ t('events.participants') }}
                </li>
            </ul>

            <div class="flex flex-wrap items-center gap-1.5">
                <Badge
                    class="border-transparent bg-foreground font-bold text-background"
                >
                    <span class="sr-only"
                        >{{ t('events.fields.distance') }}:</span
                    >
                    {{ formatDistance(event.distance_meters) }}
                </Badge>
                <Badge variant="secondary" class="font-semibold">{{
                    categoryLabel(event.category)
                }}</Badge>
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
                    v-if="statusLabel"
                    variant="destructive"
                    data-test="event-status"
                    >{{ statusLabel }}</Badge
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

        <div
            class="hidden h-24 overflow-hidden rounded-2xl bg-sand sm:block"
            aria-hidden="true"
        >
            <ContourArt :seed="event.slug" :width="240" :height="160" />
        </div>
    </article>
</template>
