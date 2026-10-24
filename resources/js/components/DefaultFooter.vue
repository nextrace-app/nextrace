<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { index as events } from '@/routes/events';

const { t } = useI18n();
const { localeRoute } = useLocale();
const year = new Date().getFullYear();
</script>

<template>
    <footer class="mt-16 border-t text-sm text-muted-foreground">
        <div
            class="mx-auto grid w-full max-w-6xl gap-6 px-4 py-10 sm:px-6 md:grid-cols-[1fr_1.4fr_auto] lg:px-8"
        >
            <div class="flex flex-col gap-2">
                <span class="flex items-center gap-2.5 text-xl text-foreground">
                    <AppLogoIcon class="size-8 shrink-0" />
                    <AppWordmark />
                </span>
                <p>{{ t('footer.tagline') }}</p>
            </div>
            <p class="max-w-prose">{{ t('footer.disclaimer') }}</p>
            <nav
                :aria-label="t('footer.browse')"
                class="flex flex-wrap items-start gap-x-5 gap-y-2"
            >
                <Link
                    :href="localeRoute(events())"
                    class="rounded-sm font-semibold text-foreground underline-offset-4 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    {{ t('nav.events') }}
                </Link>
            </nav>
        </div>
        <div class="border-t">
            <div
                class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8"
            >
                <p>{{ t('footer.copyright', { year }) }}</p>
                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <LocaleSwitcher />
                </div>
            </div>
        </div>
    </footer>
</template>
