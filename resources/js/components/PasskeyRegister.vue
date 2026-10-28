<script setup lang="ts">
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const { t } = useI18n();

const emit = defineEmits<{
    success: [];
}>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;

    const browser = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'].find(
        (browser) => new RegExp(browser).test(ua),
    );

    const os = ['iPhone', 'iPad', 'Android', 'Mac', 'Windows'].find((os) =>
        new RegExp(os).test(ua),
    );

    return [browser, os].filter(Boolean).join(' on ') || '';
};

const name = ref(getDefaultPasskeyName());
const showForm = ref(false);

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        showForm.value = false;
        emit('success');
    },
});

const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!name.value.trim()) {
        return;
    }

    await register(name.value);
};

const handleCancel = () => {
    showForm.value = false;
    name.value = '';
};
</script>

<template>
    <div
        v-if="!isSupported"
        class="text-sm text-muted-foreground"
        data-testid="passkey-register__unsupported"
    >
        {{ t('settings.passkeys.unsupported') }}
    </div>

    <Button
        v-else-if="!showForm"
        variant="outline"
        data-testid="passkey-register__add"
        @click="showForm = true"
    >
        {{ t('settings.passkeys.add') }}
    </Button>

    <form
        v-else
        data-testid="passkey-register"
        @submit="handleSubmit"
        class="space-y-4 rounded-lg border border-border bg-muted/50 p-4"
    >
        <div class="grid gap-2">
            <Label for="passkey-name">{{ t('settings.passkeys.name') }}</Label>
            <Input
                id="passkey-name"
                data-testid="passkey-register__name"
                type="text"
                v-model="name"
                :placeholder="t('settings.passkeys.name_placeholder')"
                class="mt-1 block w-full border-foreground/20"
                autofocus
            />
            <p class="text-xs text-muted-foreground">
                {{ t('settings.passkeys.name_hint') }}
            </p>
        </div>

        <InputError v-if="error" :message="error" />

        <div class="flex gap-2">
            <Button
                type="submit"
                :disabled="isLoading || !name.trim()"
                data-testid="passkey-register__submit"
            >
                {{
                    isLoading
                        ? t('settings.passkeys.registering')
                        : t('settings.passkeys.register')
                }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                data-testid="passkey-register__cancel"
                @click="handleCancel"
            >
                {{ t('form.cancel') }}
            </Button>
        </div>
    </form>
</template>
