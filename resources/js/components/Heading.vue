<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header
        :class="variant === 'small' ? '' : 'mb-8 space-y-0.5'"
        data-testid="heading"
    >
        <h2
            data-testid="heading__title"
            :class="
                variant === 'small'
                    ? 'mb-0.5 text-base font-medium'
                    : 'text-xl font-semibold tracking-tight'
            "
        >
            {{ title }}
        </h2>
        <p
            v-if="description"
            class="text-sm text-muted-foreground"
            data-testid="heading__description"
        >
            {{ description }}
        </p>
    </header>
</template>
