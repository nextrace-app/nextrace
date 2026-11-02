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

type Item = {
    id: number;
    slug: string;
    name: string;
    events_count: number;
    edit_url: string | null;
    delete_url: string | null;
};

const props = defineProps<{
    section: 'regions' | 'series';
    search: string;
    items: {
        data: Item[];
        from: number | null;
        to: number | null;
        total: number;
        prev_url: string | null;
        next_url: string | null;
    };
    canCreate: boolean;
    urls: { index: string; create: string };
}>();

const { t } = useI18n();
const page = usePage();

const query = ref(props.search);
const pending = ref<Item | null>(null);
const processing = ref(false);

const title = computed(() => t(`admin.reference.${props.section}.title`));
const deleteError = computed(
    () => page.props.errors?.delete as string | undefined,
);
const isBlocked = computed(() => (pending.value?.events_count ?? 0) > 0);

const submitSearch = () => {
    router.get(props.urls.index, query.value ? { search: query.value } : {}, {
        preserveState: true,
        replace: true,
    });
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
</script>

<template>
    <Head :title="title" />

    <AdminShell>
        <main class="flex flex-col gap-6" data-testid="admin-reference">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <h1
                        class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                        data-testid="admin-reference__title"
                    >
                        {{ title }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t(`admin.reference.${section}.lead`) }}
                    </p>
                </div>
                <Button
                    v-if="canCreate"
                    as-child
                    data-testid="admin-reference__create"
                >
                    <Link :href="urls.create">
                        {{ t(`admin.reference.${section}.new`) }}
                    </Link>
                </Button>
            </div>

            <form
                class="flex gap-2"
                role="search"
                data-testid="admin-reference__search"
                @submit.prevent="submitSearch"
            >
                <Input
                    v-model="query"
                    type="search"
                    class="max-w-sm"
                    :aria-label="t('admin.reference.search_label')"
                    :placeholder="t('admin.reference.search_placeholder')"
                    data-testid="admin-reference__search-input"
                />
                <Button type="submit" variant="outline">
                    {{ t('admin.reference.search_button') }}
                </Button>
            </form>

            <div
                v-if="items.data.length === 0"
                class="flex flex-col items-center gap-2 rounded-lg border bg-card px-8 py-12 text-center"
                data-testid="admin-reference__empty"
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
                data-testid="admin-reference__table"
            >
                <table class="w-full text-left text-sm">
                    <thead class="bg-background text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('admin.reference.name') }}
                            </th>
                            <th class="px-4 py-3 font-medium max-sm:hidden">
                                {{ t('admin.reference.slug') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('admin.reference.events') }}
                            </th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-t"
                            data-testid="admin-reference__row"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ item.name }}
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground max-sm:hidden"
                            >
                                {{ item.slug }}
                            </td>
                            <td class="px-4 py-3">{{ item.events_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-4">
                                    <Link
                                        v-if="item.edit_url"
                                        :href="item.edit_url"
                                        class="text-primary hover:underline"
                                        data-testid="admin-reference__edit"
                                    >
                                        {{ t('admin.reference.edit') }}
                                    </Link>
                                    <button
                                        v-if="item.delete_url"
                                        type="button"
                                        class="text-destructive hover:underline"
                                        data-testid="admin-reference__delete"
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
                data-testid="admin-reference__pagination"
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
            <DialogContent data-testid="admin-reference__dialog">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            isBlocked
                                ? t('admin.reference.blocked_title', {
                                      name: pending?.name,
                                  })
                                : t('admin.reference.delete_title', {
                                      name: pending?.name,
                                  })
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            isBlocked
                                ? t('admin.reference.delete_blocked', {
                                      count: pending?.events_count,
                                  })
                                : t('admin.reference.delete_text')
                        }}
                    </DialogDescription>
                </DialogHeader>
                <p
                    v-if="deleteError"
                    class="text-sm text-destructive"
                    data-testid="admin-reference__delete-error"
                >
                    {{ deleteError }}
                </p>
                <DialogFooter>
                    <Button
                        variant="outline"
                        type="button"
                        @click="pending = null"
                    >
                        {{
                            isBlocked
                                ? t('admin.reference.close')
                                : t('admin.reference.cancel')
                        }}
                    </Button>
                    <Button
                        v-if="!isBlocked"
                        variant="destructive"
                        type="button"
                        :disabled="processing"
                        data-testid="admin-reference__confirm-delete"
                        @click="confirmDelete"
                    >
                        {{ t('admin.reference.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminShell>
</template>
