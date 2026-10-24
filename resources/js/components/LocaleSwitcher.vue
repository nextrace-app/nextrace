<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
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
                data-test="locale-switcher"
            >
                <Globe class="size-[18px]" aria-hidden="true" />
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>{{ t('nav.chooseLanguage') }}</DialogTitle>
                <DialogDescription class="sr-only">
                    {{ t('nav.chooseLanguageDescription') }}
                </DialogDescription>
            </DialogHeader>
            <ul class="flex flex-col gap-1">
                <li v-for="code in supportedLocales" :key="code">
                    <Link
                        :href="localeSwitchUrl(page.url, code)"
                        :aria-current="code === locale ? 'true' : undefined"
                        :lang="code"
                        :hreflang="code"
                        class="flex items-center justify-between rounded-xl border px-4 py-3 text-sm transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            code === locale
                                ? 'border-primary font-medium'
                                : 'border-border'
                        "
                        @click="open = false"
                    >
                        <span>{{ t(`nav.locales.${code}`) }}</span>
                        <Check
                            v-if="code === locale"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
