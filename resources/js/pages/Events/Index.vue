<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { Event } from '@/types';

defineProps<{
    events: Event[];
    favorites: number[];
}>();

const { t } = useI18n();
</script>

<template>
    <Head :title="t('nav.events')" />

    <div class="min-h-screen bg-background py-6 lg:py-8">
        <DefaultHeader />

        <div
            class="mx-auto grid w-full max-w-6xl gap-6 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8"
        >
            <EventCard
                v-for="event in events"
                :key="event.id"
                :event="event"
                :favorited="favorites.includes(event.id)"
            />
        </div>
    </div>
</template>
