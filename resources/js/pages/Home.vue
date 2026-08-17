<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';

const { t } = useI18n();
const { localeRoute } = useLocale();
</script>

<template>
    <Head :title="t('home.title')" />

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <DefaultHeader />

        <div
            class="mx-auto flex max-w-3xl flex-col items-center gap-6 py-16 text-center"
        >
            <h2 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                {{ t('home.title') }}
            </h2>
            <p class="text-lg text-muted-foreground">
                {{ t('home.subtitle') }}
            </p>
            <Button as-child size="lg">
                <Link :href="localeRoute(eventsIndex())">
                    {{ t('home.cta') }}
                </Link>
            </Button>
        </div>
    </div>
</template>
