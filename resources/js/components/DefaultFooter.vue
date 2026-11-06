<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { index as events } from '@/routes/events';
import { index as posts } from '@/routes/posts';

const { t } = useI18n();
const { localeRoute } = useLocale();
const year = new Date().getFullYear();

const links = [
    { key: 'events', route: events() },
    { key: 'posts', route: posts() },
];

const linkClass =
    'rounded-lg py-2 font-bold text-foreground underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <footer
        class="border-t bg-card text-sm text-muted-foreground"
        data-testid="site-footer"
    >
        <div
            class="flex flex-col gap-4 px-4 py-4 sm:gap-6 sm:px-6 sm:py-6 md:flex-row md:items-center md:justify-between"
        >
            <div
                class="flex flex-col gap-x-4 gap-y-1 md:flex-row md:items-center"
            >
                <span data-testid="site-footer__copyright">{{
                    t('footer.copyright', { year })
                }}</span>
            </div>
            <nav
                :aria-label="t('footer.browse')"
                class="flex flex-wrap items-center gap-x-4 gap-y-1"
                data-testid="site-footer__nav"
            >
                <Link
                    v-for="link in links"
                    :key="link.key"
                    :href="localeRoute(link.route)"
                    :class="linkClass"
                    data-testid="site-footer__link"
                >
                    {{ t(`nav.${link.key}`) }}
                </Link>
            </nav>
        </div>
    </footer>
</template>
