<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarX } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import type { Event } from '@/types';

const props = defineProps<{
    events: Event[];
    favorites: number[];
}>();

const { t } = useI18n();
const { categoryLabel } = useEventFormat();

const category = ref('');
const city = ref('');

const categories = computed(() => [
    ...new Set(props.events.map((event) => event.category)),
]);
const cities = computed(() =>
    [...new Set(props.events.map((event) => event.city))].sort(),
);

const visibleEvents = computed(() =>
    props.events.filter(
        (event) =>
            (category.value === '' || event.category === category.value) &&
            (city.value === '' || event.city === city.value),
    ),
);
const isFiltered = computed(() => category.value !== '' || city.value !== '');

function resetFilters() {
    category.value = '';
    city.value = '';
}

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:w-52';
</script>

<template>
    <Head :title="t('nav.events')" />

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
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end"
                role="search"
                :aria-label="t('events.filters.label')"
                @submit.prevent
            >
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
