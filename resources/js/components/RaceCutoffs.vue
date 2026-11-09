<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import type { Cutoff } from '@/types';

/**
 * The cut-off times of a race as a collapsible table: each checkpoint with the
 * moment it closes. The day is added when a checkpoint closes on another day
 * than the race starts, as in ultras that run through the night.
 */
const props = defineProps<{
    cutoffs: Cutoff[];
    /** The day the race starts, Y-m-d. */
    raceDate: string;
}>();

const { t } = useI18n();
const { locale } = useLocale();
const { formatDateTimeString } = useEventFormat();

const distance = (cutoff: Cutoff) =>
    cutoff.is_finish
        ? t('events.cutoffs.finish')
        : `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 1 }).format(cutoff.distance_meters / 1000)} ${t('events.distance_unit')}`;

const closesAt = (cutoff: Cutoff) =>
    formatDateTimeString(
        cutoff.closes_at,
        cutoff.closes_at.slice(0, 10) === props.raceDate
            ? { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }
            : {
                  day: 'numeric',
                  month: 'short',
                  hour: '2-digit',
                  minute: '2-digit',
                  hourCycle: 'h23',
              },
    );
</script>

<template>
    <details class="group mt-6" open data-testid="race-cutoffs">
        <summary
            class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 text-sm font-bold [&::-webkit-details-marker]:hidden"
        >
            {{ t('events.cutoffs.title') }}
            <ChevronDown
                class="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                aria-hidden="true"
            />
        </summary>
        <table class="mt-2 w-full overflow-hidden rounded-lg border text-sm">
            <thead class="bg-background text-muted-foreground">
                <tr>
                    <th scope="col" class="px-3 py-2 text-left font-normal">
                        {{ t('events.cutoffs.checkpoint') }}
                    </th>
                    <th scope="col" class="px-3 py-2 text-right font-normal">
                        {{ t('events.cutoffs.closes_at') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y border-t">
                <tr
                    v-for="(cutoff, index) in cutoffs"
                    :key="index"
                    data-testid="race-cutoffs__row"
                >
                    <th scope="row" class="px-3 py-2 text-left font-normal">
                        {{ distance(cutoff) }}
                    </th>
                    <td class="px-3 py-2 text-right font-bold">
                        <time :datetime="cutoff.closes_at.replace(' ', 'T')">{{
                            closesAt(cutoff)
                        }}</time>
                    </td>
                </tr>
            </tbody>
        </table>
    </details>
</template>
