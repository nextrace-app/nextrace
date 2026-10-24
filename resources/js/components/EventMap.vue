<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    islandPath,
    mapHeight,
    mapWidth,
    placeEvent,
} from '@/composables/useEventPlacement';
import type { Point } from '@/composables/useEventPlacement';
import { useLocale } from '@/composables/useLocale';
import { show as eventsShow } from '@/routes/events';
import type { Event } from '@/types';

const props = defineProps<{
    events: Event[];
    /** The event a detail page is about, drawn larger than the rest. */
    activeSlug?: string | null;
    /** The event under the pointer in the list next to the map. */
    hoverSlug?: string | null;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();

const pins = computed(() =>
    props.events.flatMap((event) => {
        const point: Point | null = placeEvent(event);

        return point ? [{ event, ...point }] : [];
    }),
);

const hrefOf = (slug: string) => localeRoute(eventsShow(slug)).url;
</script>

<template>
    <svg
        :viewBox="`0 0 ${mapWidth} ${mapHeight}`"
        class="block w-full"
        role="group"
        :aria-label="t('events.map.title')"
    >
        <path
            :d="islandPath"
            class="fill-land stroke-border"
            stroke-width="1"
        />
        <a
            v-for="pin in pins"
            :key="pin.event.id"
            :href="hrefOf(pin.event.slug)"
            :aria-label="
                t('events.map.pin', {
                    name: pin.event.name,
                    city: pin.event.city,
                })
            "
            class="group/pin outline-none"
            @click.prevent="router.visit(hrefOf(pin.event.slug))"
        >
            <title>{{ pin.event.name }}</title>
            <circle :cx="pin.x" :cy="pin.y" r="8" fill="transparent" />
            <circle
                v-if="pin.event.slug === activeSlug"
                :cx="pin.x"
                :cy="pin.y"
                r="10"
                class="fill-primary opacity-20"
            />
            <circle
                :cx="pin.x"
                :cy="pin.y"
                :r="
                    pin.event.slug === activeSlug ||
                    pin.event.slug === hoverSlug
                        ? 6
                        : 3.6
                "
                class="fill-primary stroke-card transition-all group-hover/pin:stroke-foreground group-focus-visible/pin:stroke-foreground"
                :class="pin.event.slug === hoverSlug ? 'stroke-foreground' : ''"
                stroke-width="1.5"
            />
        </a>
    </svg>
</template>
