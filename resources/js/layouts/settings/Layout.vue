<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';

// The title arrives as a translation key, because a page cannot call t() from
// the layout options it declares.
defineProps<{
    title?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <DefaultShell>
        <main
            class="mx-auto flex w-full max-w-6xl flex-col px-4 pt-8 pb-12 sm:px-6 lg:flex-row lg:space-x-12 lg:px-8"
        >
            <aside class="w-full max-w-xl lg:w-48 lg:shrink-0">
                <SettingsNav />
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-w-0 flex-1 md:max-w-2xl">
                <h1 class="mb-6 text-3xl font-extrabold tracking-tight">
                    {{ title ? t(title) : t('settings.title') }}
                </h1>

                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </main>
        <Toaster />
    </DefaultShell>
</template>
