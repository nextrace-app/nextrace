<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Clock,
    ExternalLink,
    Map,
    MapPin,
    Route,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type { Event } from '@/types';

const props = defineProps<{
    event: Event;
    isFavorite: boolean;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const { formatDate, formatDistance, formatNumber, categoryLabel, mapUrl } =
    useEventFormat();

const fullDate = computed(() =>
    formatDate(props.event.date, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }),
);
const map = computed(() => mapUrl(props.event));
</script>

<template>
    <Head :title="event.name" />

    <div class="min-h-screen bg-background py-6 lg:py-8">
        <DefaultHeader />

        <main class="mx-auto w-full max-w-4xl px-4 sm:px-6 lg:px-8">
            <Link
                :href="localeRoute(eventsIndex())"
                class="mb-6 inline-flex items-center gap-1 rounded-sm text-sm text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ t('events.back') }}
            </Link>

            <article
                class="overflow-hidden rounded-xl border bg-card shadow-xs"
            >
                <header
                    class="relative bg-primary px-5 py-8 text-primary-foreground sm:px-8 sm:py-10"
                >
                    <img
                        v-if="event.image"
                        :src="event.image"
                        :alt="event.name"
                        class="absolute inset-0 h-full w-full object-cover opacity-15"
                    />
                    <div class="relative flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-4">
                            <Badge
                                variant="secondary"
                                data-test="event-category"
                                >{{ categoryLabel(event.category) }}</Badge
                            >
                            <FavoriteButton
                                :event-id="event.id"
                                :favorited="isFavorite"
                                class="shrink-0 text-foreground"
                            />
                        </div>
                        <h1
                            class="text-2xl leading-tight font-semibold text-balance sm:text-4xl"
                        >
                            {{ event.name }}
                        </h1>
                        <ul
                            class="flex flex-col gap-2 text-sm sm:flex-row sm:flex-wrap sm:gap-x-6 sm:text-base"
                        >
                            <li class="flex items-center gap-2">
                                <Calendar
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="sr-only"
                                    >{{ t('events.fields.date') }}:</span
                                >
                                <time :datetime="event.date">{{
                                    fullDate
                                }}</time>
                            </li>
                            <li
                                v-if="event.time"
                                class="flex items-center gap-2"
                            >
                                <Clock
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="sr-only"
                                    >{{ t('events.fields.time') }}:</span
                                >
                                {{ event.time }}
                            </li>
                            <li class="flex items-center gap-2">
                                <MapPin
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="sr-only"
                                    >{{ t('events.fields.location') }}:</span
                                >
                                {{ event.location }}
                            </li>
                            <li class="flex items-center gap-2">
                                <Route
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="sr-only"
                                    >{{ t('events.fields.distance') }}:</span
                                >
                                {{ formatDistance(event.distance_meters) }}
                            </li>
                            <li
                                v-if="event.participants"
                                class="flex items-center gap-2"
                            >
                                <Users
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                {{ formatNumber(event.participants) }}
                                {{ t('events.participants') }}
                            </li>
                        </ul>
                    </div>
                </header>

                <div class="flex flex-col gap-6 p-5 sm:p-8">
                    <div
                        v-if="event.registration_url || map"
                        class="flex flex-col gap-3 sm:flex-row"
                    >
                        <Button
                            v-if="event.registration_url"
                            as-child
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            <a
                                :href="event.registration_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ t('events.register') }}
                                <ExternalLink aria-hidden="true" />
                            </a>
                        </Button>
                        <Button
                            v-if="map"
                            as-child
                            variant="outline"
                            size="lg"
                            class="w-full sm:w-auto"
                        >
                            <a
                                :href="map"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Map aria-hidden="true" />
                                {{ t('events.view_map') }}
                            </a>
                        </Button>
                    </div>

                    <section>
                        <h2 class="mb-2 text-lg font-semibold">
                            {{ t('events.about') }}
                        </h2>
                        <p
                            v-if="event.description"
                            class="leading-relaxed whitespace-pre-line text-muted-foreground"
                        >
                            {{ event.description }}
                        </p>
                        <p v-else class="text-muted-foreground">
                            {{ t('events.no_description') }}
                        </p>
                    </section>
                </div>
            </article>
        </main>
    </div>
</template>
