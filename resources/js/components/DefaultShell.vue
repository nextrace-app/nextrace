<script setup lang="ts">
// The frame of every public page: sticky header, content and footer.
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <DefaultHeader />
        <div class="flex-1">
            <slot />
        </div>
        <DefaultFooter />
    </div>
</template>
