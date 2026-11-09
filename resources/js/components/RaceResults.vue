<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { show as eventsShow } from '@/routes/events';
import type { EventEdition, EventLink } from '@/types';

/**
 * Results and past editions: a switch between the published editions of the
 * series, and the results, photos and certificate links of this edition.
 */
defineProps<{
    editions: EventEdition[];
    links: EventLink[];
}>();

const { t, locale } = useI18n();
const { localeRoute } = useLocale();

/** "2026 · 43rd" in English, "2026 · 43η" in Greek; only the year without a number. */
function editionLabel(edition: EventEdition): string {
    if (edition.edition_number === null) {
        return String(edition.year);
    }

    const form = new Intl.PluralRules(locale.value, { type: 'ordinal' }).select(
        edition.edition_number,
    );
    const ordinal = t(`events.results.ordinal.${form}`, {
        n: edition.edition_number,
    });

    return `${edition.year} · ${ordinal}`;
}
</script>

<template>
    <section class="flex flex-col gap-4" data-testid="race-results">
        <h2 class="text-xl font-extrabold tracking-tight">
            {{ t('events.results.title') }}
        </h2>
        <div class="flex flex-col gap-4 rounded-lg border bg-card p-6">
            <div v-if="editions.length > 1" class="flex flex-col gap-3">
                <p class="text-sm text-muted-foreground">
                    {{ t('events.results.edition') }}
                </p>
                <ul
                    class="flex flex-wrap gap-2"
                    :aria-label="t('events.results.editions')"
                    data-testid="race-results__editions"
                >
                    <li v-for="edition in editions" :key="edition.slug">
                        <span
                            v-if="edition.current"
                            class="inline-flex min-h-11 items-center rounded-full border border-foreground bg-foreground px-4 text-sm font-bold text-background"
                            aria-current="page"
                            data-testid="race-results__edition"
                            >{{ editionLabel(edition) }}</span
                        >
                        <Link
                            v-else
                            :href="localeRoute(eventsShow(edition.slug))"
                            class="inline-flex min-h-11 items-center rounded-full border bg-card px-4 text-sm font-bold text-foreground transition-colors outline-none hover:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            data-testid="race-results__edition"
                            >{{ editionLabel(edition) }}</Link
                        >
                    </li>
                </ul>
            </div>
            <template v-if="links.length > 0">
                <ul
                    class="flex flex-wrap gap-2"
                    :aria-label="t('events.results.links')"
                    data-testid="race-results__links"
                >
                    <li v-for="link in links" :key="link.url">
                        <Button
                            as-child
                            variant="outline"
                            class="min-h-11"
                            data-testid="race-results__link"
                        >
                            <a
                                :href="link.url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{
                                    link.label ||
                                    t(`events.link_types.${link.type}`)
                                }}
                            </a>
                        </Button>
                    </li>
                </ul>
                <p class="text-[13px] leading-[18px] text-muted-foreground">
                    {{ t('events.results.note') }}
                </p>
            </template>
        </div>
    </section>
</template>
