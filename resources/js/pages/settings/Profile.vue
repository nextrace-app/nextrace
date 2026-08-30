<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DeleteUser from '@/components/DeleteUser.vue';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <ProfileForm />

    <DeleteUser />
</template>
