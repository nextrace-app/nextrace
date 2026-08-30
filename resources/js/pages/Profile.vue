<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('profile.title')" />

    <div class="min-h-screen bg-background py-6 lg:py-8">
        <DefaultHeader />

        <div
            class="mx-auto flex w-full max-w-6xl flex-col px-4 sm:px-6 lg:flex-row lg:space-x-12 lg:px-8"
        >
            <aside class="w-full max-w-xl lg:w-48">
                <ProfileNav />
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <h2 class="mb-6 text-2xl font-semibold">
                    {{ t('profile.title') }}
                </h2>

                <div class="space-y-12">
                    <ProfilePageForm />

                    <ProfilePageDeleteUser />
                </div>
            </div>
        </div>

        <!-- The layouts with a sidebar mount their own; this page carries the
             public header instead, and still flashes a toast when saving. -->
        <Toaster />
    </div>
</template>
