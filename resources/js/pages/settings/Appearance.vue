<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'settings.appearance.title',
    },
});
</script>

<template>
    <Head :title="t('settings.appearance.title')" />

    <div class="space-y-6" data-testid="settings-appearance-page">
        <Heading
            variant="small"
            :title="t('nav.theme.label')"
            :description="t('settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
