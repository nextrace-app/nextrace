<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';

/**
 * The elevation profile of a course as an SVG area chart, with the distance
 * marked every 10 km (5 km on short courses) and at the finish.
 */
const props = defineProps<{
    /** [kilometres, metres] at even steps along the course. */
    profile: [number, number][];
}>();

const { t } = useI18n();
const { locale } = useLocale();

const WIDTH = 600;
const HEIGHT = 96;

const total = computed(() => props.profile[props.profile.length - 1][0] || 1);

const range = computed(() => {
    const heights = props.profile.map(([, metres]) => metres);
    const low = Math.min(...heights);
    const high = Math.max(...heights);
    // A flat course still gets some room, so it does not look like a cliff.
    const span = Math.max(high - low, 50);

    return { low: low - span * 0.1, span: span * 1.2 };
});

const points = computed(() =>
    props.profile.map(([km, metres]) => [
        (km / total.value) * WIDTH,
        HEIGHT - ((metres - range.value.low) / range.value.span) * HEIGHT,
    ]),
);

const line = computed(() =>
    points.value
        .map(
            ([x, y], i) =>
                `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`,
        )
        .join(' '),
);

const area = computed(() => `${line.value} L${WIDTH},${HEIGHT} L0,${HEIGHT} Z`);

const kilometres = (km: number) =>
    `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 1 }).format(km)} ${t('events.distance_unit')}`;

const ticks = computed(() => {
    const step = total.value > 25 ? 10 : 5;
    const marks: number[] = [];

    for (let km = step; km < total.value - step / 2; km += step) {
        marks.push(km);
    }

    return [0, ...marks, total.value].map((km) => ({
        label: km === 0 ? `0 ${t('events.distance_unit')}` : kilometres(km),
        left: (km / total.value) * 100,
    }));
});
</script>

<template>
    <figure class="flex flex-col gap-3" data-testid="elevation-profile">
        <figcaption class="text-sm font-bold">
            {{ t('events.course.profile') }}
        </figcaption>
        <svg
            :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
            preserveAspectRatio="none"
            class="h-24 w-full"
            aria-hidden="true"
        >
            <path :d="area" class="fill-secondary" />
            <path
                :d="line"
                class="fill-none stroke-primary"
                stroke-width="2"
                vector-effect="non-scaling-stroke"
            />
        </svg>
        <div class="relative h-[18px] text-[13px] text-muted-foreground">
            <span
                v-for="(tick, index) in ticks"
                :key="index"
                class="absolute top-0 whitespace-nowrap"
                :class="{
                    '-translate-x-1/2': index > 0 && index < ticks.length - 1,
                    '-translate-x-full': index === ticks.length - 1,
                }"
                :style="{ left: `${tick.left}%` }"
                >{{ tick.label }}</span
            >
        </div>
    </figure>
</template>
