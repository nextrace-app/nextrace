<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
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
