<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Layers,
    LayoutDashboard,
    LogOut,
    MapPin,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useLocale } from '@/composables/useLocale';
import { home, logout } from '@/routes';

const { t } = useI18n();
const { localeRoute } = useLocale();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();

const icons: Record<string, Component> = {
    overview: LayoutDashboard,
    events: Calendar,
    regions: MapPin,
    series: Layers,
};

const sections = computed(() => page.props.adminMenu);

// The overview is the root of the admin area, so only an exact match marks it.
const isActive = (key: string, href: string) =>
    key === 'overview' ? isCurrentUrl(href) : isCurrentOrParentUrl(href);

const row =
    'flex min-h-11 items-center gap-4 rounded-lg px-4 py-2.5 text-sm transition-colors hover:bg-secondary';

// Anything prefetched for the signed in user is no longer theirs to see.
const handleLogout = () => router.flushAll();
</script>

<template>
    <div class="flex flex-1 flex-col gap-6" data-testid="admin-nav">
        <nav
            :aria-label="t('admin.nav.label')"
            class="flex flex-col gap-2"
            data-testid="admin-nav__sections"
        >
            <Link
                v-for="section in sections"
                :key="section.key"
                :href="section.href"
                :class="[
                    row,
                    isActive(section.key, section.href)
                        ? 'bg-secondary font-medium text-primary'
                        : 'text-foreground',
                ]"
                :aria-current="
                    isActive(section.key, section.href) ? 'page' : undefined
                "
                :data-testid="`admin-nav__${section.key}`"
            >
                <component
                    :is="icons[section.key]"
                    class="size-6 shrink-0"
                    aria-hidden="true"
                />
                {{ t(`admin.nav.${section.key}`) }}
            </Link>
        </nav>

        <div
            class="mt-auto flex flex-col gap-2 border-t pt-4 lg:border-t-0 lg:pt-0"
        >
            <Link
                :href="localeRoute(home())"
                :class="[row, 'text-foreground']"
                data-testid="admin-nav__back"
            >
                <ArrowLeft
                    class="size-6 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                {{ t('admin.back_to_site') }}
            </Link>
            <Link
                :href="localeRoute(logout())"
                as="button"
                :class="[row, 'w-full text-left text-foreground']"
                data-testid="admin-nav__logout"
                @click="handleLogout"
            >
                <LogOut
                    class="size-6 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                {{ t('nav.logout') }}
            </Link>
        </div>
    </div>
</template>
