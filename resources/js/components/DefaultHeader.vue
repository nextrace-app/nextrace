<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, Menu, Search, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { useLocale } from '@/composables/useLocale';
import { home, login, logout } from '@/routes';
import { index as events } from '@/routes/events';
import { index as favorites } from '@/routes/favorites';
import { show as profile } from '@/routes/profile';

const { t } = useI18n();
const { localeRoute } = useLocale();
const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

// Anything prefetched for the signed in user is no longer theirs to see.
const handleLogout = () => router.flushAll();

const distanceTabs = ['marathon', 'half_marathon', '10k', '5k', 'ultra'];

const urlParams = computed(
    () => new URLSearchParams(page.url.split('?')[1] ?? ''),
);
const onEventsIndex = computed(
    () => page.url.split('?')[0] === localeRoute(events()).url,
);
const activeDistance = computed(() =>
    onEventsIndex.value ? (urlParams.value.get('distance') ?? '') : null,
);

const categoryLinks = computed(() => [
    { key: '', label: t('nav.all_races'), route: events() },
    ...distanceTabs.map((key) => ({
        key,
        label: t(`events.distance_classes.${key}`),
        route: events({ query: { distance: key } }),
    })),
]);

const searchOpen = ref(false);
const menuOpen = ref(false);
const searchQuery = ref(urlParams.value.get('q') ?? '');

watch(
    () => page.url,
    () => {
        searchQuery.value = urlParams.value.get('q') ?? '';
        searchOpen.value = false;
        menuOpen.value = false;
    },
);

function submitSearch() {
    const q = searchQuery.value.trim();

    router.visit(localeRoute(events({ query: q === '' ? {} : { q } })));
}

const pill =
    'inline-flex shrink-0 items-center rounded-full px-4 py-2 text-sm font-bold whitespace-nowrap outline-none transition-colors hover:bg-secondary hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50';
const searchInput =
    'h-11 w-full min-w-0 rounded-lg border bg-background pr-4 pl-10 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 lg:h-10 lg:text-sm';
const menuRow =
    'flex min-h-12 items-center justify-between border-b px-6 text-base font-medium outline-none hover:bg-secondary focus-visible:bg-secondary';
</script>

<template>
    <header
        class="sticky top-0 z-30 border-b bg-card/95 backdrop-blur"
        data-testid="site-header"
    >
        <div
            class="flex h-14 items-center gap-4 px-4 sm:px-6 lg:h-16 lg:gap-4"
            data-testid="site-header__bar"
        >
            <Link
                :href="localeRoute(home())"
                :aria-label="t('nav.home')"
                class="-ml-1 flex items-center rounded-lg px-1 text-xl text-foreground outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                data-testid="site-header__home"
            >
                <AppWordmark />
            </Link>

            <span
                class="hidden h-6 w-px bg-border lg:block"
                aria-hidden="true"
            ></span>
            <Link
                :href="localeRoute(events())"
                :class="[
                    pill,
                    'max-lg:hidden',
                    isCurrentOrParentUrl(localeRoute(events()))
                        ? 'bg-secondary text-foreground'
                        : 'text-foreground',
                ]"
                data-testid="site-header__races"
            >
                {{ t('nav.races') }}
            </Link>

            <div
                class="ml-auto flex items-center gap-1.5 lg:gap-2"
                data-testid="site-header__actions"
            >
                <div
                    class="hidden items-center gap-2 lg:flex"
                    data-testid="site-header__desktop-nav"
                >
                    <LocaleSwitcher />
                    <DropdownMenu v-if="user">
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="rounded-full outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                :aria-label="t('nav.account_menu')"
                                data-testid="site-header__account"
                            >
                                <Avatar class="size-10">
                                    <AvatarImage
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback
                                        class="bg-secondary text-sm font-bold text-secondary-foreground"
                                        data-testid="site-header__initials"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-70 p-0"
                            data-testid="site-header__account-menu"
                        >
                            <div class="px-6 pt-4 pb-3">
                                <p class="text-xs text-muted-foreground">
                                    {{ t('nav.welcome') }}
                                </p>
                                <p
                                    class="truncate text-sm font-medium"
                                    data-testid="site-header__account-name"
                                >
                                    {{ user.name }}
                                </p>
                            </div>
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="localeRoute(favorites())"
                                    data-testid="site-header__favorites"
                                    :class="[menuRow, 'rounded-none']"
                                >
                                    {{ t('nav.favorites') }}
                                    <ChevronRight
                                        class="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="localeRoute(profile())"
                                    data-testid="site-header__profile"
                                    :class="[menuRow, 'rounded-none']"
                                >
                                    {{ t('nav.profile') }}
                                    <ChevronRight
                                        class="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </DropdownMenuItem>
                            <div class="p-4">
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="localeRoute(logout())"
                                        as="button"
                                        :class="[
                                            buttonVariants({
                                                variant: 'outline',
                                            }),
                                            'w-full',
                                        ]"
                                        data-testid="site-header__logout"
                                        @click="handleLogout"
                                    >
                                        {{ t('nav.logout') }}
                                    </Link>
                                </DropdownMenuItem>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Link
                        v-else
                        :href="localeRoute(login())"
                        data-testid="site-header__login"
                        :class="buttonVariants({ size: 'sm' })"
                    >
                        {{ t('nav.login') }}
                    </Link>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-11 lg:hidden"
                    :aria-label="t('nav.search')"
                    :aria-expanded="searchOpen"
                    data-testid="site-header__search-toggle"
                    @click="searchOpen = !searchOpen"
                >
                    <X v-if="searchOpen" class="size-5" aria-hidden="true" />
                    <Search v-else class="size-5" aria-hidden="true" />
                </Button>

                <Sheet v-model:open="menuOpen">
                    <SheetTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="size-11 lg:hidden"
                            :aria-label="t('nav.menu')"
                            data-testid="site-header__menu-toggle"
                        >
                            <Menu class="size-5" aria-hidden="true" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        class="w-full gap-0 overflow-y-auto p-0 sm:max-w-sm"
                        data-testid="site-header__menu"
                    >
                        <SheetHeader class="border-b px-6 py-4">
                            <SheetTitle class="text-xl">
                                <AppWordmark />
                            </SheetTitle>
                            <SheetDescription class="sr-only">
                                {{ t('nav.main') }}
                            </SheetDescription>
                        </SheetHeader>

                        <div
                            v-if="user"
                            data-testid="site-header__menu-account"
                        >
                            <div class="flex items-center gap-3 px-6 py-4">
                                <Avatar class="size-10">
                                    <AvatarImage
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback
                                        class="bg-secondary text-sm font-bold text-secondary-foreground"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <p class="text-xs text-muted-foreground">
                                        {{ t('nav.welcome') }}
                                    </p>
                                    <p class="truncate text-sm font-medium">
                                        {{ user.name }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="localeRoute(favorites())"
                                data-testid="site-header__menu-favorites"
                                :class="[menuRow, 'border-t']"
                            >
                                {{ t('nav.favorites') }}
                                <ChevronRight
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                            <Link
                                :href="localeRoute(profile())"
                                data-testid="site-header__menu-profile"
                                :class="menuRow"
                            >
                                {{ t('nav.profile') }}
                                <ChevronRight
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                            <div class="border-b px-6 py-4">
                                <Link
                                    :href="localeRoute(logout())"
                                    as="button"
                                    :class="[
                                        buttonVariants({ variant: 'outline' }),
                                        'h-11 w-full',
                                    ]"
                                    data-testid="site-header__menu-logout"
                                    @click="handleLogout"
                                >
                                    {{ t('nav.logout') }}
                                </Link>
                            </div>
                        </div>
                        <div
                            v-else
                            class="border-b px-6 py-4"
                            data-testid="site-header__menu-account"
                        >
                            <Link
                                :href="localeRoute(login())"
                                :class="[
                                    buttonVariants({ variant: 'outline' }),
                                    'h-11 w-full',
                                ]"
                                data-testid="site-header__menu-login"
                            >
                                {{ t('nav.login') }}
                            </Link>
                        </div>

                        <nav
                            :aria-label="t('nav.main')"
                            data-testid="site-header__menu-races"
                        >
                            <p
                                class="px-6 pt-4 pb-1 text-[11px] font-extrabold tracking-widest text-muted-foreground uppercase"
                            >
                                {{ t('nav.races') }}
                            </p>
                            <Link
                                v-for="item in categoryLinks"
                                :key="item.key"
                                :href="localeRoute(item.route)"
                                data-testid="site-header__menu-category"
                                :class="[
                                    menuRow,
                                    activeDistance === item.key &&
                                        'bg-secondary font-bold',
                                ]"
                            >
                                {{ item.label }}
                                <ChevronRight
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                        </nav>

                        <div
                            class="mt-auto flex justify-end border-t px-6 py-4"
                            data-testid="site-header__menu-language"
                        >
                            <LocaleSwitcher />
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>

        <form
            v-if="searchOpen"
            class="flex gap-2 border-t px-4 py-2 sm:px-6 lg:hidden"
            data-testid="site-header__mobile-search-form"
            role="search"
            @submit.prevent="submitSearch"
        >
            <div class="relative min-w-0 flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <input
                    v-model="searchQuery"
                    type="search"
                    :placeholder="t('nav.search_placeholder')"
                    :aria-label="t('nav.search')"
                    :class="searchInput"
                    data-testid="site-header__mobile-search"
                />
            </div>
            <Button
                type="submit"
                class="h-11"
                data-testid="site-header__mobile-search-submit"
                >{{ t('nav.search') }}</Button
            >
        </form>
    </header>
</template>
