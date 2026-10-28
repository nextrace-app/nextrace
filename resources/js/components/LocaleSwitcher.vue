<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Check, Globe } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useLocale } from '@/composables/useLocale';

const { t } = useI18n();
const { locale, supportedLocales, localeSwitchUrl } = useLocale();
const page = usePage();

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="secondary"
                size="icon"
                class="size-10"
                :aria-label="t('nav.language')"
                :title="t('nav.language')"
                data-testid="locale-switcher__trigger"
            >
                <Globe class="size-[18px]" aria-hidden="true" />
            </Button>
        </DialogTrigger>
        <DialogContent
            class="sm:max-w-sm"
            data-testid="locale-switcher__dialog"
        >
            <DialogHeader>
                <DialogTitle>{{ t('nav.chooseLanguage') }}</DialogTitle>
                <DialogDescription class="sr-only">
                    {{ t('nav.chooseLanguageDescription') }}
                </DialogDescription>
            </DialogHeader>
            <ul class="flex flex-col gap-1" data-testid="locale-switcher__list">
                <li v-for="code in supportedLocales" :key="code">
                    <a
                        :href="localeSwitchUrl(page.url, code)"
                        :aria-current="code === locale ? 'true' : undefined"
                        :lang="code"
                        data-testid="locale-switcher__option"
                        :hreflang="code"
                        class="flex items-center justify-between rounded-xl border px-4 py-3 text-sm transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            code === locale
                                ? 'border-primary font-medium'
                                : 'border-border'
                        "
                    >
                        <span>{{ t(`nav.locales.${code}`) }}</span>
                        <Check
                            v-if="code === locale"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </a>
                </li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
