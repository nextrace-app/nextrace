<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/composables/useLocale';
import { confirm as confirmStore, confirmOptions } from '@/routes/passkey';
import { store } from '@/routes/password/confirm';

const { t } = useI18n();
const { localeRoute } = useLocale();

defineOptions({
    layout: {
        title: 'auth.confirm.title',
        description: 'auth.confirm.description',
    },
});
</script>

<template>
    <Head :title="t('auth.confirm.title')" />

    <PasskeyVerify
        :routes="{
            options: localeRoute(confirmOptions()),
            submit: localeRoute(confirmStore()),
        }"
        :label="t('auth.confirm.passkey')"
        :loading-label="t('auth.confirm.passkey_loading')"
        :separator="t('auth.confirm.separator')"
    />

    <Form
        v-bind="localeRoute(store.form())"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password">{{ t('form.password') }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.confirm.submit') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
