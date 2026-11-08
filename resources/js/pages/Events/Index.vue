<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarX } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type { Event } from '@/types';

const props = defineProps<{
    events: Event[];
    favorites: number[];
    pagination: {
        current: number;
        last: number;
        total: number;
        perPage: number;
    };
    filters: {
        distance: string | null;
        q: string;
        open: boolean;
    };
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { localeRoute } = useLocale();

const eventsList = ref<HTMLElement | null>(null);

const isFiltered = computed(
    () =>
        props.filters.distance !== null ||
        props.filters.q !== '' ||
        props.filters.open,
);

/** The current address with the "open" filter switched; the page starts again from 1. */
const openFilterHref = computed(() => {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    params.delete('page');

    if (props.filters.open) {
        params.delete('open');
    } else {
        params.set('open', '1');
    }

    const search = params.toString();

    return search === '' ? path : `${path}?${search}`;
});

function resetFilters() {
    router.visit(localeRoute(eventsIndex()));
}

function scrollToList() {
    eventsList.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <Head :title="seo.title ?? t('nav.races')" />

    <DefaultShell flush>
        <main
            class="mx-auto w-full max-w-6xl px-4 sm:px-6"
            data-testid="events-page"
        >
            <h1 class="sr-only" data-testid="events-page__title">
                {{ t('events.index.title') }}
            </h1>

            <div class="py-4 sm:py-6">
                <div class="mb-4 flex flex-wrap gap-2 sm:mb-6">
                    <Link
                        :href="openFilterHref"
                        preserve-scroll
                        class="inline-flex min-h-11 items-center gap-2 rounded-full border px-4 text-sm font-bold outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:min-h-10"
                        :class="
                            filters.open
                                ? 'border-primary bg-secondary text-secondary-foreground'
                                : 'bg-card hover:border-primary'
                        "
                        :aria-pressed="filters.open"
                        role="button"
                        data-testid="events-page__filter-open"
                    >
                        <span
                            class="size-2 rounded-full"
                            :class="
                                filters.open
                                    ? 'bg-primary'
                                    : 'bg-muted-foreground'
                            "
                            aria-hidden="true"
                        />
                        {{ t('events.filters.registration_open') }}
                    </Link>
                </div>
                <div
                    v-if="events.length > 0"
                    ref="eventsList"
                    data-testid="events-page__list"
                    class="grid scroll-mt-24 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3"
                >
                    <EventCard
                        v-for="event in events"
                        :key="event.id"
                        :event="event"
                        :heading-level="2"
                        :favorited="favorites.includes(event.id)"
                    />
                </div>

                <ListPagination
                    :pagination="pagination"
                    testid="events-page__pagination"
                    @navigated="scrollToList"
                />

                <div
                    v-if="events.length === 0"
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
