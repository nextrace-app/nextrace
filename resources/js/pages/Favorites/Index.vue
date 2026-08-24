<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type { Event } from '@/types';

const props = defineProps<{
    events: Event[];
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
</script>

<template>
    <Head :title="t('favorites.title')" />

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <DefaultHeader />

        <div class="mx-auto max-w-6xl">
            <h2 class="mb-6 text-2xl font-semibold">
                {{ t('favorites.title') }}
            </h2>

            <div
                v-if="props.events.length === 0"
                class="flex flex-col items-center gap-4 rounded-xl border border-dashed py-16 text-center"
            >
                <Heart class="size-8 text-muted-foreground" />
                <p class="text-muted-foreground">{{ t('favorites.empty') }}</p>
                <Button as-child variant="outline">
                    <Link :href="localeRoute(eventsIndex())">
                        {{ t('favorites.cta') }}
                    </Link>
                </Button>
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <EventCard
                    v-for="event in props.events"
                    :key="event.id"
                    :event="event"
                    :favorited="true"
                />
            </div>
        </div>
    </div>
</template>
