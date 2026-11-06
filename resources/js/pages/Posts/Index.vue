<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type { Post } from '@/types';

defineProps<{
    posts: Post[];
    pagination: {
        current: number;
        last: number;
        total: number;
        perPage: number;
    };
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
            <h1
                class="mb-4 text-3xl font-extrabold sm:mb-6 lg:text-5xl"
                data-testid="posts-page__title"
            >
                {{ t('posts.title') }}
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
