<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Separator } from '@/components/ui/separator';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('profile.title')" />

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <DefaultHeader />

        <div class="mx-auto flex max-w-4xl flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <ProfileNav />
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <h2 class="mb-6 text-2xl font-semibold">
                    {{ t('profile.title') }}
                </h2>

                <ProfileForm />
            </div>
        </div>
    </div>
</template>
