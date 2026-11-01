<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/composables/useLocale';
import { login } from '@/routes';
import { email } from '@/routes/password';

const { t } = useI18n();
const { localeRoute } = useLocale();

defineOptions({
    layout: {
        title: 'auth.forgot.title',
        description: 'auth.forgot.description',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.forgot.title')" />

    <div
        v-if="status"
        data-testid="forgot-password-page__status"
        class="mb-4 text-center text-sm font-medium text-primary"
    >
        {{ status }}
    </div>

    <div class="space-y-6" data-testid="forgot-password-page">
        <Form
            v-bind="localeRoute(email.form())"
            data-testid="forgot-password-page__form"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="email">{{ t('form.email') }}</Label>
                <Input
                    id="email"
                    data-testid="forgot-password-page__email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-testid="forgot-password-page__submit"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.forgot.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ t('auth.forgot.return_to') }}</span>
            <TextLink
                :href="localeRoute(login())"
                data-testid="forgot-password-page__login"
                >{{ t('auth.forgot.log_in') }}</TextLink
            >
        </div>
    </div>
</template>
