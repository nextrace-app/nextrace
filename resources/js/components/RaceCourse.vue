<script setup lang="ts">
import { Download } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CourseMap from '@/components/CourseMap.vue';
import ElevationProfile from '@/components/ElevationProfile.vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import type { Race } from '@/types';

/**
 * The course of the races that have a GPX file: map, elevation profile, the
 * figures and the file itself. With several such races a switch picks one,
 * the main race first.
 */
const props = defineProps<{
    races: Race[];
}>();

const { t } = useI18n();
const { locale } = useLocale();

const selected = ref(
    Math.max(
        0,
        props.races.findIndex((race) => race.is_main),
    ),
);

const race = computed(() => props.races[selected.value]);
const course = computed(() => race.value.course!);

const number = (value: number, digits = 0) =>
    new Intl.NumberFormat(locale.value, {
        maximumFractionDigits: digits,
    }).format(value);

const figures = computed(() =>
    [
        {
            key: 'distance',
            value: `${number(course.value.distance_meters / 1000, 1)} ${t('events.distance_unit')}`,
        },
        course.value.elevation_gain_m !== null && {
            key: 'gain',
            value: `+${number(course.value.elevation_gain_m)} m`,
        },
        course.value.elevation_loss_m !== null && {
            key: 'loss',
            value: `−${number(course.value.elevation_loss_m)} m`,
        },
        course.value.highest_point_m !== null && {
            key: 'highest',
            value: `${number(course.value.highest_point_m)} m`,
        },
    ].filter((figure) => figure !== false),
);

const label = (kind: 'start' | 'finish') => {
    const place =
        kind === 'start'
            ? race.value.start_location
            : race.value.finish_location;

    return place
        ? `${t(`events.course.${kind}`)} · ${place}`
        : t(`events.course.${kind}`);
};
</script>

<template>
    <section class="flex flex-col gap-4" data-testid="race-course">
        <h2 class="text-xl font-extrabold tracking-tight">
            {{ t('events.course.title') }}
        </h2>

        <div
            v-if="races.length > 1"
            class="flex flex-wrap gap-2"
            role="group"
            :aria-label="t('events.course.races')"
            data-testid="race-course__switch"
        >
            <button
                v-for="(option, index) in races"
                :key="option.id"
                type="button"
                class="min-h-11 rounded-full border px-4 text-sm font-bold transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :class="
                    index === selected
                        ? 'border-primary bg-secondary text-secondary-foreground'
                        : 'bg-card text-foreground hover:border-primary'
                "
                :aria-pressed="index === selected"
                data-testid="race-course__race"
                @click="selected = index"
            >
                {{ option.name }}
            </button>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <CourseMap
                :track="course.track"
                :start-label="label('start')"
                :finish-label="label('finish')"
            />

            <div class="flex flex-col gap-6 p-6">
                <ElevationProfile
                    v-if="course.profile"
                    :key="race.id"
                    :profile="course.profile"
                />

                <dl
                    class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-4"
                    data-testid="race-course__figures"
                >
                    <div v-for="figure in figures" :key="figure.key">
                        <dt class="text-muted-foreground">
                            {{ t(`events.course.${figure.key}`) }}
                        </dt>
                        <dd class="font-bold">{{ figure.value }}</dd>
                    </div>
                </dl>

                <Button
                    v-if="race.gpx_url"
                    as-child
                    variant="outline"
                    class="self-start max-sm:w-full"
                >
                    <a
                        :href="race.gpx_url"
                        :download="course.gpx_name"
                        data-testid="race-course__download"
                    >
                        <Download aria-hidden="true" />
                        {{ t('events.course.download') }}
                    </a>
                </Button>
            </div>
        </div>
    </section>
</template>
