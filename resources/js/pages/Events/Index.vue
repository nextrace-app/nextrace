<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarX, ChevronLeft, ChevronRight } from '@lucide/vue';
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
    };
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { localeRoute } = useLocale();

const eventsList = ref<HTMLElement | null>(null);

const isFiltered = computed(
    () => props.filters.distance !== null || props.filters.q !== '',
);

function resetFilters() {
    router.visit(localeRoute(eventsIndex()));
}

// Every page of the list has an address of its own, so that it can be opened
// directly and followed by crawlers; the first page is the bare address.
function pageHref(number: number): string {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    if (number === 1) {
        params.delete('page');
    } else {
        params.set('page', String(number));
    }

    const search = params.toString();

    return search === '' ? path : `${path}?${search}`;
}

function scrollToList() {
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

                <nav
                    v-if="pagination.last > 1"
                    class="mt-4 flex items-center justify-center gap-1.5 sm:mt-6"
                    :aria-label="t('events.pagination.label')"
                    data-testid="events-page__pagination"
                >
                    <Button
                        v-if="pagination.current === 1"
                        type="button"
                        variant="outline"
                        size="icon"
                        disabled
                        data-testid="events-page__pagination-previous"
                        :aria-label="t('events.pagination.previous')"
                    >
                        <ChevronLeft class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        v-else
                        as-child
                        variant="outline"
                        size="icon"
                        data-testid="events-page__pagination-previous"
                    >
                        <Link
                            :href="pageHref(pagination.current - 1)"
                            rel="prev"
                            preserve-scroll
                            :aria-label="t('events.pagination.previous')"
                            @success="scrollToList"
                        >
                            <ChevronLeft class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                    <Button
                        v-for="number in pagination.last"
                        :key="number"
                        as-child
                        :variant="
                            number === pagination.current
                                ? 'default'
                                : 'outline'
                        "
                        size="icon"
                        data-testid="events-page__pagination-page"
                    >
                        <Link
                            :href="pageHref(number)"
                            preserve-scroll
                            :aria-current="
                                number === pagination.current
                                    ? 'page'
                                    : undefined
                            "
                            :aria-label="
                                t('events.pagination.page', { number })
                            "
                            @success="scrollToList"
                        >
                            {{ number }}
                        </Link>
                    </Button>
                    <Button
                        v-if="pagination.current === pagination.last"
                        type="button"
                        variant="outline"
                        size="icon"
                        disabled
                        data-testid="events-page__pagination-next"
                        :aria-label="t('events.pagination.next')"
                    >
                        <ChevronRight class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        v-else
                        as-child
                        variant="outline"
                        size="icon"
                        data-testid="events-page__pagination-next"
                    >
                        <Link
                            :href="pageHref(pagination.current + 1)"
                            rel="next"
                            preserve-scroll
                            :aria-label="t('events.pagination.next')"
                            @success="scrollToList"
                        >
                            <ChevronRight class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </nav>

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
