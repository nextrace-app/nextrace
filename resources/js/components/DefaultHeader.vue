<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { buttonVariants } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useLocale } from '@/composables/useLocale';
import { home, login, logout, register } from '@/routes';
import { index as events } from '@/routes/events';
import { index as favorites } from '@/routes/favorites';
import { show as profile } from '@/routes/profile';

const { t } = useI18n();
const { localeRoute } = useLocale();
const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();
const user = computed(() => page.props.auth.user);

// Anything prefetched for the signed in user is no longer theirs to see.
const handleLogout = () => router.flushAll();

const pill =
    'inline-flex shrink-0 items-center rounded-full px-3.5 py-2 text-sm font-semibold whitespace-nowrap outline-none transition-colors hover:bg-secondary hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50';

const navLinks = computed(() =>
    user.value
        ? [
              { key: 'races', route: events() },
              { key: 'favorites', route: favorites() },
          ]
        : [{ key: 'races', route: events() }],
);
</script>

<template>
    <header class="sticky top-0 z-30 border-b bg-background/95 backdrop-blur">
        <div
            class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-3 px-4 sm:px-6 md:flex-nowrap lg:px-8"
        >
            <Link
                :href="localeRoute(home())"
                :aria-label="t('nav.home')"
                class="-ml-1 flex h-16 items-center gap-2.5 rounded-full px-1 text-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <AppLogoIcon class="size-8 shrink-0" />
                <AppWordmark />
            </Link>

            <nav
                :aria-label="t('nav.main')"
                class="order-last -mx-4 flex w-[calc(100%+2rem)] [scrollbar-width:none] items-center gap-1 overflow-x-auto px-4 pb-3 sm:-mx-6 sm:w-[calc(100%+3rem)] sm:px-6 md:order-none md:mx-0 md:ml-4 md:w-auto md:overflow-visible md:p-0 [&::-webkit-scrollbar]:hidden"
            >
                <Link
                    v-for="item in navLinks"
                    :key="item.key"
                    :href="localeRoute(item.route)"
                    :class="[
                        pill,
                        isCurrentOrParentUrl(localeRoute(item.route))
                            ? 'bg-secondary text-foreground'
                            : 'text-muted-foreground',
                    ]"
                    :aria-current="
                        isCurrentOrParentUrl(localeRoute(item.route))
                            ? 'page'
                            : undefined
                    "
                >
                    {{ t(`nav.${item.key}`) }}
                </Link>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <Link
                    v-if="user"
                    :href="localeRoute(profile())"
                    :class="[
                        pill,
                        isCurrentOrParentUrl(localeRoute(profile()))
                            ? 'bg-secondary text-foreground'
                            : 'text-muted-foreground',
                    ]"
                    :aria-current="
                        isCurrentOrParentUrl(localeRoute(profile()))
                            ? 'page'
                            : undefined
                    "
                >
                    {{ t('nav.profile') }}
                </Link>
                <ThemeToggle />
                <LocaleSwitcher />
                <template v-if="user">
                    <Link
                        :href="localeRoute(logout())"
                        as="button"
                        :class="[pill, 'text-muted-foreground']"
                        data-test="logout-button"
                        @click="handleLogout"
                    >
                        {{ t('nav.logout') }}
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="localeRoute(login())"
                        :class="
                            buttonVariants({ variant: 'outline', size: 'sm' })
                        "
                    >
                        {{ t('nav.login') }}
                    </Link>
                    <Link
                        :href="localeRoute(register())"
                        :class="buttonVariants({ size: 'sm' })"
                    >
                        {{ t('nav.register') }}
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>
