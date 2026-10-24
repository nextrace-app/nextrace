<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useLocale } from '@/composables/useLocale';
import { toUrl } from '@/lib/utils';
import { show as showProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

const { t } = useI18n();
const { isCurrentOrParentUrl } = useCurrentUrl();
const { localeRoute } = useLocale();

const items = computed<NavItem[]>(() => [
    {
        title: t('nav.profile'),
        href: localeRoute(showProfile()),
    },
]);
</script>

<template>
    <nav
        class="flex flex-col space-y-1 space-x-0"
        :aria-label="t('nav.profile')"
    >
        <Button
            v-for="item in items"
            :key="toUrl(item.href)"
            variant="ghost"
            :class="[
                'w-full justify-start',
                { 'bg-muted': isCurrentOrParentUrl(item.href) },
            ]"
            as-child
        >
            <Link :href="item.href">
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.title }}
            </Link>
        </Button>
    </nav>
</template>
