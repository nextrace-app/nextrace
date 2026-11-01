<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CalendarX, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type { DistanceClass, Event } from '@/types';

const props = defineProps<{
    events: Event[];
    favorites: number[];
    perPage: number;
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { localeRoute } = useLocale();

const eventsList = ref<HTMLElement | null>(null);
const distance = ref('');
const query = ref('');

const distanceFilters = ['5k', '10k', 'half_marathon', 'marathon', 'ultra'];

function distanceFilterOf(distanceClass: DistanceClass | null): string {
    return distanceClass !== null && distanceFilters.includes(distanceClass)
        ? distanceClass
        : 'kids_other';
}

// The header search and the category links send their choice in the URL.
function syncFromUrl() {
    const params = new URLSearchParams(page.url.split('?')[1] ?? '');
    const wanted = params.get('distance') ?? '';

    distance.value = [...distanceFilters, 'kids_other'].includes(wanted)
        ? wanted
        : '';
    query.value = params.get('q') ?? '';
}

syncFromUrl();
watch(() => page.url, syncFromUrl);

const visibleEvents = computed(() =>
    props.events.filter(
        (event) =>
            (distance.value === '' ||
                event.races.some(
                    (race) =>
                        distanceFilterOf(race.distance_class) ===
                        distance.value,
                )) &&
            (query.value === '' ||
                `${event.name} ${event.city}`
                    .toLowerCase()
                    .includes(query.value.toLowerCase())),
    ),
);
const isFiltered = computed(() => distance.value !== '' || query.value !== '');

function resetFilters() {
    router.visit(localeRoute(eventsIndex()));
}

// Visible events in date order.
const sortedEvents = computed(() =>
    [...visibleEvents.value].sort((a, b) => a.date.localeCompare(b.date)),
);

const currentPage = ref(1);
const pageCount = computed(() =>
    Math.max(1, Math.ceil(sortedEvents.value.length / props.perPage)),
);
const pagedEvents = computed(() =>
    sortedEvents.value.slice(
        (currentPage.value - 1) * props.perPage,
        currentPage.value * props.perPage,
    ),
);

watch(visibleEvents, () => {
    currentPage.value = 1;
});

function goToPage(value: number) {
    currentPage.value = Math.min(Math.max(1, value), pageCount.value);
    eventsList.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <Head :title="seo.title ?? t('nav.events')" />

    <DefaultShell flush>
        <main
            class="mx-auto w-full max-w-6xl px-4 sm:px-6"
            data-testid="events-page"
        >
            <h1 class="sr-only" data-testid="events-page__title">
                {{ t('events.index.title') }}
            </h1>

            <div class="py-4 sm:py-6">
                <div
                    v-if="visibleEvents.length > 0"
                    ref="eventsList"
                    data-testid="events-page__list"
                    class="grid scroll-mt-24 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3"
                >
                    <EventCard
                        v-for="event in pagedEvents"
                        :key="event.id"
                        :event="event"
                        :favorited="favorites.includes(event.id)"
                    />
                </div>

                <nav
                    v-if="pageCount > 1"
                    class="mt-4 flex items-center justify-center gap-1.5 sm:mt-6"
                    :aria-label="t('events.pagination.label')"
                    data-testid="events-page__pagination"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        :disabled="currentPage === 1"
                        data-testid="events-page__pagination-previous"
                        :aria-label="t('events.pagination.previous')"
                        @click="goToPage(currentPage - 1)"
                    >
                        <ChevronLeft class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        v-for="number in pageCount"
                        :key="number"
                        type="button"
                        :variant="
                            number === currentPage ? 'default' : 'outline'
                        "
                        size="icon"
                        :aria-current="
                            number === currentPage ? 'page' : undefined
                        "
                        :aria-label="t('events.pagination.page', { number })"
                        data-testid="events-page__pagination-page"
                        @click="goToPage(number)"
                    >
                        {{ number }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        :disabled="currentPage === pageCount"
                        data-testid="events-page__pagination-next"
                        :aria-label="t('events.pagination.next')"
                        @click="goToPage(currentPage + 1)"
                    >
                        <ChevronRight class="size-4" aria-hidden="true" />
                    </Button>
                </nav>

                <div
                    v-if="visibleEvents.length === 0"
                    class="flex flex-col items-center gap-4 rounded-lg border border-dashed bg-card py-16 text-center"
                    data-testid="events-page__empty"
                >
                    <CalendarX
                        class="size-8 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p class="max-w-sm text-muted-foreground">
                        {{
                            isFiltered
                                ? t('events.index.no_matches')
                                : t('events.index.empty')
                        }}
                    </p>
                    <Button
                        v-if="isFiltered"
                        variant="outline"
                        data-testid="events-page__reset"
                        @click="resetFilters"
                    >
                        {{ t('events.filters.reset') }}
                    </Button>
                </div>
            </div>
        </main>
    </DefaultShell>
</template>
