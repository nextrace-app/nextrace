<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { index as events } from '@/routes/events';
import { index as favorites } from '@/routes/favorites';

const { t } = useI18n();
const { localeRoute } = useLocale();
const page = usePage();
const user = computed(() => page.props.auth.user);
const year = new Date().getFullYear();

const links = computed(() => [
    { key: 'events', route: events() },
    ...(user.value ? [{ key: 'favorites', route: favorites() }] : []),
]);

const linkClass =
    'rounded-sm py-2 font-semibold text-foreground underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <footer class="mt-16 border-t bg-background text-sm text-muted-foreground">
        <div
            class="flex flex-col gap-3 px-4 py-6 sm:px-6 md:flex-row md:items-center md:justify-between md:py-4 lg:px-8"
        >
            <div
                class="flex flex-col gap-x-4 gap-y-1 md:flex-row md:items-center"
            >
                <span class="flex items-center gap-2 text-base text-foreground">
                    <AppLogoIcon class="size-6 shrink-0" />
                    <AppWordmark />
                </span>
                <span>{{ t('footer.copyright', { year }) }}</span>
                <span>{{ t('footer.note') }}</span>
            </div>
            <nav
                :aria-label="t('footer.browse')"
                class="flex flex-wrap items-center gap-x-4 gap-y-1"
            >
                <Link
                    v-for="link in links"
                    :key="link.key"
                    :href="localeRoute(link.route)"
                    :class="linkClass"
                >
                    {{ t(`nav.${link.key}`) }}
                </Link>
                <LocaleSwitcher />
            </nav>
        </div>
    </footer>
</template>
