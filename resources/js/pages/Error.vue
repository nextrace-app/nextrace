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

    <DefaultShell>
        <main
            class="mx-auto flex w-full max-w-xl flex-col items-center px-4 py-16 text-center sm:py-24"
        >
            <p
                class="text-6xl font-extrabold tracking-tight text-primary sm:text-7xl"
                aria-hidden="true"
            >
                {{ props.status }}
            </p>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight">
                {{ t(`error.title.${props.status}`) }}
            </h1>
            <p class="mt-3 text-muted-foreground">
                {{ t(`error.text.${props.status}`) }}
            </p>
            <Button v-if="props.status !== 503" as-child class="mt-8">
                <Link :href="localeRoute(eventsIndex())">
                    {{ t('error.back') }}
                </Link>
            </Button>
        </main>
    </DefaultShell>
</template>
