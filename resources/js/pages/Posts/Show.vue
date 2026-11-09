<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import RelatedRace from '@/components/RelatedRace.vue';
import { Badge } from '@/components/ui/badge';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { index as postsIndex } from '@/routes/posts';
import type { PostPage, RelatedEvent } from '@/types';

const props = defineProps<{
    post: PostPage;
    events: RelatedEvent[];
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { localeRoute } = useLocale();
const { formatDate } = useEventFormat();

const sourceHost = computed(() => {
    try {
        return props.post.source_url
            ? new URL(props.post.source_url).hostname.replace(/^www\./, '')
            : null;
    } catch {
        return null;
    }
});
</script>

<template>
    <Head :title="seo.title ?? post.title" />

    <DefaultShell>
        <main
            class="mx-auto w-full max-w-[688px] px-4 pt-4 sm:px-6 sm:pt-6"
            data-testid="post-page"
        >
            <Link
                :href="localeRoute(postsIndex())"
                class="inline-flex items-center gap-1 rounded-full py-1.5 pr-2 text-sm font-bold text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                data-testid="post-page__back"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ t('posts.back') }}
            </Link>

            <article class="mt-6 space-y-6">
                <header class="space-y-4" data-testid="post-page__header">
                    <div class="flex items-center gap-2">
                        <Badge
                            class="border-transparent bg-card font-bold text-foreground"
                            data-testid="post-page__category"
                            >{{ t(`posts.categories.${post.category}`) }}</Badge
                        >
                        <time
                            v-if="post.date"
                            :datetime="post.date"
                            class="text-[13px] text-muted-foreground"
                            data-testid="post-page__date"
                            >{{
                                formatDate(post.date, {
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric',
                                })
                            }}</time
                        >
                    </div>
                    <h1
                        class="text-3xl leading-tight font-extrabold lg:text-5xl"
                        data-testid="post-page__title"
                    >
                        {{ post.title }}
                    </h1>
                </header>

                <img
                    v-if="post.image"
                    :src="post.image"
                    :alt="post.image_alt ?? ''"
                    class="aspect-[16/10] w-full rounded-lg object-cover"
                    data-testid="post-page__cover"
                />

                <!-- Rendered on the server from Markdown, with raw HTML stripped. -->
                <div
                    class="post-body"
                    data-testid="post-page__body"
                    v-html="post.body_html"
                ></div>

                <footer
                    v-if="post.source_url"
                    class="flex flex-wrap items-center gap-2 border-t pt-4 text-sm"
                    data-testid="post-page__source"
                >
                    <span class="text-muted-foreground">{{
                        t('posts.source')
                    }}</span>
                    <a
                        :href="post.source_url"
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        class="rounded-lg font-bold text-primary underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        data-testid="post-page__source-link"
                        >{{ sourceHost ?? post.source_url }}</a
                    >
                </footer>

                <div
                    v-if="events.length > 0"
                    class="flex flex-col gap-4"
                    data-testid="post-page__events"
                >
                    <RelatedRace
                        v-for="event in events"
                        :key="event.slug"
                        :event="event"
                    />
                </div>
            </article>
        </main>
    </DefaultShell>
</template>
