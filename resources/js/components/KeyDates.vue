<script setup lang="ts">
import { Check } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { useEventFormat } from '@/composables/useEventFormat';
import type { KeyDate } from '@/types';

/**
 * The important dates of an event as a vertical timeline: dates that are over
 * are muted and ticked, the next one is highlighted.
 */
defineProps<{
    dates: KeyDate[];
}>();

const { t } = useI18n();
const { formatDateRange } = useEventFormat();

const when = (date: KeyDate) =>
    formatDateRange(date.from, date.to, { day: 'numeric', month: 'short' });

const label = (date: KeyDate) =>
    date.label ?? t(`events.key_dates.types.${date.type}`);
</script>

<template>
    <section
        class="flex flex-col gap-4 rounded-lg border bg-card p-6"
        data-testid="key-dates"
    >
        <h2 class="text-lg font-extrabold tracking-tight">
            {{ t('events.key_dates.title') }}
        </h2>
        <ol class="flex flex-col">
            <li
                v-for="(date, index) in dates"
                :key="`${date.type}-${date.from}-${index}`"
                class="relative flex gap-3 pb-4 last:pb-0"
                :data-state="date.state"
                data-testid="key-dates__item"
            >
                <span
                    v-if="index < dates.length - 1"
                    class="absolute top-6 bottom-1 left-[9px] w-0.5 bg-border"
                    aria-hidden="true"
                />
                <span
                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-secondary text-secondary-foreground':
                            date.state === 'past',
                        'bg-primary': date.state === 'next',
                        'border-2 border-border bg-card':
                            date.state === 'upcoming',
                    }"
                    aria-hidden="true"
                >
                    <Check
                        v-if="date.state === 'past'"
                        class="size-3"
                        :stroke-width="3"
                    />
                </span>
                <div class="min-w-0 text-sm">
                    <p class="flex flex-wrap items-center gap-2">
                        <time
                            :datetime="date.from"
                            class="font-bold"
                            :class="
                                date.state === 'past'
                                    ? 'text-muted-foreground'
                                    : 'text-foreground'
                            "
                            data-testid="key-dates__date"
                            >{{ when(date) }}</time
                        >
                        <Badge
                            v-if="date.state === 'next'"
                            class="border-transparent bg-primary font-bold text-primary-foreground"
                            data-testid="key-dates__next"
                            >{{ t('events.key_dates.next') }}</Badge
                        >
                        <span v-if="date.state === 'past'" class="sr-only"
                            >({{ t('events.key_dates.past') }})</span
                        >
                    </p>
                    <p
                        class="mt-0.5"
                        :class="
                            date.state === 'past'
                                ? 'text-muted-foreground'
                                : 'text-foreground'
                        "
                        data-testid="key-dates__label"
                    >
                        {{ label(date) }}
                    </p>
                </div>
            </li>
        </ol>
    </section>
</template>
