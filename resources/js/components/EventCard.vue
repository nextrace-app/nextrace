<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin, Route, Users } from '@lucide/vue';
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
const {
    formatDate,
    formatDateRange,
    formatDistance,
    formatNumber,
    categoryLabel,
} = useEventFormat();

const maxChips = 4;

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
        class="group relative flex gap-4 rounded-xl border bg-card p-4 text-card-foreground shadow-xs transition-shadow focus-within:ring-[3px] focus-within:ring-ring/50 hover:shadow-md"
        data-test="event-card"
    >
        <div
            class="flex size-16 shrink-0 flex-col items-center justify-center rounded-lg bg-primary text-primary-foreground"
            aria-hidden="true"
        >
            <span class="text-2xl leading-none font-semibold">{{ day }}</span>
            <span class="mt-1 text-xs tracking-wide uppercase">{{
                month
            }}</span>
        </div>

        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base leading-snug font-semibold">
                        <Link
                            :href="localeRoute(eventsShow(event.slug))"
                            class="rounded-sm outline-none after:absolute after:inset-0 after:content-['']"
                        >
                            {{ event.name }}
                        </Link>
                    </h3>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        <time :datetime="event.date">{{ fullDate }}</time>
                        <template v-if="event.time">
                            &middot; {{ event.time }}
                        </template>
                    </p>
                </div>
                <FavoriteButton
                    :event-slug="event.slug"
                    :favorited="favorited"
                    class="relative z-10 shrink-0"
                />
            </div>

            <ul
                class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-muted-foreground"
            >
                <li class="flex items-center gap-1.5">
                    <MapPin class="size-4 shrink-0" aria-hidden="true" />
                    <span class="sr-only">{{ t('events.fields.city') }}:</span>
                    {{ event.city }}
                </li>
                <li class="flex items-center gap-1.5">
                    <Route class="size-4 shrink-0" aria-hidden="true" />
                    <span class="sr-only"
                        >{{ t('events.fields.distance') }}:</span
                    >
                    {{ formatDistance(event.distance_meters) }}
                </li>
                <li v-if="event.participants" class="flex items-center gap-1.5">
                    <Users class="size-4 shrink-0" aria-hidden="true" />
                    {{ formatNumber(event.participants) }}
                    {{ t('events.participants') }}
                </li>
            </ul>

            <div class="flex flex-wrap items-center gap-1.5">
                <Badge variant="secondary">{{
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
                    v-if="statusLabel"
                    variant="destructive"
                    data-test="event-status"
                    >{{ statusLabel }}</Badge
                >
                <Badge
                    v-if="!event.date_confirmed"
                    variant="outline"
                    class="border-amber-500/60 text-amber-700 dark:text-amber-400"
                    data-test="date-unconfirmed"
                    >{{ t('events.status.date_unconfirmed') }}</Badge
                >
            </div>
        </div>
    </article>
</template>
