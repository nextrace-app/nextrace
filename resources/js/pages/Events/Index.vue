<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarX, MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import { placeEvent } from '@/composables/useEventPlacement';
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

// Events grouped under their month, in date order, for the timeline.
const monthGroups = computed(() => {
    const groups: { key: string; events: Event[] }[] = [];

    [...visibleEvents.value]
        .sort((a, b) => a.date.localeCompare(b.date))
        .forEach((event) => {
            const key = event.date.slice(0, 7);
            const last = groups[groups.length - 1];

            if (last?.key === key) {
                last.events.push(event);
            } else {
                groups.push({ key, events: [event] });
            }
        });

    return groups;
});

const hoverSlug = ref<string | null>(null);
const showMap = ref(false);
const hasPins = computed(() =>
    visibleEvents.value.some((event) => placeEvent(event) !== null),
);

const fieldClass =
    'flex min-w-0 flex-col gap-0.5 rounded-2xl px-3.5 py-2 text-[11px] font-bold tracking-widest text-muted-foreground uppercase transition-colors focus-within:bg-secondary hover:bg-secondary/60';
const selectClass =
    'w-full cursor-pointer truncate rounded-sm border-0 bg-transparent py-0.5 text-base font-bold tracking-normal text-foreground normal-case outline-none';
</script>

<template>
    <Head :title="seo.title ?? t('nav.events')" />

    <DefaultShell>
        <main class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <section class="pt-8 pb-2 sm:pt-12">
                <p
                    class="text-xs font-bold tracking-[0.12em] text-olive uppercase"
                >
                    {{ t('events.index.eyebrow') }}
                </p>
                <h1
                    class="mt-2 max-w-3xl text-4xl leading-[1.08] font-extrabold tracking-tight text-balance sm:text-5xl"
                >
                    {{ t('events.index.title') }}
                </h1>
                <p class="mt-3 max-w-xl text-lg text-muted-foreground">
                    {{ t('events.index.subtitle') }}
                </p>

                <form
                    v-if="events.length > 0"
                    class="mt-7 grid grid-cols-2 gap-1 rounded-3xl border bg-card p-2 shadow-[0_10px_30px_-12px_rgb(34_49_58/0.18)] md:grid-cols-3 lg:grid-cols-[repeat(auto-fit,minmax(9rem,1fr))] lg:items-center"
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

            <div
                class="grid gap-6 pt-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-10"
            >
                <div class="min-w-0">
                    <p
                        class="mb-4 font-semibold text-muted-foreground"
                        role="status"
                    >
                        {{
                            t(
                                'events.index.count',
                                { count: visibleEvents.length },
                                visibleEvents.length,
                            )
                        }}
                    </p>

                    <ol
                        v-if="visibleEvents.length > 0"
                        class="relative flex flex-col gap-8 pl-7 before:absolute before:top-2 before:bottom-0 before:left-[7px] before:w-0.5 before:bg-border before:content-['']"
                    >
                        <li
                            v-for="group in monthGroups"
                            :key="group.key"
                            class="relative"
                            data-test="month-group"
                        >
                            <h2
                                class="relative mb-3.5 flex flex-wrap items-baseline gap-x-2 text-2xl leading-tight font-extrabold tracking-tight capitalize before:absolute before:top-[0.35rem] before:-left-7 before:size-4 before:rounded-full before:border-[3px] before:border-primary before:bg-background before:content-['']"
                            >
                                {{ monthLabel(group.key) }}
                                <span
                                    class="text-sm font-medium tracking-normal text-muted-foreground normal-case"
                                    >&middot;
                                    {{
                                        t(
                                            'events.index.month_count',
                                            { count: group.events.length },
                                            group.events.length,
                                        )
                                    }}</span
                                >
                            </h2>
                            <div class="flex flex-col gap-3">
                                <EventCard
                                    v-for="event in group.events"
                                    :key="event.id"
                                    :event="event"
                                    :favorited="favorites.includes(event.id)"
                                    @hover="hoverSlug = $event"
                                />
                            </div>
                        </li>
                    </ol>

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

                <aside
                    v-if="hasPins"
                    id="events-map"
                    class="order-first lg:order-none"
                    :class="showMap ? 'block' : 'hidden lg:block'"
                >
                    <div
                        class="rounded-2xl border bg-secondary p-4 lg:sticky lg:top-24"
                    >
                        <h2 class="mb-2 text-sm font-bold">
                            {{ t('events.map.title') }}
                        </h2>
                        <EventMap
                            :events="visibleEvents"
                            :hover-slug="hoverSlug"
                        />
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ t('events.map.note') }}
                        </p>
                    </div>
                </aside>
            </div>

            <button
                v-if="hasPins"
                type="button"
                class="fixed bottom-5 left-1/2 z-20 inline-flex -translate-x-1/2 items-center gap-2 rounded-full bg-foreground px-5 py-3 text-sm font-bold whitespace-nowrap text-background shadow-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring lg:hidden"
                :aria-expanded="showMap"
                aria-controls="events-map"
                data-test="toggle-map"
                @click="showMap = !showMap"
            >
                <MapPin class="size-4" aria-hidden="true" />
                {{ showMap ? t('events.map.hide') : t('events.map.show') }}
            </button>
        </main>
    </DefaultShell>
</template>
