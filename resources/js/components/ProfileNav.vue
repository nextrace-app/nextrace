<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useLocale } from '@/composables/useLocale';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { show as showProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const { isCurrentOrParentUrl } = useCurrentUrl();
const { localeRoute } = useLocale();

const items: NavItem[] = [
    {
        title: 'Profile',
        href: localeRoute(showProfile()),
    },
    {
        title: 'Security',
        href: localeRoute(editSecurity()),
    },
    {
        title: 'Appearance',
        href: localeRoute(editAppearance()),
    },
];
</script>

<template>
    <nav class="flex flex-col space-y-1 space-x-0" aria-label="Profile">
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
