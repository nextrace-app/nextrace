<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminEventRaces from '@/components/AdminEventRaces.vue';
import type { EventLink, Race } from '@/components/AdminEventRaces.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { stateClass } from './state';

type Translated = { el?: string; en?: string };
type Option = { id: number; name: string };
type RaceRecord = Omit<
    Race,
    'name' | 'prices' | 'terrain' | 'elevation_gain_m' | 'registration_url'
> & {
    name: Translated;
    terrain: string | null;
    elevation_gain_m: number | null;
    registration_url: string | null;
    prices: {
        label: Translated | null;
        amount_cents: number;
        valid_from: string | null;
        valid_until: string | null;
    }[];
};
type LinkRecord = { type: string; url: string; label: Translated | null };

const props = defineProps<{
    record: {
        slug: string;
        name: Translated;
        description: Translated | null;
        venue: Translated | null;
        package_description: Translated | null;
        cancellation_policy: Translated | null;
        starts_on: string;
        ends_on: string | null;
        city: string;
        address: string | null;
        region_id: number | null;
        organizer_id: number | null;
        series_id: number | null;
        edition_number: number | null;
        status: string;
        date_confirmed: boolean;
        registration_status: string;
        registration_url: string | null;
        registration_opens_at: string | null;
        registration_closes_at: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        source_url: string | null;
        image_url: string | null;
        medical_certificate_required: boolean;
        state: 'draft' | 'in_review' | 'published';
        races: RaceRecord[];
        links: LinkRecord[];
        urls: {
            submit: string;
            publish: string;
            unpublish: string;
            destroy: string;
        };
    } | null;
    urls: { index: string; submit: string };
    regions: Option[];
    organizers: Option[];
    series: Option[];
    statuses: string[];
    registrationStatuses: string[];
    raceOptions: {
        disciplines: string[];
        terrains: string[];
        statuses: string[];
        linkTypes: string[];
    };
    canPublish: boolean;
    canDelete: boolean;
}>();

const { t } = useI18n();
const locales = ['el', 'en'] as const;
const activeLocale = ref<(typeof locales)[number]>('el');
const confirming = ref<'publish' | 'unpublish' | 'delete' | null>(null);

const pair = (value?: Translated | null) => ({
    el: value?.el ?? '',
    en: value?.en ?? '',
});
const r = props.record;

const form = useForm({
    slug: r?.slug ?? '',
    name: pair(r?.name),
    description: pair(r?.description),
    venue: pair(r?.venue),
    package_description: pair(r?.package_description),
    cancellation_policy: pair(r?.cancellation_policy),
    starts_on: r?.starts_on ?? '',
    ends_on: r?.ends_on ?? '',
    city: r?.city ?? '',
    address: r?.address ?? '',
    region_id: r?.region_id ?? null,
    organizer_id:
        r?.organizer_id ??
        (props.organizers.length === 1 ? props.organizers[0].id : null),
    series_id: r?.series_id ?? null,
    edition_number: r?.edition_number ?? '',
    status: r?.status ?? 'scheduled',
    date_confirmed: r?.date_confirmed ?? true,
    registration_status: r?.registration_status ?? 'unknown',
    registration_url: r?.registration_url ?? '',
    registration_opens_at: r?.registration_opens_at ?? '',
    registration_closes_at: r?.registration_closes_at ?? '',
    contact_email: r?.contact_email ?? '',
    contact_phone: r?.contact_phone ?? '',
    source_url: r?.source_url ?? '',
    image_url: r?.image_url ?? '',
    medical_certificate_required: r?.medical_certificate_required ?? false,
    races: (r?.races ?? []).map(
        (race): Race => ({
            ...race,
            name: pair(race.name),
            terrain: race.terrain ?? '',
            elevation_gain_m: race.elevation_gain_m ?? '',
            registration_url: race.registration_url ?? '',
            prices: race.prices.map((price) => ({
                label: pair(price.label),
                amount_cents: price.amount_cents,
                valid_from: price.valid_from ?? '',
                valid_until: price.valid_until ?? '',
            })),
        }),
    ) as Race[],
    links: (r?.links ?? []).map(
        (link): EventLink => ({ ...link, label: pair(link.label) }),
    ),
});

const title = computed(() =>
    t(`admin.events.${props.record ? 'edit_title' : 'create_title'}`),
);

const errorFor = (key: string) =>
    (form.errors as Record<string, string | undefined>)[key];

const allErrors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const hasErrorIn = (locale: string) =>
    [
        'name',
        'description',
        'venue',
        'package_description',
        'cancellation_policy',
    ].some((field) => errorFor(`${field}.${locale}`));

const translatedFields = [
    { key: 'name', label: 'admin.reference.name_label', long: false },
    {
        key: 'description',
        label: 'admin.reference.description_label',
        long: true,
    },
    { key: 'venue', label: 'admin.events.fields.venue', long: false },
    {
        key: 'package_description',
        label: 'admin.events.fields.package_description',
        long: true,
    },
    {
        key: 'cancellation_policy',
        label: 'admin.events.fields.cancellation_policy',
        long: true,
    },
] as const;

const submit = () => {
    form.transform((data) => ({
        ...data,
        edition_number: data.edition_number === '' ? null : data.edition_number,
        ends_on: data.ends_on || null,
        registration_opens_at: data.registration_opens_at || null,
        registration_closes_at: data.registration_closes_at || null,
        races: data.races.map((race) => ({
            ...race,
            terrain: race.terrain || null,
            elevation_gain_m:
                race.elevation_gain_m === '' ? null : race.elevation_gain_m,
            registration_url: race.registration_url || null,
            prices: race.prices.map((price) => ({
                ...price,
                valid_from: price.valid_from || null,
                valid_until: price.valid_until || null,
            })),
        })),
    }));

    if (props.record) {
        form.put(props.urls.submit);
    } else {
        form.post(props.urls.submit);
    }
};

const post = (url: string) => router.post(url, {}, { preserveScroll: true });

const confirm = () => {
    if (!props.record) {
        return;
    }

    const { urls } = props.record;

    if (confirming.value === 'delete') {
        router.delete(urls.destroy);
    } else if (confirming.value === 'publish') {
        post(urls.publish);
    } else if (confirming.value === 'unpublish') {
        post(urls.unpublish);
    }

    confirming.value = null;
};

const field = 'min-h-11 rounded-md border bg-transparent px-3 text-sm';
</script>

<template>
    <Head :title="title" />

    <AdminShell>
        <main class="flex flex-col gap-6" data-testid="admin-event-form">
            <div class="flex flex-wrap items-center gap-4">
                <h1
                    class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                    data-testid="admin-event-form__title"
                >
                    {{ title }}
                </h1>
                <span
                    v-if="record"
                    :class="stateClass(record.state)"
                    data-testid="admin-event-form__state"
                >
                    {{ t(`admin.events.states.${record.state}`) }}
                </span>
            </div>

            <form
                class="flex max-w-3xl flex-col gap-6 rounded-lg border bg-card p-6"
                data-testid="admin-event-form__form"
                @submit.prevent="submit"
            >
                <div class="flex flex-col gap-4">
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
                            :data-testid="`admin-event-form__tab-${locale}`"
                            @click="activeLocale = locale"
                        >
                            {{ t(`admin.reference.language_${locale}`) }}
                        </button>
                    </div>

                    <template v-for="locale in locales" :key="locale">
                        <div
                            v-show="activeLocale === locale"
                            class="flex flex-col gap-4"
                        >
                            <div
                                v-for="item in translatedFields"
                                :key="item.key"
                                class="grid gap-2"
                            >
                                <Label :for="`${item.key}-${locale}`">{{
                                    t(item.label)
                                }}</Label>
                                <textarea
                                    v-if="item.long"
                                    :id="`${item.key}-${locale}`"
                                    v-model="form[item.key][locale]"
                                    rows="3"
                                    class="rounded-md border bg-transparent px-3 py-2 text-sm"
                                    :data-testid="`admin-event-form__${item.key}-${locale}`"
                                />
                                <Input
                                    v-else
                                    :id="`${item.key}-${locale}`"
                                    v-model="form[item.key][locale]"
                                    :data-testid="`admin-event-form__${item.key}-${locale}`"
                                />
                                <InputError
                                    :message="errorFor(`${item.key}.${locale}`)"
                                />
                            </div>
                        </div>
                    </template>
                </div>

                <div class="grid gap-2">
                    <Label for="slug">{{ t('admin.reference.slug') }}</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        data-testid="admin-event-form__slug"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ t('admin.reference.slug_hint') }}
                    </p>
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="starts_on">{{
                            t('admin.events.fields.starts_on')
                        }}</Label>
                        <Input
                            id="starts_on"
                            v-model="form.starts_on"
                            type="date"
                            data-testid="admin-event-form__starts-on"
                        />
                        <InputError :message="form.errors.starts_on" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="ends_on">{{
                            t('admin.events.fields.ends_on')
                        }}</Label>
                        <Input
                            id="ends_on"
                            v-model="form.ends_on"
                            type="date"
                        />
                        <InputError :message="form.errors.ends_on" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="city">{{
                            t('admin.events.fields.city')
                        }}</Label>
                        <Input
                            id="city"
                            v-model="form.city"
                            data-testid="admin-event-form__city"
                        />
                        <InputError :message="form.errors.city" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address">{{
                            t('admin.events.fields.address')
                        }}</Label>
                        <Input id="address" v-model="form.address" />
                        <InputError :message="form.errors.address" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="region_id">{{
                            t('admin.events.fields.region')
                        }}</Label>
                        <select
                            id="region_id"
                            v-model="form.region_id"
                            :class="field"
                        >
                            <option :value="null">
                                {{ t('admin.events.fields.none') }}
                            </option>
                            <option
                                v-for="o in regions"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.region_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="organizer_id">{{
                            t('admin.events.fields.organizer')
                        }}</Label>
                        <select
                            id="organizer_id"
                            v-model="form.organizer_id"
                            :class="field"
                            :disabled="!canPublish"
                            data-testid="admin-event-form__organizer"
                        >
                            <option :value="null">
                                {{ t('admin.events.fields.none') }}
                            </option>
                            <option
                                v-for="o in organizers"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.organizer_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="series_id">{{
                            t('admin.events.fields.series')
                        }}</Label>
                        <select
                            id="series_id"
                            v-model="form.series_id"
                            :class="field"
                        >
                            <option :value="null">
                                {{ t('admin.events.fields.none') }}
                            </option>
                            <option
                                v-for="o in series"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.series_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edition_number">{{
                            t('admin.events.fields.edition_number')
                        }}</Label>
                        <Input
                            id="edition_number"
                            v-model="form.edition_number"
                            type="number"
                            min="1"
                        />
                        <InputError :message="form.errors.edition_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">{{
                            t('admin.events.fields.status')
                        }}</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            :class="field"
                        >
                            <option v-for="s in statuses" :key="s" :value="s">
                                {{ t(`admin.events.status_values.${s}`) }}
                            </option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="registration_status">{{
                            t('admin.events.fields.registration_status')
                        }}</Label>
                        <select
                            id="registration_status"
                            v-model="form.registration_status"
                            :class="field"
                        >
                            <option
                                v-for="s in registrationStatuses"
                                :key="s"
                                :value="s"
                            >
                                {{ t(`admin.events.registration_values.${s}`) }}
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.registration_status"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="registration_url">{{
                            t('admin.events.fields.registration_url')
                        }}</Label>
                        <Input
                            id="registration_url"
                            v-model="form.registration_url"
                            type="url"
                        />
                        <InputError :message="form.errors.registration_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="registration_opens_at">{{
                            t('admin.events.fields.registration_opens_at')
                        }}</Label>
                        <Input
                            id="registration_opens_at"
                            v-model="form.registration_opens_at"
                            type="datetime-local"
                        />
                        <InputError
                            :message="form.errors.registration_opens_at"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="registration_closes_at">{{
                            t('admin.events.fields.registration_closes_at')
                        }}</Label>
                        <Input
                            id="registration_closes_at"
                            v-model="form.registration_closes_at"
                            type="datetime-local"
                        />
                        <InputError
                            :message="form.errors.registration_closes_at"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact_email">{{
                            t('admin.events.fields.contact_email')
                        }}</Label>
                        <Input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                        />
                        <InputError :message="form.errors.contact_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact_phone">{{
                            t('admin.events.fields.contact_phone')
                        }}</Label>
                        <Input
                            id="contact_phone"
                            v-model="form.contact_phone"
                        />
                        <InputError :message="form.errors.contact_phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="source_url">{{
                            t('admin.events.fields.source_url')
                        }}</Label>
                        <Input
                            id="source_url"
                            v-model="form.source_url"
                            type="url"
                        />
                        <InputError :message="form.errors.source_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="image_url">{{
                            t('admin.events.fields.image_url')
                        }}</Label>
                        <Input
                            id="image_url"
                            v-model="form.image_url"
                            type="url"
                        />
                        <InputError :message="form.errors.image_url" />
                    </div>
                </div>

                <AdminEventRaces
                    v-model:races="form.races"
                    v-model:links="form.links"
                    :errors="allErrors"
                    :options="raceOptions"
                />

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.date_confirmed" type="checkbox" />
                    {{ t('admin.events.fields.date_confirmed') }}
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.medical_certificate_required"
                        type="checkbox"
                    />
                    {{ t('admin.events.fields.medical_certificate_required') }}
                </label>

                <div class="flex flex-wrap gap-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-testid="admin-event-form__submit"
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

                <div
                    v-if="record"
                    class="flex flex-wrap gap-2 border-t pt-6"
                    data-testid="admin-event-form__moderation"
                >
                    <Button
                        v-if="record.state === 'draft'"
                        type="button"
                        variant="outline"
                        data-testid="admin-event-form__review"
                        @click="post(record.urls.submit)"
                    >
                        {{ t('admin.events.submit') }}
                    </Button>
                    <Button
                        v-if="canPublish && record.state !== 'published'"
                        type="button"
                        data-testid="admin-event-form__publish"
                        @click="confirming = 'publish'"
                    >
                        {{ t('admin.events.publish') }}
                    </Button>
                    <Button
                        v-if="canPublish && record.state === 'published'"
                        type="button"
                        variant="outline"
                        data-testid="admin-event-form__unpublish"
                        @click="confirming = 'unpublish'"
                    >
                        {{ t('admin.events.unpublish') }}
                    </Button>
                    <Button
                        v-if="canDelete"
                        type="button"
                        variant="destructive"
                        data-testid="admin-event-form__delete"
                        @click="confirming = 'delete'"
                    >
                        {{ t('admin.reference.delete') }}
                    </Button>
                </div>
            </form>
        </main>

        <Dialog
            :open="confirming !== null"
            @update:open="(open) => !open && (confirming = null)"
        >
            <DialogContent data-testid="admin-event-form__dialog">
                <DialogHeader>
                    <DialogTitle>{{
                        confirming ? t(`admin.events.${confirming}_title`) : ''
                    }}</DialogTitle>
                    <DialogDescription>{{
                        confirming ? t(`admin.events.${confirming}_text`) : ''
                    }}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        type="button"
                        @click="confirming = null"
                    >
                        {{ t('admin.reference.cancel') }}
                    </Button>
                    <Button
                        :variant="
                            confirming === 'delete' ? 'destructive' : 'default'
                        "
                        type="button"
                        data-testid="admin-event-form__confirm"
                        @click="confirm"
                    >
                        {{
                            confirming === 'delete'
                                ? t('admin.reference.delete')
                                : confirming
                                  ? t(`admin.events.${confirming}`)
                                  : ''
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminShell>
</template>
