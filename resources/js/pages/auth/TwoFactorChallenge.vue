<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { useLocale } from '@/composables/useLocale';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const { t } = useI18n();
const { localeRoute } = useLocale();

const showRecoveryInput = ref<boolean>(false);
const code = ref<string>('');

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'auth.two_factor.recovery_title',
            description: 'auth.two_factor.recovery_description',
            buttonText: 'auth.two_factor.use_code',
        };
    }

    return {
        title: 'auth.two_factor.code_title',
        description: 'auth.two_factor.code_description',
        buttonText: 'auth.two_factor.use_recovery',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};
</script>

<template>
    <Head :title="t('auth.two_factor.title')" />

    <div class="space-y-6" data-testid="two-factor-challenge-page">
        <template v-if="!showRecoveryInput">
            <Form
                v-bind="localeRoute(store.form())"
                class="space-y-4"
                reset-on-error
                data-testid="two-factor-challenge-page__code-form"
                @error="code = ''"
                #default="{ errors, processing, clearErrors }"
            >
                <input type="hidden" name="code" :value="code" />
                <div
                    class="flex flex-col items-center justify-center space-y-3 text-center"
                >
                    <div class="flex w-full items-center justify-center">
                        <InputOTP
                            v-model="code"
                            data-testid="two-factor-challenge-page__code"
                            :maxlength="6"
                            :disabled="processing"
                            autofocus
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="index in 6"
                                    :key="index"
                                    :index="index - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="errors.code" />
                </div>
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="processing"
                    data-testid="two-factor-challenge-page__submit"
                    >{{ t('auth.two_factor.continue') }}</Button
                >
                <div class="text-center text-sm text-muted-foreground">
                    <span>{{ t('auth.two_factor.or_you_can') }} </span>
                    <button
                        type="button"
                        data-testid="two-factor-challenge-page__toggle"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ t(authConfigContent.buttonText) }}
                    </button>
                </div>
            </Form>
        </template>

        <template v-else>
            <Form
                v-bind="localeRoute(store.form())"
                class="space-y-4"
                reset-on-error
                data-testid="two-factor-challenge-page__recovery-form"
                #default="{ errors, processing, clearErrors }"
            >
                <Input
                    name="recovery_code"
                    data-testid="two-factor-challenge-page__recovery-code"
                    type="text"
                    :placeholder="t('auth.two_factor.recovery_placeholder')"
                    :aria-label="t('auth.two_factor.recovery_title')"
                    :autofocus="showRecoveryInput"
                    required
                />
                <InputError :message="errors.recovery_code" />
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="processing"
                    data-testid="two-factor-challenge-page__submit"
                    >{{ t('auth.two_factor.continue') }}</Button
                >

                <div class="text-center text-sm text-muted-foreground">
                    <span>{{ t('auth.two_factor.or_you_can') }} </span>
                    <button
                        type="button"
                        data-testid="two-factor-challenge-page__toggle"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ t(authConfigContent.buttonText) }}
                    </button>
                </div>
            </Form>
        </template>
    </div>
</template>
