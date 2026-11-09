<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { show as eventsShow } from '@/routes/events';
import type { RelatedEvent } from '@/types';

/**
 * A race a post is about, as a card linking to the event page.
 */
defineProps<{
    event: RelatedEvent;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const { formatDate } = useEventFormat();
</script>

<template>
    <div
        class="relative flex items-center gap-4 rounded-lg border bg-card p-4 transition-colors focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary"
        data-testid="related-race"
    >
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <p
                class="text-[11px] leading-[14px] font-bold tracking-wider text-muted-foreground uppercase"
            >
                {{ t('posts.related_race') }}
            </p>
            <Link
                :href="localeRoute(eventsShow(event.slug))"
                class="text-[17px] leading-snug font-bold outline-none after:absolute after:inset-0 after:content-['']"
                data-testid="related-race__link"
                >{{ event.name }}</Link
            >
            <p class="text-sm text-muted-foreground">
                <time :datetime="event.date">{{
                    formatDate(event.date, {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                    })
                }}</time>
                · {{ event.city }}
            </p>
        </div>
        <ChevronRight
            class="size-6 shrink-0 text-muted-foreground"
            aria-hidden="true"
        />
    </div>
</template>
