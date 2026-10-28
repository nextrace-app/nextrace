<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        seed: string;
        width?: number;
        height?: number;
    }>(),
    { width: 400, height: 220 },
);

function random(seed: number): () => number {
    let state = seed;

    return () => {
        state =
            (Math.imul(state ^ (state >>> 15), 2246822507) + 1013904223) >>> 0;

        return state / 4294967296;
    };
}

function seedOf(text: string): number {
    let hash = 2166136261;

    for (const char of text) {
        hash = Math.imul(hash ^ char.charCodeAt(0), 16777619);
    }

    return hash >>> 0;
}

function smooth(points: [number, number][]): string {
    const count = points.length;
    let path = `M${points[0][0].toFixed(1)} ${points[0][1].toFixed(1)}`;

    for (let i = 0; i < count; i++) {
        const a = points[(i - 1 + count) % count];
        const b = points[i];
        const c = points[(i + 1) % count];
        const d = points[(i + 2) % count];

        path += `C${(b[0] + (c[0] - a[0]) / 6).toFixed(1)} ${(b[1] + (c[1] - a[1]) / 6).toFixed(1)} ${(c[0] - (d[0] - b[0]) / 6).toFixed(1)} ${(c[1] - (d[1] - b[1]) / 6).toFixed(1)} ${c[0].toFixed(1)} ${c[1].toFixed(1)}`;
    }

    return `${path}Z`;
}

// Topographic rings, unique per race so every card has its own little landscape.
const rings = computed(() => {
    const next = random(seedOf(props.seed));
    const cx = props.width * (0.3 + next() * 0.4);
    const cy = props.height * (0.35 + next() * 0.3);
    const step = props.width / 25;

    return Array.from({ length: 10 }, (_, index) => {
        const radius = step * 0.9 + index * step;
        const points: [number, number][] = Array.from(
            { length: 10 },
            (_, k) => {
                const angle = (k / 10) * Math.PI * 2;
                const jitter = 1 + (next() - 0.5) * 0.2;

                return [
                    cx + Math.cos(angle) * radius * 1.6 * jitter,
                    cy + Math.sin(angle) * radius * jitter,
                ];
            },
        );

        return {
            path: smooth(points),
            opacity: (0.95 - index * 0.06).toFixed(2),
        };
    });
});
</script>

<template>
    <svg
        :viewBox="`0 0 ${width} ${height}`"
        preserveAspectRatio="xMidYMid slice"
        aria-hidden="true"
        class="h-full w-full"
        data-testid="contour-art"
    >
        <path
            v-for="(ring, index) in rings"
            :key="index"
            :d="ring.path"
            fill="none"
            class="stroke-contour"
            :stroke-opacity="ring.opacity"
            stroke-width="1.4"
        />
    </svg>
</template>
