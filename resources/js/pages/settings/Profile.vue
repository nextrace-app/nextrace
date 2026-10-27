<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DeleteUser from '@/components/DeleteUser.vue';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'settings.profile.title',
    },
});
</script>

<template>
    <Head :title="t('settings.profile.title')" />

    <ProfileForm />

    <DeleteUser />
</template>
