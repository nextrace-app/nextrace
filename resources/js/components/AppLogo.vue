<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppWordmark from '@/components/AppWordmark.vue';
</script>

<template>
    <AppLogoIcon class="size-8 shrink-0" />
    <div
        class="ml-1 grid flex-1 text-left text-base"
        data-testid="app-logo__label"
    >
        <AppWordmark class="truncate leading-tight" />
    </div>
</template>
