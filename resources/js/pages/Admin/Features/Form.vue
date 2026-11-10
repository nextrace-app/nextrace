<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    flag: { key: string; description: string } | null;
    urls: { index: string; submit: string };
}>();

const { t } = useI18n();

const form = useForm({
    key: props.flag?.key ?? '',
    description: props.flag?.description ?? '',
});

const isNew = computed(() => props.flag === null);

const title = computed(() =>
    t(
        isNew.value
            ? 'admin.features.create_title'
            : 'admin.features.edit_title',
    ),
);

const submit = () => {
    if (isNew.value) {
        form.post(props.urls.submit);
    } else {
        // The key never changes, so it is not sent.
        form.transform((data) => ({ description: data.description })).put(
            props.urls.submit,
        );
    }
};
</script>

<template>
    <Head :title="title" />

    <AdminShell>
        <main
            class="flex flex-col gap-4 sm:gap-6"
            data-testid="admin-feature-form"
        >
            <h1
                class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                data-testid="admin-feature-form__title"
            >
                {{ title }}
            </h1>

            <form
                class="flex flex-col gap-6 rounded-lg border bg-card p-4 sm:p-6 lg:max-w-[600px]"
                data-testid="admin-feature-form__form"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="key">{{ t('admin.features.key') }}</Label>
                    <Input
                        id="key"
                        v-model="form.key"
                        :readonly="!isNew"
                        :aria-readonly="!isNew"
                        autocomplete="off"
                        spellcheck="false"
                        :class="!isNew && 'bg-background text-muted-foreground'"
                        placeholder="posts"
                        aria-describedby="key-hint"
                        data-testid="admin-feature-form__key"
                    />
                    <p
                        id="key-hint"
                        class="text-[13px] leading-[18px] text-muted-foreground"
                    >
                        {{
                            isNew
                                ? t('admin.features.key_hint')
                                : t('admin.features.key_locked')
                        }}
                    </p>
                    <InputError :message="form.errors.key" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">{{
                        t('admin.features.description')
                    }}</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        aria-describedby="description-hint"
                        data-testid="admin-feature-form__description"
                    />
                    <p
                        id="description-hint"
                        class="text-[13px] leading-[18px] text-muted-foreground"
                    >
                        {{ t('admin.features.description_hint') }}
                    </p>
                    <InputError :message="form.errors.description" />
                </div>

                <div class="flex gap-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-testid="admin-feature-form__submit"
                    >
                        {{
                            form.processing
                                ? t('admin.features.saving')
                                : isNew
                                  ? t('admin.features.create')
                                  : t('admin.features.save')
                        }}
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="urls.index">{{
                            t('admin.features.cancel')
                        }}</Link>
                    </Button>
                </div>
            </form>
        </main>
    </AdminShell>
</template>
