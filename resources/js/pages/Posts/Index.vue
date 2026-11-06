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

// Every page of the list has an address of its own, so that it can be opened
// directly and followed by crawlers; the first page is the bare address.
function pageHref(number: number): string {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    if (number === 1) {
        params.delete('page');
    } else {
        params.set('page', String(number));
    }

    const search = params.toString();

    return search === '' ? path : `${path}?${search}`;
}
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

            <nav
                v-if="pagination.last > 1"
                class="mt-4 flex items-center justify-center gap-4 sm:mt-6"
                :aria-label="t('posts.pagination.label')"
                data-testid="posts-page__pagination"
            >
                <Button
                    v-if="pagination.current === 1"
                    type="button"
                    variant="outline"
                    class="h-11"
                    disabled
                    data-testid="posts-page__pagination-previous"
                >
                    {{ t('posts.pagination.previous') }}
                </Button>
                <Button
                    v-else
                    as-child
                    variant="outline"
                    class="h-11"
                    data-testid="posts-page__pagination-previous"
                >
                    <Link :href="pageHref(pagination.current - 1)" rel="prev">
                        {{ t('posts.pagination.previous') }}
                    </Link>
                </Button>

                <span
                    class="text-sm font-medium text-muted-foreground"
                    data-testid="posts-page__pagination-status"
                >
                    {{
                        t('posts.pagination.status', {
                            current: pagination.current,
                            last: pagination.last,
                        })
                    }}
                </span>

                <Button
                    v-if="pagination.current === pagination.last"
                    type="button"
                    variant="outline"
                    class="h-11"
                    disabled
                    data-testid="posts-page__pagination-next"
                >
                    {{ t('posts.pagination.next') }}
                </Button>
                <Button
                    v-else
                    as-child
                    variant="outline"
                    class="h-11"
                    data-testid="posts-page__pagination-next"
                >
                    <Link :href="pageHref(pagination.current + 1)" rel="next">
                        {{ t('posts.pagination.next') }}
                    </Link>
                </Button>
            </nav>

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
