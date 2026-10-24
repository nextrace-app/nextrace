<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { login } from '@/routes';
import {
    destroy as unfavorite,
    store as favorite,
} from '@/routes/events/favorite';

const props = defineProps<{
    eventSlug: string;
    favorited: boolean;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const page = usePage();

const isFavorite = ref(props.favorited);

// The server is the source of truth: a visit that reloads the page brings the
// real state back, which also reverts the optimistic toggle below on failure.
watch(
    () => props.favorited,
    (favorited) => (isFavorite.value = favorited),
);

const label = computed(() =>
    isFavorite.value ? t('events.favorite.remove') : t('events.favorite.add'),
);

function toggle() {
    if (!page.props.auth.user) {
        router.visit(localeRoute(login()));

        return;
    }

    const route = isFavorite.value
        ? unfavorite(props.eventSlug)
        : favorite(props.eventSlug);

    isFavorite.value = !isFavorite.value;

    router.visit(localeRoute(route), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <button
        type="button"
        :aria-label="label"
        :title="label"
        :aria-pressed="isFavorite"
        data-test="favorite-button"
        class="flex size-9 items-center justify-center rounded-full bg-background/80 text-muted-foreground shadow-xs backdrop-blur transition-colors hover:text-red-500 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        :class="{ 'text-red-500': isFavorite }"
        @click.stop.prevent="toggle"
    >
        <Heart class="size-5" :class="{ 'fill-current': isFavorite }" />
    </button>
</template>
