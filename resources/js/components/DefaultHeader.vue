<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { dashboard, home, login, logout, register } from '@/routes';
import { index as favorites } from '@/routes/favorites';
import { show as profile } from '@/routes/profile';

const { t } = useI18n();
const { localeRoute } = useLocale();
const page = usePage();
const user = computed(() => page.props.auth.user);

// Anything prefetched for the signed in user is no longer theirs to see.
const handleLogout = () => router.flushAll();
</script>

<template>
    <header
        class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 pb-8 sm:px-6 lg:px-8"
    >
        <Link
            :href="localeRoute(home())"
            class="flex items-center gap-2 font-medium"
        >
            <AppLogoIcon
                class="size-8 fill-current text-[var(--foreground)] dark:text-white"
            />
            <h1 class="text-lg font-semibold">{{ t('nav.events') }}</h1>
        </Link>
        <nav
            class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm sm:gap-4"
        >
            <template v-if="user">
                <Link
                    :href="localeRoute(favorites())"
                    class="text-muted-foreground hover:text-foreground"
                >
                    {{ t('nav.favorites') }}
                </Link>
                <Link
                    :href="localeRoute(dashboard())"
                    class="text-muted-foreground hover:text-foreground"
                >
                    {{ t('nav.dashboard') }}
                </Link>
                <Link
                    :href="localeRoute(profile())"
                    :title="user.name"
                    class="font-medium"
                >
                    {{ t('nav.profile') }}
                </Link>
                <Link
                    :href="localeRoute(logout())"
                    as="button"
                    class="text-muted-foreground hover:text-foreground"
                    data-test="logout-button"
                    @click="handleLogout"
                >
                    {{ t('nav.logout') }}
                </Link>
            </template>
            <template v-else>
                <Link
                    :href="localeRoute(login())"
                    class="text-muted-foreground hover:text-foreground"
                >
                    {{ t('nav.login') }}
                </Link>
                <Link
                    :href="localeRoute(register())"
                    class="text-muted-foreground hover:text-foreground"
                >
                    {{ t('nav.register') }}
                </Link>
            </template>
            <ThemeToggle />
            <LocaleSwitcher />
        </nav>
    </header>
</template>
