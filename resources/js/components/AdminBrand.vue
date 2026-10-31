<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { home } from '@/routes';

const { t } = useI18n();
const { localeRoute } = useLocale();
</script>

<template>
    <div class="flex items-center gap-2" data-testid="admin-brand">
        <Link
            :href="localeRoute(home())"
            class="text-xl"
            :aria-label="t('nav.home')"
        >
            <AppWordmark />
        </Link>
        <span
            class="rounded-lg bg-secondary px-2 py-1 text-xs font-bold text-primary"
            data-testid="admin-brand__badge"
        >
            {{ t('admin.badge') }}
        </span>
    </div>
</template>
