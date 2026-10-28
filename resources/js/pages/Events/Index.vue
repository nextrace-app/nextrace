<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarX, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import type { DistanceClass, Event } from '@/types';

const props = defineProps<{
    events: Event[];
    favorites: number[];
    perPage: number;
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { categoryLabel, formatDate } = useEventFormat();

const category = ref('');
const city = ref('');
const distance = ref('');
const region = ref('');
const month = ref('');
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

const categories = computed(() => [
    ...new Set(props.events.map((event) => event.category)),
]);
const cities = computed(() =>
    [...new Set(props.events.map((event) => event.city))].sort(),
);
const distances = computed(() => {
    const present = new Set(
        props.events.flatMap((event) =>
            event.races.map((race) => distanceFilterOf(race.distance_class)),
        ),
    );

    return [...distanceFilters, 'kids_other'].filter((item) =>
        present.has(item),
    );
});
const regions = computed(() => {
    const unique = new Map<string, string>();

    props.events.forEach((event) => {
        if (event.region) {
            unique.set(event.region.slug, event.region.name);
        }
    });

    return [...unique].sort((a, b) => a[1].localeCompare(b[1]));
});
const months = computed(() =>
    [...new Set(props.events.map((event) => event.date.slice(0, 7)))].sort(),
);

const visibleEvents = computed(() =>
    props.events.filter(
        (event) =>
            (category.value === '' || event.category === category.value) &&
            (city.value === '' || event.city === city.value) &&
            (distance.value === '' ||
                event.races.some(
                    (race) =>
                        distanceFilterOf(race.distance_class) ===
                        distance.value,
                )) &&
            (region.value === '' || event.region?.slug === region.value) &&
            (month.value === '' || event.date.startsWith(month.value)) &&
            (query.value === '' ||
                `${event.name} ${event.city}`
                    .toLowerCase()
                    .includes(query.value.toLowerCase())),
    ),
);
const isFiltered = computed(
    () =>
        category.value !== '' ||
        city.value !== '' ||
        distance.value !== '' ||
        region.value !== '' ||
        month.value !== '' ||
        query.value !== '',
);

function monthLabel(value: string): string {
    return formatDate(`${value}-01`, { month: 'long', year: 'numeric' });
}

function resetFilters() {
    category.value = '';
    city.value = '';
    distance.value = '';
    region.value = '';
    month.value = '';
    query.value = '';
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
    document
        .getElementById('events-list')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const fieldClass =
    'flex min-w-0 flex-col gap-0.5 rounded-lg px-3.5 py-2 text-[11px] font-bold tracking-widest text-muted-foreground uppercase transition-colors focus-within:bg-secondary hover:bg-secondary/60';
const selectClass =
    'w-full cursor-pointer truncate rounded-sm border-0 bg-transparent py-0.5 text-base font-bold tracking-normal text-foreground normal-case outline-none';
</script>

<template>
    <Head :title="seo.title ?? t('nav.events')" />

    <DefaultShell>
        <main class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <h1 class="sr-only">{{ t('events.index.title') }}</h1>

            <section class="pt-4 sm:pt-6">
                <form
                    v-if="events.length > 0"
                    class="grid grid-cols-2 gap-1 rounded-xl border bg-card p-2 md:grid-cols-3 lg:grid-cols-[repeat(auto-fit,minmax(9rem,1fr))] lg:items-center"
                    role="search"
                    :aria-label="t('events.filters.label')"
                    @submit.prevent
                >
                    <label :class="fieldClass">
                        {{ t('events.distance_filter') }}
                        <select
                            v-model="distance"
                            :class="selectClass"
                            data-test="filter-distance"
                        >
                            <option value="">
                                {{ t('events.filters.all') }}
                            </option>
                            <option
                                v-for="item in distances"
                                :key="item"
                                :value="item"
                            >
                                {{ t(`events.distance_classes.${item}`) }}
                            </option>
                        </select>
                    </label>
                    <label v-if="regions.length > 1" :class="fieldClass">
                        {{ t('events.region_filter') }}
                        <select
                            v-model="region"
                            :class="selectClass"
                            data-test="filter-region"
                        >
                            <option value="">
                                {{ t('events.filters.all') }}
                            </option>
                            <option
                                v-for="[slug, name] in regions"
                                :key="slug"
                                :value="slug"
                            >
                                {{ name }}
                            </option>
                        </select>
                    </label>
                    <label :class="fieldClass">
                        {{ t('events.month_filter') }}
                        <select
                            v-model="month"
                            :class="selectClass"
                            data-test="filter-month"
                        >
                            <option value="">
                                {{ t('events.filters.all') }}
                            </option>
                            <option
                                v-for="item in months"
                                :key="item"
                                :value="item"
                            >
                                {{ monthLabel(item) }}
                            </option>
                        </select>
                    </label>
                    <label :class="fieldClass">
                        {{ t('events.filters.category') }}
                        <select v-model="category" :class="selectClass">
                            <option value="">
                                {{ t('events.filters.all') }}
                            </option>
                            <option
                                v-for="item in categories"
                                :key="item"
                                :value="item"
                            >
                                {{ categoryLabel(item) }}
                            </option>
                        </select>
                    </label>
                    <label :class="fieldClass">
                        {{ t('events.filters.city') }}
                        <select v-model="city" :class="selectClass">
                            <option value="">
                                {{ t('events.filters.all') }}
                            </option>
                            <option
                                v-for="item in cities"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>
                    </label>
                    <Button
                        v-if="isFiltered"
                        type="button"
                        class="col-span-2 m-1 md:col-span-3 lg:col-span-1"
                        @click="resetFilters"
                    >
                        {{ t('events.filters.reset') }}
                    </Button>
                </form>
            </section>

            <div class="pt-6">
                <div class="min-w-0">
                    <div
                        v-if="visibleEvents.length > 0"
                        id="events-list"
                        class="grid scroll-mt-24 gap-5 sm:grid-cols-2 lg:grid-cols-3"
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
                        class="mt-6 flex items-center justify-center gap-1.5"
                        :aria-label="t('events.pagination.label')"
                        data-test="pagination"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :disabled="currentPage === 1"
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
                            :aria-label="
                                t('events.pagination.page', { number })
                            "
                            @click="goToPage(number)"
                        >
                            {{ number }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :disabled="currentPage === pageCount"
                            :aria-label="t('events.pagination.next')"
                            @click="goToPage(currentPage + 1)"
                        >
                            <ChevronRight class="size-4" aria-hidden="true" />
                        </Button>
                    </nav>

                    <div
                        v-else
                        class="flex flex-col items-center gap-4 rounded-3xl border border-dashed bg-card py-16 text-center"
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
                            @click="resetFilters"
                        >
                            {{ t('events.filters.reset') }}
                        </Button>
                    </div>
                </div>
            </div>
        </main>
    </DefaultShell>
</template>
