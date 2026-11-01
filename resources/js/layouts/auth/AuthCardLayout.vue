<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { home } from '@/routes';

const { localeRoute } = useLocale();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
        data-testid="auth-card-layout"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="localeRoute(home())"
                class="flex items-center gap-2 self-center font-medium"
                data-testid="auth-card-layout__home"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogoIcon class="size-10" />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="rounded-lg" data-testid="auth-card-layout__card">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle
                            class="text-xl"
                            data-testid="auth-card-layout__title"
                            >{{ title }}</CardTitle
                        >
                        <CardDescription
                            data-testid="auth-card-layout__description"
                        >
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
