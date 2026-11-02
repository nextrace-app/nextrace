<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Translated = { el?: string; en?: string };

const props = defineProps<{
    section: 'regions' | 'series';
    record: {
        id: number;
        slug: string;
        name: Translated;
        parent_id?: number | null;
        description?: Translated | null;
        logo_url?: string | null;
        website?: string | null;
    } | null;
    urls: { index: string; submit: string };
    parents?: { id: number; name: string }[];
}>();

const { t } = useI18n();
const locales = ['el', 'en'] as const;
const activeLocale = ref<(typeof locales)[number]>('el');

const form = useForm({
    slug: props.record?.slug ?? '',
    name: { el: props.record?.name.el ?? '', en: props.record?.name.en ?? '' },
    parent_id: props.record?.parent_id ?? null,
    description: {
        el: props.record?.description?.el ?? '',
        en: props.record?.description?.en ?? '',
    },
    logo_url: props.record?.logo_url ?? '',
    website: props.record?.website ?? '',
});

const title = computed(() =>
    t(
        `admin.reference.${props.section}.${props.record ? 'edit_title' : 'create_title'}`,
    ),
);

const errorFor = (key: string) =>
    (form.errors as Record<string, string | undefined>)[key];

const hasErrorIn = (locale: string) =>
    Boolean(errorFor('name.' + locale) || errorFor('description.' + locale));

const submit = () => {
    form.transform((data) => ({
        ...data,
        parent_id: data.parent_id === null ? null : Number(data.parent_id),
    }));

    if (props.record) {
        form.put(props.urls.submit);
    } else {
        form.post(props.urls.submit);
    }
};
</script>

<template>
    <Head :title="title" />

    <AdminShell>
        <main class="flex flex-col gap-6" data-testid="admin-form">
            <h1
                class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                data-testid="admin-form__title"
            >
                {{ title }}
            </h1>

            <form
                class="flex max-w-xl flex-col gap-6 rounded-lg border bg-card p-6"
                data-testid="admin-form__form"
                @submit.prevent="submit"
            >
                <div class="flex flex-col gap-2">
                    <div class="flex gap-2" role="tablist">
                        <button
                            v-for="locale in locales"
                            :key="locale"
                            type="button"
                            role="tab"
                            :aria-selected="activeLocale === locale"
                            :class="[
                                'min-h-11 rounded-full border px-4 text-sm font-bold',
                                activeLocale === locale
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : hasErrorIn(locale)
                                      ? 'border-destructive text-destructive'
                                      : 'bg-card',
                            ]"
                            :data-testid="`admin-form__tab-${locale}`"
                            @click="activeLocale = locale"
                        >
                            {{ t(`admin.reference.language_${locale}`) }}
                        </button>
                    </div>

                    <div
                        v-for="locale in locales"
                        v-show="activeLocale === locale"
                        :key="locale"
                        class="flex flex-col gap-4"
                    >
                        <div class="grid gap-2">
                            <Label :for="`name-${locale}`">{{
                                t('admin.reference.name_label')
                            }}</Label>
                            <Input
                                :id="`name-${locale}`"
                                v-model="form.name[locale]"
                                :data-testid="`admin-form__name-${locale}`"
                            />
                            <InputError :message="errorFor('name.' + locale)" />
                        </div>

                        <div v-if="section === 'series'" class="grid gap-2">
                            <Label :for="`description-${locale}`">{{
                                t('admin.reference.description_label')
                            }}</Label>
                            <textarea
                                :id="`description-${locale}`"
                                v-model="form.description[locale]"
                                rows="4"
                                class="rounded-md border bg-transparent px-3 py-2 text-sm"
                                :data-testid="`admin-form__description-${locale}`"
                            />
                            <InputError
                                :message="errorFor('description.' + locale)"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="slug">{{ t('admin.reference.slug') }}</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        data-testid="admin-form__slug"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ t('admin.reference.slug_hint') }}
                    </p>
                    <InputError :message="form.errors.slug" />
                </div>

                <div v-if="section === 'regions'" class="grid gap-2">
                    <Label for="parent_id">{{
                        t('admin.reference.regions.parent')
                    }}</Label>
                    <select
                        id="parent_id"
                        v-model="form.parent_id"
                        class="min-h-11 rounded-md border bg-transparent px-3 text-sm"
                        data-testid="admin-form__parent"
                    >
                        <option :value="null">
                            {{ t('admin.reference.regions.parent_none') }}
                        </option>
                        <option
                            v-for="parent in parents"
                            :key="parent.id"
                            :value="parent.id"
                        >
                            {{ parent.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.parent_id" />
                </div>

                <template v-if="section === 'series'">
                    <div class="grid gap-2">
                        <Label for="logo_url">{{
                            t('admin.reference.series.logo_url')
                        }}</Label>
                        <Input
                            id="logo_url"
                            v-model="form.logo_url"
                            type="url"
                            data-testid="admin-form__logo-url"
                        />
                        <InputError :message="form.errors.logo_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="website">{{
                            t('admin.reference.series.website')
                        }}</Label>
                        <Input
                            id="website"
                            v-model="form.website"
                            type="url"
                            data-testid="admin-form__website"
                        />
                        <InputError :message="form.errors.website" />
                    </div>
                </template>

                <div class="flex gap-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-testid="admin-form__submit"
                    >
                        {{
                            form.processing
                                ? t('admin.reference.saving')
                                : t('admin.reference.save')
                        }}
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="urls.index">{{
                            t('admin.reference.cancel')
                        }}</Link>
                    </Button>
                </div>
            </form>
        </main>
    </AdminShell>
</template>
