<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import { index as favoritesIndex } from '@/routes/favorites';

const { t } = useI18n();
const { localeRoute } = useLocale();
const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head :title="t('account.dashboard.title')" />

    <DefaultShell>
        <main class="mx-auto w-full max-w-6xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
            <div
                class="rounded-3xl border bg-card p-6 text-card-foreground sm:p-10"
            >
                <h1 class="text-3xl font-extrabold tracking-tight">
                    {{ t('account.dashboard.welcome', { name: user.name }) }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{ t('account.dashboard.lead') }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <Button as-child>
                        <Link :href="localeRoute(eventsIndex())">
                            {{ t('account.dashboard.races') }}
                        </Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="localeRoute(favoritesIndex())">
                            {{ t('account.dashboard.favorites') }}
                        </Link>
                    </Button>
                </div>
            </div>
        </main>
    </DefaultShell>
</template>
