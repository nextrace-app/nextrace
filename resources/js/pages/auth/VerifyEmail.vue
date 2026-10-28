<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/composables/useLocale';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const { t } = useI18n();
const { localeRoute } = useLocale();

defineOptions({
    layout: {
        title: 'auth.verify.title',
        description: 'auth.verify.description',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.verify.title')" />

    <div
        v-if="status === 'verification-link-sent'"
        data-testid="verify-email-page__status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ t('auth.verify.link_sent') }}
    </div>

    <Form
        v-bind="localeRoute(send.form())"
        class="space-y-6 text-center"
        data-testid="verify-email-page"
        v-slot="{ processing }"
    >
        <Button
            :disabled="processing"
            variant="secondary"
            data-testid="verify-email-page__resend"
        >
            <Spinner v-if="processing" />
            {{ t('auth.verify.resend') }}
        </Button>

        <TextLink
            :href="localeRoute(logout())"
            as="button"
            class="mx-auto block text-sm"
            data-testid="verify-email-page__logout"
        >
            {{ t('auth.verify.logout') }}
        </TextLink>
    </Form>
</template>
