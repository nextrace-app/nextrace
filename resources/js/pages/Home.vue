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

    <div class="min-h-screen bg-background py-6 lg:py-8">
        <DefaultHeader />

        <div
            class="mx-auto flex w-full max-w-6xl flex-col items-center gap-6 px-4 py-16 text-center sm:px-6 lg:px-8"
        >
            <h2
                class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl"
            >
                {{ t('home.title') }}
            </h2>
            <p class="max-w-3xl text-lg text-muted-foreground">
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
