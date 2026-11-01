<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GoogleButton from '@/components/GoogleButton.vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/composables/useLocale';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const { t } = useI18n();
const { localeRoute } = useLocale();

defineOptions({
    layout: {
        title: 'auth.login.title',
        description: 'auth.login.description',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head :title="t('auth.login.title')" />

    <div
        v-if="status"
        data-testid="login-page__status"
        class="mb-4 text-center text-sm font-medium text-primary"
    >
        {{ status }}
    </div>

    <GoogleButton class="mb-6" />

    <PasskeyVerify />

    <Form
        v-bind="localeRoute(store.form())"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
        data-testid="login-page"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{{ t('form.email') }}</Label>
                <Input
                    id="email"
                    data-testid="login-page__email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">{{ t('form.password') }}</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="localeRoute(request())"
                        class="text-sm"
                        :tabindex="5"
                        data-testid="login-page__forgot"
                    >
                        {{ t('auth.login.forgot') }}
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    data-testid="login-page__password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('form.password')"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        data-testid="login-page__remember"
                    />
                    <span>{{ t('auth.login.remember') }}</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-testid="login-page__submit"
            >
                <Spinner v-if="processing" />
                {{ t('auth.login.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.login.no_account') }}
            <TextLink
                :href="localeRoute(register())"
                :tabindex="5"
                data-testid="login-page__register"
                >{{ t('auth.login.sign_up') }}</TextLink
            >
        </div>
    </Form>
</template>
