<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import type { EventSection } from '@/types';

/**
 * The practical information of an event as an accordion: one row per section,
 * the first one open. The bodies are Markdown rendered to safe HTML on the
 * server.
 */
defineProps<{
    sections: EventSection[];
}>();

const { t } = useI18n();

const title = (section: EventSection) =>
    section.title ?? t(`events.practical.types.${section.type}`);
</script>

<template>
    <section class="flex flex-col gap-4" data-testid="practical-info">
        <h2 class="text-xl font-extrabold tracking-tight">
            {{ t('events.practical.title') }}
        </h2>
        <div class="divide-y rounded-lg border bg-card">
            <details
                v-for="(section, index) in sections"
                :key="index"
                class="group"
                :open="index === 0"
                data-testid="practical-info__section"
            >
                <summary
                    class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-[15px] font-bold [&::-webkit-details-marker]:hidden"
                    data-testid="practical-info__title"
                >
                    {{ title(section) }}
                    <ChevronDown
                        class="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>
                <div
                    class="practical-body px-5 pb-4"
                    data-testid="practical-info__body"
                    v-html="section.body_html"
                ></div>
            </details>
        </div>
    </section>
</template>
