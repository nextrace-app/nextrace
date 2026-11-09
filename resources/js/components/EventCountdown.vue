<script setup lang="ts">
import { Calendar, CalendarPlus } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useEventFormat } from '@/composables/useEventFormat';
import type { EventCountdown } from '@/types';

/**
 * How long until the main race starts, with a menu to put it in a calendar.
 * "Today" is the date in Athens as the server saw it, so the visitor's clock
 * and time zone do not move the count.
 */
const props = defineProps<{
    countdown: EventCountdown;
}>();

const { t } = useI18n();
const { formatDate, formatTime } = useEventFormat();

const days = computed(() => {
    const day = (value: string) => Date.parse(`${value}T00:00:00Z`);

    return Math.round(
        (day(props.countdown.starts_on) - day(props.countdown.today)) /
            86_400_000,
    );
});

const headline = computed(() => {
    if (days.value === 0) {
        return t('events.countdown.today');
    }

    if (days.value === 1) {
        return t('events.countdown.tomorrow');
    }

    return t('events.countdown.days', { n: days.value }, days.value);
});

const when = computed(() => {
    const date = formatDate(props.countdown.starts_on, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    return props.countdown.time
        ? `${date}, ${formatTime(props.countdown.starts_on, props.countdown.time)}`
        : date;
});
</script>

<template>
    <section
        class="flex flex-col gap-2 rounded-lg bg-secondary p-6 text-secondary-foreground"
        data-testid="event-countdown"
    >
        <p
            class="text-[11px] leading-[14px] font-bold tracking-wider uppercase"
        >
            {{
                days > 1
                    ? t('events.countdown.starts_in')
                    : t('events.countdown.starts')
            }}
        </p>
        <p
            class="text-5xl leading-none font-extrabold tracking-tight"
            data-testid="event-countdown__headline"
        >
            {{ headline }}
        </p>
        <p class="text-sm" data-testid="event-countdown__when">
            <time
                :datetime="
                    countdown.time
                        ? `${countdown.starts_on}T${countdown.time}`
                        : countdown.starts_on
                "
                >{{ when }}</time
            >
        </p>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    class="mt-2 h-11 w-full bg-card text-foreground"
                    data-testid="event-countdown__calendar"
                >
                    <CalendarPlus aria-hidden="true" />
                    {{ t('events.countdown.add_to_calendar') }}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                class="w-(--reka-dropdown-menu-trigger-width) min-w-56"
                data-testid="event-countdown__menu"
            >
                <DropdownMenuItem
                    v-for="item in [
                        {
                            key: 'apple',
                            href: countdown.ics_url,
                            download: true,
                        },
                        {
                            key: 'google',
                            href: countdown.google_url,
                            download: false,
                        },
                        {
                            key: 'outlook',
                            href: countdown.ics_url,
                            download: true,
                        },
                    ]"
                    :key="item.key"
                    as-child
                    class="min-h-11"
                >
                    <a
                        :href="item.href"
                        :download="item.download ? '' : undefined"
                        :target="item.download ? undefined : '_blank'"
                        :rel="item.download ? undefined : 'noopener noreferrer'"
                        :data-testid="`event-countdown__${item.key}`"
                    >
                        <Calendar aria-hidden="true" />
                        {{ t(`events.countdown.${item.key}`) }}
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </section>
</template>
