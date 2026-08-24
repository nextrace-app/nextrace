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

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <DefaultHeader />

        <div class="mx-auto grid max-w-6xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <EventCard
                v-for="event in events"
                :key="event.id"
                :event="event"
                :favorited="favorites.includes(event.id)"
            />
        </div>
    </div>
</template>
