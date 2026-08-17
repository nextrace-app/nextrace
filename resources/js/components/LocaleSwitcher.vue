<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';

const { t } = useI18n();
const { locale, supportedLocales, localeUrl } = useLocale();
const page = usePage();

const itemClass = 'rounded-md px-2 py-1 text-xs font-medium transition-colors';
</script>

<template>
    <div
        role="group"
        :aria-label="t('nav.language')"
        class="inline-flex gap-0.5 rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800"
    >
        <template v-for="code in supportedLocales" :key="code">
            <span
                v-if="code === locale"
                aria-current="true"
                :class="[
                    itemClass,
                    'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100',
                ]"
            >
                {{ code.toUpperCase() }}
            </span>
            <Link
                v-else
                :href="localeUrl(page.url, code)"
                :class="[
                    itemClass,
                    'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                ]"
            >
                {{ code.toUpperCase() }}
            </Link>
        </template>
    </div>
</template>
