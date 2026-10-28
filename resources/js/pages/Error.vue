<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';

const props = defineProps<{
    status: number;
}>();

const { t } = useI18n();
const { localeRoute } = useLocale();
</script>

<template>
    <Head :title="t(`error.title.${props.status}`)" />

    <!-- While the site is down there is nowhere to go, so no header or footer. -->
    <main
        v-if="props.status === 503"
        data-testid="error-page--maintenance"
        class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-16 text-center text-foreground"
    >
        <div
            class="mb-10 flex items-center gap-2.5 text-2xl"
            data-testid="error-page__brand"
        >
            <AppLogoIcon class="size-10 shrink-0" />
            <AppWordmark />
        </div>
        <h1
            class="text-3xl font-extrabold tracking-tight sm:text-4xl"
            data-testid="error-page__title"
        >
            {{ t(`error.title.${props.status}`) }}
        </h1>
        <p
            class="mt-3 max-w-md text-muted-foreground"
            data-testid="error-page__text"
        >
            {{ t(`error.text.${props.status}`) }}
        </p>
    </main>

    <DefaultShell v-else>
        <main
            class="mx-auto flex w-full max-w-xl flex-col items-center px-4 py-16 text-center sm:py-24"
            data-testid="error-page"
        >
            <p
                class="text-6xl font-extrabold tracking-tight text-primary sm:text-7xl"
                data-testid="error-page__status"
                aria-hidden="true"
            >
                {{ props.status }}
            </p>
            <h1
                class="mt-4 text-3xl font-extrabold tracking-tight"
                data-testid="error-page__title"
            >
                {{ t(`error.title.${props.status}`) }}
            </h1>
            <p
                class="mt-3 text-muted-foreground"
                data-testid="error-page__text"
            >
                {{ t(`error.text.${props.status}`) }}
            </p>
            <Button as-child class="mt-8">
                <Link
                    :href="localeRoute(eventsIndex())"
                    data-testid="error-page__back"
                >
                    {{ t('error.back') }}
                </Link>
            </Button>
        </main>
    </DefaultShell>
</template>
