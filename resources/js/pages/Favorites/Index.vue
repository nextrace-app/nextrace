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

    <DefaultShell>
        <main
            class="mx-auto w-full max-w-6xl px-4 pt-8 sm:px-6 lg:px-8"
            data-testid="favorites-page"
        >
            <h1
                class="mb-6 text-3xl font-extrabold tracking-tight"
                data-testid="favorites-page__title"
            >
                {{ t('favorites.title') }}
            </h1>

            <div
                v-if="props.events.length === 0"
                class="flex flex-col items-center gap-4 rounded-lg border border-dashed bg-card py-16 text-center"
                data-testid="favorites-page__empty"
            >
                <Heart class="size-8 text-muted-foreground" />
                <p class="text-muted-foreground">{{ t('favorites.empty') }}</p>
                <Button as-child variant="outline">
                    <Link
                        :href="localeRoute(eventsIndex())"
                        data-testid="favorites-page__browse"
                    >
                        {{ t('favorites.cta') }}
                    </Link>
                </Button>
            </div>

            <div
                v-else
                class="grid gap-4 lg:grid-cols-2"
                data-testid="favorites-page__list"
            >
                <EventCard
                    v-for="event in props.events"
                    :key="event.id"
                    :event="event"
                    :favorited="true"
                />
            </div>
        </main>
    </DefaultShell>
</template>
