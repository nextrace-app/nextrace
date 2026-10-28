<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GoogleButton from '@/components/GoogleButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/composables/useLocale';
import { login } from '@/routes';
import { store } from '@/routes/register';

const { t } = useI18n();
const { localeRoute } = useLocale();

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'auth.register.title',
        description: 'auth.register.description',
    },
});
</script>

<template>
    <Head :title="t('auth.register.title')" />

    <GoogleButton class="mb-6" />

    <Form
        v-bind="localeRoute(store.form())"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
        data-testid="register-page"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">{{ t('form.name') }}</Label>
                <Input
                    id="name"
                    data-testid="register-page__name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    :placeholder="t('form.full_name')"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('form.email') }}</Label>
                <Input
                    id="email"
                    data-testid="register-page__email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ t('form.password') }}</Label>
                <PasswordInput
                    id="password"
                    data-testid="register-page__password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('form.password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('form.confirm_password')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    data-testid="register-page__password-confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('form.confirm_password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-testid="register-page__submit"
            >
                <Spinner v-if="processing" />
                {{ t('auth.register.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.register.have_account') }}
            <TextLink
                :href="localeRoute(login())"
                class="underline underline-offset-4"
                :tabindex="6"
                data-testid="register-page__login"
                >{{ t('auth.register.log_in') }}</TextLink
            >
        </div>
    </Form>
</template>
