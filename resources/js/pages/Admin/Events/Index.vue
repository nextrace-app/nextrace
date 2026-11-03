<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
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
import { stateClass } from './state';

type Option = { id: number; name: string };
type Item = {
    id: number;
    slug: string;
    name: string;
    starts_on: string;
    region: string | null;
    organizer: string | null;
    state: 'draft' | 'in_review' | 'published';
    edit_url: string | null;
    delete_url: string | null;
};

const props = defineProps<{
    filters: {
        search: string;
        state: string;
        region_id: number | null;
        organizer_id: number | null;
    };
    items: {
        data: Item[];
        from: number | null;
        to: number | null;
        total: number;
        prev_url: string | null;
        next_url: string | null;
    };
    regions: Option[];
    organizers: Option[];
    canCreate: boolean;
    urls: { index: string; create: string };
}>();

const { t } = useI18n();
const page = usePage();

const search = ref(props.filters.search);
const state = ref(props.filters.state);
const regionId = ref<number | null>(props.filters.region_id);
const organizerId = ref<number | null>(props.filters.organizer_id);
const pending = ref<Item | null>(null);
const processing = ref(false);

const deleteError = computed(
    () => page.props.errors?.delete as string | undefined,
);

const apply = () => {
    const query: Record<string, string | number> = {};

    if (search.value) {
        query.search = search.value;
    }

    if (state.value) {
        query.state = state.value;
    }

    if (regionId.value) {
        query.region_id = regionId.value;
    }

    if (organizerId.value) {
        query.organizer_id = organizerId.value;
    }

    router.get(props.urls.index, query, { preserveState: true, replace: true });
};

const confirmDelete = () => {
    if (!pending.value?.delete_url) {
        return;
    }

    router.delete(pending.value.delete_url, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => (pending.value = null),
    });
};

const select = 'min-h-11 rounded-md border bg-transparent px-3 text-sm';
</script>

<template>
    <Head :title="t('admin.events.title')" />

    <AdminShell>
        <main class="flex flex-col gap-6" data-testid="admin-events">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <h1
                        class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                        data-testid="admin-events__title"
                    >
                        {{ t('admin.events.title') }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t('admin.events.lead') }}
                    </p>
                </div>
                <Button
                    v-if="canCreate"
                    as-child
                    data-testid="admin-events__create"
                >
                    <Link :href="urls.create">{{ t('admin.events.new') }}</Link>
                </Button>
            </div>

            <form
                class="flex flex-wrap gap-2"
                role="search"
                data-testid="admin-events__filters"
                @submit.prevent="apply"
            >
                <Input
                    v-model="search"
                    type="search"
                    class="max-w-xs"
                    :aria-label="t('admin.reference.search_label')"
                    :placeholder="t('admin.events.search_placeholder')"
                    data-testid="admin-events__search"
                />
                <select
                    v-model="state"
                    :class="select"
                    data-testid="admin-events__state"
                    @change="apply"
                >
                    <option value="">{{ t('admin.events.all_states') }}</option>
                    <option
                        v-for="key in ['draft', 'in_review', 'published']"
                        :key="key"
                        :value="key"
                    >
                        {{ t(`admin.events.states.${key}`) }}
                    </option>
                </select>
                <select
                    v-model="regionId"
                    :class="select"
                    data-testid="admin-events__region"
                    @change="apply"
                >
                    <option :value="null">
                        {{ t('admin.events.all_regions') }}
                    </option>
                    <option
                        v-for="region in regions"
                        :key="region.id"
                        :value="region.id"
                    >
                        {{ region.name }}
                    </option>
                </select>
                <select
                    v-if="organizers.length > 0"
                    v-model="organizerId"
                    :class="select"
                    data-testid="admin-events__organizer"
                    @change="apply"
                >
                    <option :value="null">
                        {{ t('admin.events.all_organizers') }}
                    </option>
                    <option
                        v-for="organizer in organizers"
                        :key="organizer.id"
                        :value="organizer.id"
                    >
                        {{ organizer.name }}
                    </option>
                </select>
                <Button type="submit" variant="outline">
                    {{ t('admin.reference.search_button') }}
                </Button>
            </form>

            <div
                v-if="items.data.length === 0"
                class="flex flex-col items-center gap-2 rounded-lg border bg-card px-8 py-12 text-center"
                data-testid="admin-events__empty"
            >
                <h2 class="text-lg font-bold">
                    {{ t('admin.reference.empty_title') }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ t('admin.reference.empty_text') }}
                </p>
            </div>

            <div
                v-else
                class="overflow-x-auto rounded-lg border bg-card"
                data-testid="admin-events__table"
            >
                <table class="w-full text-left text-sm">
                    <thead class="bg-background text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('admin.reference.name') }}
                            </th>
                            <th class="px-4 py-3 font-medium max-sm:hidden">
                                {{ t('admin.events.date') }}
                            </th>
                            <th class="px-4 py-3 font-medium max-lg:hidden">
                                {{ t('admin.events.region') }}
                            </th>
                            <th class="px-4 py-3 font-medium max-lg:hidden">
                                {{ t('admin.events.organizer') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('admin.events.state') }}
                            </th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-t"
                            data-testid="admin-events__row"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ item.name }}
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground max-sm:hidden"
                            >
                                {{ item.starts_on }}
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground max-lg:hidden"
                            >
                                {{ item.region }}
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground max-lg:hidden"
                            >
                                {{ item.organizer }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="stateClass(item.state)"
                                    data-testid="admin-events__badge"
                                >
                                    {{ t(`admin.events.states.${item.state}`) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-4">
                                    <Link
                                        v-if="item.edit_url"
                                        :href="item.edit_url"
                                        class="text-primary hover:underline"
                                        data-testid="admin-events__edit"
                                    >
                                        {{ t('admin.reference.edit') }}
                                    </Link>
                                    <button
                                        v-if="item.delete_url"
                                        type="button"
                                        class="text-destructive hover:underline"
                                        data-testid="admin-events__delete"
                                        @click="pending = item"
                                    >
                                        {{ t('admin.reference.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="items.total > 0"
                class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                data-testid="admin-events__pagination"
            >
                <span class="flex-1">
                    {{
                        t('admin.reference.showing', {
                            from: items.from ?? 0,
                            to: items.to ?? 0,
                            total: items.total,
                        })
                    }}
                </span>
                <Button v-if="items.prev_url" as-child variant="outline">
                    <Link :href="items.prev_url">{{
                        t('admin.reference.previous')
                    }}</Link>
                </Button>
                <Button v-if="items.next_url" as-child variant="outline">
                    <Link :href="items.next_url">{{
                        t('admin.reference.next')
                    }}</Link>
                </Button>
            </div>
        </main>

        <Dialog
            :open="pending !== null"
            @update:open="(open) => !open && (pending = null)"
        >
            <DialogContent data-testid="admin-events__dialog">
                <DialogHeader>
                    <DialogTitle>{{
                        t('admin.events.delete_title')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ pending?.name }} -
                        {{ t('admin.events.delete_text') }}
                    </DialogDescription>
                </DialogHeader>
                <p v-if="deleteError" class="text-sm text-destructive">
                    {{ deleteError }}
                </p>
                <DialogFooter>
                    <Button
                        variant="outline"
                        type="button"
                        @click="pending = null"
                    >
                        {{ t('admin.reference.cancel') }}
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        :disabled="processing"
                        data-testid="admin-events__confirm-delete"
                        @click="confirmDelete"
                    >
                        {{ t('admin.reference.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminShell>
</template>
