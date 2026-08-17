<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { dashboard, home, login, register } from '@/routes';

const { t } = useI18n();
const { localeRoute } = useLocale();
const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <header class="mx-auto flex max-w-6xl items-center justify-between pb-8">
        <Link
            :href="localeRoute(home())"
            class="flex items-center gap-2 font-medium"
        >
            <AppLogoIcon
                class="size-8 fill-current text-[var(--foreground)] dark:text-white"
            />
            <h1 class="text-lg font-semibold">{{ t('nav.events') }}</h1>
        </Link>
        <nav class="flex items-center gap-4 text-sm">
            <Link
                v-if="user"
                :href="localeRoute(dashboard())"
                class="text-muted-foreground hover:text-foreground"
            >
                {{ t('nav.dashboard') }}
            </Link>
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
            <LocaleSwitcher />
        </nav>
    </header>
</template>
