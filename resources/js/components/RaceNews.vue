<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { index as postsIndex, show as postsShow } from '@/routes/posts';
import type { Post } from '@/types';

/**
 * The latest news about an event: up to three compact post cards, and a link
 * to the full list of its news when there are more.
 */
defineProps<{
    posts: Post[];
    total: number;
    eventSlug: string;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const { formatDate } = useEventFormat();
</script>

<template>
    <section class="flex flex-col gap-4" data-testid="race-news">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-extrabold tracking-tight">
                {{ t('events.news.title') }}
            </h2>
            <Link
                v-if="total > posts.length"
                :href="localeRoute(postsIndex({ query: { event: eventSlug } }))"
                class="inline-flex min-h-11 items-center gap-1 rounded-lg text-sm font-bold text-primary outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
                data-testid="race-news__all"
            >
                {{ t('events.news.all') }}
                <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
        </div>
        <ul class="grid gap-4 sm:grid-cols-3">
            <li
                v-for="post in posts"
                :key="post.id"
                class="relative flex flex-col gap-2 rounded-lg border bg-card p-4 transition-colors focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary"
                data-testid="race-news__post"
            >
                <p
                    class="text-[11px] leading-[14px] font-bold tracking-wider text-muted-foreground uppercase"
                >
                    {{ t(`posts.categories.${post.category}`) }}
                    <template v-if="post.date">
                        ·
                        <time :datetime="post.date">{{
                            formatDate(post.date, {
                                day: 'numeric',
                                month: 'short',
                            })
                        }}</time>
                    </template>
                </p>
                <h3 class="text-sm font-medium">
                    <Link
                        :href="localeRoute(postsShow(post.slug))"
                        class="outline-none after:absolute after:inset-0 after:content-['']"
                        data-testid="race-news__link"
                        >{{ post.title }}</Link
                    >
                </h3>
            </li>
        </ul>
    </section>
</template>
