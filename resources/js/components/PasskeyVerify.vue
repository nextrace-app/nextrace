<script setup lang="ts">
import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { KeyRound } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/composables/useLocale';
import { login as passkeyLogin, loginOptions } from '@/routes/passkey';

type Props = {
    routes?: {
        options: UrlMethodPair;
        submit: UrlMethodPair;
    };
    label?: string;
    loadingLabel?: string;
    separator?: string;
};

const props = defineProps<Props>();

const { t } = useI18n();
const { localeUrl } = useLocale();

// The package's own defaults point at the default locale, so the passkey endpoints
// are resolved here instead and moved onto the locale the page is being served in.
const routes = props.routes ?? {
    options: loginOptions(),
    submit: passkeyLogin(),
};

const { verify, isLoading, error, isSupported } = usePasskeyVerify({
    routes: {
        options: localeUrl(routes.options.url),
        submit: localeUrl(routes.submit.url),
    },
    onSuccess: (response) => {
        router.visit(response.redirect ?? localeUrl('/dashboard'));
    },
});
</script>

<template>
    <div v-if="isSupported">
        <div class="grid gap-2">
            <Button
                type="button"
                variant="outline"
                class="w-full"
                @click="verify"
                :disabled="isLoading"
            >
                <Spinner v-if="isLoading" />
                <KeyRound v-else class="h-4 w-4" />
                {{
                    isLoading
                        ? (props.loadingLabel ??
                          t('auth.passkey.authenticating'))
                        : (props.label ?? t('auth.passkey.sign_in'))
                }}
            </Button>

            <div v-if="error" class="text-center">
                <InputError :message="error" />
            </div>
        </div>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <Separator class="w-full" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">
                    {{ props.separator ?? t('auth.passkey.separator') }}
                </span>
            </div>
        </div>
    </div>
</template>
