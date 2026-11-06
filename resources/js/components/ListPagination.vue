<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';

/**
 * The pagination under a list: a previous arrow, one button per page and a
 * next arrow. Every page has an address of its own (?page=N), so that it can
 * be opened directly and followed by crawlers; the first page is the bare
 * address.
 */
defineProps<{
    pagination: { current: number; last: number };
    /** The test id of the nav; the parts of it add -previous, -page and -next. */
    testid: string;
}>();

const emit = defineEmits<{ navigated: [] }>();

const { t } = useI18n();
const page = usePage();

function pageHref(number: number): string {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    if (number === 1) {
        params.delete('page');
    } else {
        params.set('page', String(number));
    }

    const search = params.toString();

    return search === '' ? path : `${path}?${search}`;
}

// 44px touch targets below lg, 36px on desktop.
const buttonClass = 'size-11 lg:size-9';
</script>

<template>
    <nav
        v-if="pagination.last > 1"
        class="mt-4 flex items-center justify-center gap-1.5 sm:mt-6"
        :aria-label="t('pagination.label')"
        :data-testid="testid"
    >
        <Button
            v-if="pagination.current === 1"
            type="button"
            variant="outline"
            size="icon"
            :class="buttonClass"
            disabled
            :data-testid="`${testid}-previous`"
            :aria-label="t('pagination.previous')"
        >
            <ChevronLeft class="size-4" aria-hidden="true" />
        </Button>
        <Button
            v-else
            as-child
            variant="outline"
            size="icon"
            :class="buttonClass"
            :data-testid="`${testid}-previous`"
        >
            <Link
                :href="pageHref(pagination.current - 1)"
                rel="prev"
                preserve-scroll
                :aria-label="t('pagination.previous')"
                @success="emit('navigated')"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
            </Link>
        </Button>
        <Button
            v-for="number in pagination.last"
            :key="number"
            as-child
            :variant="number === pagination.current ? 'default' : 'outline'"
            size="icon"
            :class="buttonClass"
            :data-testid="`${testid}-page`"
        >
            <Link
                :href="pageHref(number)"
                preserve-scroll
                :aria-current="
                    number === pagination.current ? 'page' : undefined
                "
                :aria-label="t('pagination.page', { number })"
                @success="emit('navigated')"
            >
                {{ number }}
            </Link>
        </Button>
        <Button
            v-if="pagination.current === pagination.last"
            type="button"
            variant="outline"
            size="icon"
            :class="buttonClass"
            disabled
            :data-testid="`${testid}-next`"
            :aria-label="t('pagination.next')"
        >
            <ChevronRight class="size-4" aria-hidden="true" />
        </Button>
        <Button
            v-else
            as-child
            variant="outline"
            size="icon"
            :class="buttonClass"
            :data-testid="`${testid}-next`"
        >
            <Link
                :href="pageHref(pagination.current + 1)"
                rel="next"
                preserve-scroll
                :aria-label="t('pagination.next')"
                @success="emit('navigated')"
            >
                <ChevronRight class="size-4" aria-hidden="true" />
            </Link>
        </Button>
    </nav>
</template>
