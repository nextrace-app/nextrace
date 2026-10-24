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
const { formatDate, formatDistance, formatNumber, categoryLabel } =
    useEventFormat();

const day = computed(() => formatDate(props.event.date, { day: 'numeric' }));
const month = computed(() =>
    formatDate(props.event.date, { month: 'short' }).replace('.', ''),
);
const fullDate = computed(() => formatDate(props.event.date));
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
                            :href="localeRoute(eventsShow(event.id))"
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
                    :event-id="event.id"
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

            <div>
                <Badge variant="secondary">{{
                    categoryLabel(event.category)
                }}</Badge>
            </div>
        </div>
    </article>
</template>
