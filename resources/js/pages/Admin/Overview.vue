<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('admin.overview.title')" />

    <AdminShell>
        <main class="flex flex-col gap-6" data-testid="admin-overview">
            <div class="flex flex-col gap-2">
                <h1
                    class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                    data-testid="admin-overview__title"
                >
                    {{ t('admin.overview.title') }}
                </h1>
                <p
                    class="text-muted-foreground"
                    data-testid="admin-overview__lead"
                >
                    {{ t('admin.overview.lead') }}
                </p>
            </div>

            <div
                class="flex flex-col items-center gap-2 rounded-lg border bg-card px-8 py-12 text-center"
                data-testid="admin-overview__empty"
            >
                <h2 class="text-lg font-bold">
                    {{ t('admin.overview.empty_title') }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ t('admin.overview.empty_text') }}
                </p>
            </div>
        </main>
    </AdminShell>
</template>
