<script setup lang="ts">
// The frame of every public page: sticky header, content and footer. A page
// that carries its own bottom spacing passes `flush` to drop the gap above
// the footer.
withDefaults(defineProps<{ flush?: boolean }>(), { flush: false });
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-background text-foreground"
        data-testid="default-shell"
    >
        <DefaultHeader />
        <div class="flex-1" data-testid="default-shell__content">
            <slot />
        </div>
        <DefaultFooter :class="flush ? '' : 'mt-16'" />
    </div>
</template>
