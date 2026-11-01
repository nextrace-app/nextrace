<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, Menu, Search, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
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

            <form
                class="relative ml-4 hidden max-w-md flex-1 lg:block"
                data-testid="site-header__search-form"
                role="search"
                @submit.prevent="submitSearch"
            >
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
                    data-testid="site-header__search"
                />
            </form>

            <div
                class="ml-auto flex items-center gap-1.5 lg:gap-2"
                data-testid="site-header__actions"
            >
                <div
                    class="hidden items-center gap-2 lg:flex"
                    data-testid="site-header__desktop-nav"
                >
                    <Link
                        v-if="user"
                        :href="localeRoute(favorites())"
                        data-testid="site-header__favorites"
                        :class="[
                            pill,
                            isCurrentOrParentUrl(localeRoute(favorites()))
                                ? 'bg-secondary text-foreground'
                                : 'text-muted-foreground',
                        ]"
                    >
                        {{ t('nav.favorites') }}
                    </Link>
                    <Link
                        v-if="user"
                        :href="localeRoute(profile())"
                        data-testid="site-header__profile"
                        :class="[
                            pill,
                            isCurrentOrParentUrl(localeRoute(profile()))
                                ? 'bg-secondary text-foreground'
                                : 'text-muted-foreground',
                        ]"
                    >
                        {{ t('nav.profile') }}
                    </Link>
                    <LocaleSwitcher />
                    <template v-if="user">
                        <Link
                            :href="localeRoute(logout())"
                            as="button"
                            :class="[pill, 'text-muted-foreground']"
                            data-testid="site-header__logout"
                            @click="handleLogout"
                        >
                            {{ t('nav.logout') }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="localeRoute(login())"
                            data-testid="site-header__login"
                            :class="
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'sm',
                                })
                            "
                        >
                            {{ t('nav.login') }}
                        </Link>
                        <Link
                            :href="localeRoute(register())"
                            data-testid="site-header__register"
                            :class="buttonVariants({ size: 'sm' })"
                        >
                            {{ t('nav.register') }}
                        </Link>
                    </template>
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
                            class="mt-auto bg-muted/50"
                            data-testid="site-header__menu-account"
                        >
                            <Link
                                v-if="user"
                                :href="localeRoute(favorites())"
                                data-testid="site-header__menu-favorites"
                                :class="menuRow"
                            >
                                {{ t('nav.favorites') }}
                                <ChevronRight
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                            <Link
                                v-if="user"
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
                            <Link
                                v-if="user"
                                :href="localeRoute(logout())"
                                as="button"
                                :class="[menuRow, 'w-full']"
                                data-testid="site-header__menu-logout"
                                @click="handleLogout"
                            >
                                {{ t('nav.logout') }}
                            </Link>
                            <template v-else>
                                <Link
                                    :href="localeRoute(login())"
                                    data-testid="site-header__menu-login"
                                    :class="menuRow"
                                >
                                    {{ t('nav.login') }}
                                    <ChevronRight
                                        class="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </Link>
                                <Link
                                    :href="localeRoute(register())"
                                    data-testid="site-header__menu-register"
                                    :class="menuRow"
                                >
                                    {{ t('nav.register') }}
                                    <ChevronRight
                                        class="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </template>
                            <div class="flex items-center gap-2 px-6 py-4">
                                <span
                                    class="mr-auto text-sm text-muted-foreground"
                                >
                                    {{ t('nav.language') }}
                                </span>
                                <LocaleSwitcher />
                            </div>
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

        <nav
            :aria-label="t('nav.races')"
            class="hidden border-t lg:block"
            data-testid="site-header__category-bar"
        >
            <div
                class="flex items-center gap-1 overflow-x-auto px-4 py-2 sm:px-6"
            >
                <Link
                    v-for="item in categoryLinks"
                    :key="item.key"
                    :href="localeRoute(item.route)"
                    data-testid="site-header__category"
                    :class="[
                        pill,
                        activeDistance === item.key
                            ? 'bg-foreground text-background hover:bg-foreground hover:text-background'
                            : 'text-muted-foreground',
                    ]"
                    :aria-current="
                        activeDistance === item.key ? 'page' : undefined
                    "
                >
                    {{ item.label }}
                </Link>
            </div>
        </nav>
    </header>
</template>
