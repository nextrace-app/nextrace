<script setup lang="ts">
import { AlertCircle } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    errors: string[];
    title?: string;
};

const props = defineProps<Props>();

const { t, te } = useI18n();

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
</script>

<template>
    <Alert variant="destructive" data-testid="alert-error">
        <AlertCircle class="size-4" />
        <AlertTitle data-testid="alert-error__title">{{
            title ?? t('error.title.500')
        }}</AlertTitle>
        <AlertDescription>
            <ul
                class="list-inside list-disc text-sm"
                data-testid="alert-error__list"
            >
                <li
                    v-for="(error, index) in uniqueErrors"
                    :key="index"
                    data-testid="alert-error__item"
                >
                    {{ te(error) ? t(error) : error }}
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
