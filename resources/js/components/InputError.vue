<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" data-testid="input-error">
        <p
            class="text-sm text-red-600 dark:text-red-500"
            data-testid="input-error__message"
        >
            {{ message }}
        </p>
    </div>
</template>
