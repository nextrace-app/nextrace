<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarX } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import type { DistanceClass, Event } from '@/types';

const props = defineProps<{
    events: Event[];
    favorites: number[];
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

const distanceFilters = ['5k', '10k', 'half_marathon', 'marathon', 'ultra'];

function distanceFilterOf(distanceClass: DistanceClass | null): string {
    return distanceClass !== null && distanceFilters.includes(distanceClass)
        ? distanceClass
        : 'kids_other';
}

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
            (month.value === '' || event.date.startsWith(month.value)),
    ),
);
const isFiltered = computed(
    () =>
        category.value !== '' ||
        city.value !== '' ||
        distance.value !== '' ||
        region.value !== '' ||
        month.value !== '',
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
}

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:w-44';
</script>

<template>
    <Head :title="seo.title ?? t('nav.events')" />

    <div class="min-h-screen bg-background py-6 lg:py-8">
        <DefaultHeader />

        <main class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold">
                    {{ t('events.index.title') }}
                </h2>
                <p class="mt-1 text-muted-foreground">
                    {{ t('events.index.subtitle') }}
                </p>
            </div>

            <form
                v-if="events.length > 0"
                class="mb-6 grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-end"
                role="search"
                :aria-label="t('events.filters.label')"
                @submit.prevent
            >
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    {{ t('events.distance_filter') }}
                    <select
                        v-model="distance"
                        :class="selectClass"
                        data-test="filter-distance"
                    >
                        <option value="">{{ t('events.filters.all') }}</option>
                        <option
                            v-for="item in distances"
                            :key="item"
                            :value="item"
                        >
                            {{ t(`events.distance_classes.${item}`) }}
                        </option>
                    </select>
                </label>
                <label
                    v-if="regions.length > 1"
                    class="flex flex-col gap-1.5 text-sm font-medium"
                >
                    {{ t('events.region_filter') }}
                    <select
                        v-model="region"
                        :class="selectClass"
                        data-test="filter-region"
                    >
                        <option value="">{{ t('events.filters.all') }}</option>
                        <option
                            v-for="[slug, name] in regions"
                            :key="slug"
                            :value="slug"
                        >
                            {{ name }}
                        </option>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    {{ t('events.month_filter') }}
                    <select
                        v-model="month"
                        :class="selectClass"
                        data-test="filter-month"
                    >
                        <option value="">{{ t('events.filters.all') }}</option>
                        <option
                            v-for="item in months"
                            :key="item"
                            :value="item"
                        >
                            {{ monthLabel(item) }}
                        </option>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    {{ t('events.filters.category') }}
                    <select v-model="category" :class="selectClass">
                        <option value="">{{ t('events.filters.all') }}</option>
                        <option
                            v-for="item in categories"
                            :key="item"
                            :value="item"
                        >
                            {{ categoryLabel(item) }}
                        </option>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    {{ t('events.filters.city') }}
                    <select v-model="city" :class="selectClass">
                        <option value="">{{ t('events.filters.all') }}</option>
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
                    variant="ghost"
                    @click="resetFilters"
                >
                    {{ t('events.filters.reset') }}
                </Button>
            </form>

            <p class="sr-only" role="status">
                {{ t('events.index.count', { count: visibleEvents.length }) }}
            </p>

            <div
                v-if="visibleEvents.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <EventCard
                    v-for="event in visibleEvents"
                    :key="event.id"
                    :event="event"
                    :favorited="favorites.includes(event.id)"
                />
            </div>

            <div
                v-else
                class="flex flex-col items-center gap-4 rounded-xl border border-dashed py-16 text-center"
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
        </main>
    </div>
</template>
