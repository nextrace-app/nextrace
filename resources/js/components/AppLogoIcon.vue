<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        aria-hidden="true"
        v-bind="$attrs"
    >
        <circle cx="16" cy="16" r="16" fill="var(--secondary)" />
        <circle cx="16" cy="12" r="5" fill="var(--primary)" />
        <path
            d="M5 20c3-2.5 5-2.5 8 0s5 2.5 8 0 4-2 6-.7M7 25c2.6-2 4.4-2 7 0s4.4 2 7 0 3-1.6 4.5-.6"
            fill="none"
            stroke="var(--foreground)"
            stroke-width="2"
            stroke-linecap="round"
        />
    </svg>
</template>
