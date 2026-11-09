<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex, show as eventsShow } from '@/routes/events';
import type { Post } from '@/types';

defineProps<{
    posts: Post[];
    pagination: {
        current: number;
        last: number;
        total: number;
        perPage: number;
    };
    /** The event the list is narrowed to, when it shows the news of one. */
    event: { slug: string; name: string } | null;
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { localeRoute } = useLocale();
</script>

<template>
    <Head :title="seo.title ?? t('posts.title')" />

    <DefaultShell flush>
        <main
            class="mx-auto w-full max-w-6xl px-4 py-4 sm:px-6 sm:py-6"
            data-testid="posts-page"
        >
            <Link
                v-if="event"
                :href="localeRoute(eventsShow(event.slug))"
                class="mb-4 inline-flex items-center gap-1 rounded-full py-1.5 pr-2 text-sm font-bold text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                data-testid="posts-page__event"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ event.name }}
            </Link>
            <h1
                class="mb-4 text-3xl font-extrabold sm:mb-6 lg:text-5xl"
                data-testid="posts-page__title"
            >
                {{
                    event
                        ? t('posts.event_news', { name: event.name })
                        : t('posts.title')
                }}
            </h1>

            <div
                v-if="posts.length > 0"
                class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3"
                data-testid="posts-page__list"
            >
                <PostCard
                    v-for="post in posts"
                    :key="post.id"
                    :post="post"
                    :heading-level="2"
                />
            </div>

            <ListPagination
                :pagination="pagination"
                testid="posts-page__pagination"
            />

            <div
                v-if="posts.length === 0"
                class="flex flex-col items-center gap-4 rounded-lg border bg-card px-6 py-12 text-center"
                data-testid="posts-page__empty"
            >
                <p class="max-w-sm text-muted-foreground">
                    {{ t('posts.empty') }}
                </p>
                <Button
                    as-child
                    variant="outline"
                    class="h-11"
                    data-testid="posts-page__browse-events"
                >
                    <Link :href="localeRoute(eventsIndex())">
                        {{ t('posts.browse_events') }}
                    </Link>
                </Button>
            </div>
        </main>
    </DefaultShell>
</template>
