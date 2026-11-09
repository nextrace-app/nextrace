<script setup lang="ts">
import EventCard from '@/components/EventCard.vue';
import type { Event } from '@/types';

/**
 * A row of event cards under the event page: three on desktop, two on
 * tablet and one on mobile, as in the design.
 */
defineProps<{
    title: string;
    events: Event[];
    favorites: number[];
}>();
</script>

<template>
    <section class="flex flex-col gap-4" data-testid="more-races">
        <h2 class="text-xl font-extrabold tracking-tight">{{ title }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <EventCard
                v-for="event in events"
                :key="event.id"
                :event="event"
                :favorited="favorites.includes(event.id)"
                :heading-level="3"
                class="max-sm:not-first:hidden sm:max-lg:nth-3:hidden"
                data-testid="more-races__event"
            />
        </div>
    </section>
</template>
