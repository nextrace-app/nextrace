<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, Heart, SlidersHorizontal } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
const { localeRoute } = useLocale();

const features = [
    { key: 'calendar', icon: CalendarDays },
    { key: 'filters', icon: SlidersHorizontal },
    { key: 'favorites', icon: Heart },
] as const;
</script>

<template>
    <Head :title="seo.title ?? t('home.title')" />

    <DefaultShell>
        <main
            class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8"
            data-testid="home-page"
        >
            <section
                class="relative mt-6 overflow-hidden rounded-3xl border bg-sand px-6 py-14 sm:mt-10 sm:px-12 sm:py-24"
                data-testid="home-page__hero"
            >
                <div class="absolute inset-0" aria-hidden="true">
                    <ContourArt
                        seed="nextrace-home"
                        :width="1000"
                        :height="520"
                    />
                </div>
                <div class="relative flex max-w-2xl flex-col items-start gap-5">
                    <p
                        class="text-xs font-bold tracking-[0.12em] text-olive uppercase"
                        data-testid="home-page__eyebrow"
                    >
                        {{ t('home.eyebrow') }}
                    </p>
                    <h1
                        class="text-4xl leading-[1.06] font-extrabold tracking-tight text-balance sm:text-6xl"
                        data-testid="home-page__heading"
                    >
                        {{ t('home.heading') }}
                    </h1>
                    <p
                        class="max-w-xl text-lg text-muted-foreground"
                        data-testid="home-page__lead"
                    >
                        {{ t('home.lead') }}
                    </p>
                    <Button as-child size="lg" class="mt-2">
                        <Link
                            :href="localeRoute(eventsIndex())"
                            data-testid="home-page__cta"
                        >
                            {{ t('home.cta') }}
                        </Link>
                    </Button>
                </div>
            </section>

            <ul
                class="mt-6 grid gap-4 sm:grid-cols-3"
                data-testid="home-page__features"
            >
                <li
                    v-for="feature in features"
                    :key="feature.key"
                    class="rounded-2xl border bg-card p-5"
                    data-testid="home-page__feature"
                >
                    <span
                        class="grid size-10 place-items-center rounded-xl bg-secondary text-primary"
                    >
                        <component
                            :is="feature.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                    <h2
                        class="mt-3 text-lg font-bold"
                        data-testid="home-page__feature-title"
                    >
                        {{ t(`home.features.${feature.key}.title`) }}
                    </h2>
                    <p
                        class="mt-1 text-sm text-muted-foreground"
                        data-testid="home-page__feature-text"
                    >
                        {{ t(`home.features.${feature.key}.text`) }}
                    </p>
                </li>
            </ul>
        </main>
    </DefaultShell>
</template>
