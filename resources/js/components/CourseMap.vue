<script setup lang="ts">
import type * as Leaflet from 'leaflet';
import type { LatLngTuple, Map as LeafletMap, LayerGroup } from 'leaflet';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

/**
 * The course drawn on OpenStreetMap with its start and finish. Leaflet and the
 * map tiles load only once the map scrolls near the screen, so a visitor who
 * never reaches the block downloads nothing.
 */
const props = defineProps<{
    track: [number, number][];
    startLabel: string;
    finishLabel: string;
}>();

const { t } = useI18n();
const container = ref<HTMLElement | null>(null);

let map: LeafletMap | null = null;
let layers: LayerGroup | null = null;
let leaflet: typeof Leaflet | null = null;
let observer: IntersectionObserver | null = null;

function draw(): void {
    if (!map || !layers || !leaflet || props.track.length < 2) {
        return;
    }

    const color =
        getComputedStyle(document.documentElement)
            .getPropertyValue('--primary')
            .trim() || '#0f7a5c';
    const line = props.track as LatLngTuple[];

    layers.clearLayers();
    leaflet.polyline(line, { color, weight: 4, opacity: 0.9 }).addTo(layers);

    for (const [point, label] of [
        [line[0], props.startLabel],
        [line[line.length - 1], props.finishLabel],
    ] as const) {
        leaflet
            .circleMarker(point, {
                radius: 7,
                color,
                weight: 3,
                fillColor: '#ffffff',
                fillOpacity: 1,
            })
            .bindTooltip(label, {
                permanent: true,
                direction: 'top',
                offset: [0, -8],
                className: 'course-map__label',
            })
            .addTo(layers);
    }

    map.fitBounds(leaflet.latLngBounds(line), { padding: [24, 24] });
}

async function load(): Promise<void> {
    if (map || !container.value) {
        return;
    }

    const [module] = await Promise.all([
        import('leaflet'),
        import('leaflet/dist/leaflet.css'),
    ]);

    leaflet = module.default ?? module;
    map = leaflet.map(container.value, {
        scrollWheelZoom: false,
        attributionControl: true,
    });
    map.attributionControl.setPrefix(false);
    leaflet
        .tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
        })
        .addTo(map);
    layers = leaflet.layerGroup().addTo(map);
    draw();
}

onMounted(() => {
    if (!container.value) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        void load();

        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer?.disconnect();
                void load();
            }
        },
        { rootMargin: '200px' },
    );
    observer.observe(container.value);
});

watch(() => props.track, draw);

onBeforeUnmount(() => {
    observer?.disconnect();
    map?.remove();
    map = null;
});
</script>

<template>
    <div
        ref="container"
        class="course-map relative z-0 h-[280px] w-full bg-secondary"
        role="img"
        :aria-label="t('events.course.map')"
        data-testid="course-map"
    ></div>
</template>
