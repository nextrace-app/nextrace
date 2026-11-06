<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { show as postsShow } from '@/routes/posts';
import type { Post } from '@/types';

defineProps<{
    post: Post;
    /** The heading level of the title, one below the heading of its section. */
    headingLevel?: 2 | 3;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
const { formatDate } = useEventFormat();

const shortDate = { day: 'numeric', month: 'short', year: 'numeric' } as const;
</script>

<template>
    <article
        class="group relative flex flex-col overflow-hidden rounded-lg border bg-card text-card-foreground shadow-xs transition-[box-shadow,transform,border-color] focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary hover:shadow-lg motion-safe:hover:-translate-y-0.5"
        data-testid="post-card"
    >
        <div
            class="relative aspect-[16/10] overflow-hidden bg-secondary"
            data-testid="post-card__media"
        >
            <img
                v-if="post.image"
                :src="post.image"
                :alt="post.image_alt ?? ''"
                loading="lazy"
                data-testid="post-card__image"
                class="size-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
            />
            <ContourArt v-else :seed="post.slug" :width="480" :height="300" />
        </div>

        <div
            class="flex flex-1 flex-col gap-4 p-4"
            data-testid="post-card__body"
        >
            <div class="flex items-center justify-between gap-2">
                <Badge
                    class="border-transparent bg-background font-bold text-foreground"
                    data-testid="post-card__category"
                    >{{ t(`posts.categories.${post.category}`) }}</Badge
                >
                <time
                    v-if="post.date"
                    :datetime="post.date"
                    class="text-[13px] text-muted-foreground"
                    data-testid="post-card__date"
                    >{{ formatDate(post.date, shortDate) }}</time
                >
            </div>

            <div class="min-w-0 space-y-2">
                <component
                    :is="`h${headingLevel ?? 3}`"
                    class="line-clamp-2 text-[17px] leading-snug font-bold"
                    data-testid="post-card__title"
                >
                    <Link
                        :href="localeRoute(postsShow(post.slug))"
                        class="rounded-lg outline-none after:absolute after:inset-0 after:content-['']"
                        data-testid="post-card__link"
                    >
                        {{ post.title }}
                    </Link>
                </component>
                <p
                    v-if="post.excerpt"
                    class="line-clamp-3 text-sm text-muted-foreground"
                    data-testid="post-card__excerpt"
                >
                    {{ post.excerpt }}
                </p>
            </div>
        </div>
    </article>
</template>
