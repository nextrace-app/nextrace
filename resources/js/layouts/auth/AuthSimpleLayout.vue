<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Toaster } from '@/components/ui/sonner';

// The title and description arrive as translation keys, because a page cannot
// call t() from the layout options it declares.
defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <DefaultShell>
        <main
            class="mx-auto flex w-full max-w-md flex-col px-4 py-10 sm:py-16"
            data-testid="auth-simple-layout"
        >
            <div
                class="rounded-3xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
                data-testid="auth-simple-layout__card"
            >
                <div class="mb-6 space-y-2 text-center">
                    <h1
                        class="text-2xl font-extrabold tracking-tight"
                        data-testid="auth-simple-layout__title"
                    >
                        {{ title ? t(title) : '' }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm text-muted-foreground"
                        data-testid="auth-simple-layout__description"
                    >
                        {{ t(description) }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
        <Toaster />
    </DefaultShell>
</template>
