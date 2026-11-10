<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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

type Flag = {
    key: string;
    description: string;
    is_active: boolean;
    updated_at: string | null;
    updated_by: string | null;
    edit_url: string | null;
    activate_url: string | null;
    delete_url: string | null;
};

defineProps<{
    flags: Flag[];
    canCreate: boolean;
    urls: { create: string };
}>();

const { t, locale } = useI18n();

/** The flag being switched, so its switch waits for the answer. */
const switching = ref<string | null>(null);
const pending = ref<Flag | null>(null);
const processing = ref(false);

const date = (iso: string): string =>
    new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(iso));

const changedShort = (flag: Flag): string => {
    if (flag.updated_at === null) {
        return '';
    }

    return flag.updated_by
        ? t('admin.features.changed_by', {
              date: date(flag.updated_at),
              name: flag.updated_by,
          })
        : date(flag.updated_at);
};

const changedFull = (flag: Flag): string => {
    if (flag.updated_at === null) {
        return '';
    }

    return flag.updated_by
        ? t('admin.features.changed_full', {
              date: date(flag.updated_at),
              name: flag.updated_by,
          })
        : t('admin.features.changed_at', { date: date(flag.updated_at) });
};

const toggle = (flag: Flag) => {
    if (!flag.activate_url) {
        return;
    }

    router.put(
        flag.activate_url,
        { is_active: !flag.is_active },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (switching.value = flag.key),
            onFinish: () => (switching.value = null),
        },
    );
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
    <Head :title="t('admin.features.title')" />

    <AdminShell>
        <main class="flex flex-col gap-4 sm:gap-6" data-testid="admin-features">
            <div class="flex items-start gap-4">
                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <h1
                        class="text-3xl font-extrabold tracking-tight lg:text-5xl"
                        data-testid="admin-features__title"
                    >
                        {{ t('admin.features.title') }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t('admin.features.lead') }}
                    </p>
                </div>
                <Button
                    v-if="canCreate"
                    as-child
                    data-testid="admin-features__create"
                >
                    <Link :href="urls.create">
                        <span class="sm:hidden">{{
                            t('admin.features.new_short')
                        }}</span>
                        <span class="max-sm:hidden">{{
                            t('admin.features.new')
                        }}</span>
                    </Link>
                </Button>
            </div>

            <div
                v-if="flags.length === 0"
                class="flex flex-col items-center gap-2 rounded-lg border bg-card px-8 py-12 text-center"
                data-testid="admin-features__empty"
            >
                <h2 class="text-[17px] leading-[22px] font-bold">
                    {{ t('admin.features.empty_title') }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ t('admin.features.empty_text') }}
                </p>
            </div>

            <template v-else>
                <!-- Desktop: a table. -->
                <div
                    class="overflow-hidden rounded-lg border bg-card max-lg:hidden"
                    data-testid="admin-features__table"
                >
                    <table class="w-full text-left text-sm">
                        <thead class="bg-background text-muted-foreground">
                            <tr>
                                <th class="w-52 p-4 font-medium">
                                    {{ t('admin.features.key') }}
                                </th>
                                <th class="p-4 font-medium">
                                    {{ t('admin.features.description') }}
                                </th>
                                <th class="w-36 p-4 font-medium">
                                    {{ t('admin.features.status') }}
                                </th>
                                <th class="w-52 p-4 font-medium">
                                    {{ t('admin.features.changed') }}
                                </th>
                                <th class="p-4" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="flag in flags"
                                :key="flag.key"
                                class="border-t"
                                :data-testid="`admin-features__row-${flag.key}`"
                            >
                                <td class="p-4 font-bold">{{ flag.key }}</td>
                                <td class="p-4 text-muted-foreground">
                                    {{ flag.description }}
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="flag.is_active"
                                            :aria-label="
                                                t(
                                                    'admin.features.toggle_label',
                                                    {
                                                        key: flag.key,
                                                    },
                                                )
                                            "
                                            :disabled="
                                                !flag.activate_url ||
                                                switching === flag.key
                                            "
                                            class="group inline-flex min-h-11 items-center outline-none disabled:cursor-wait"
                                            :data-testid="`admin-features__switch-${flag.key}`"
                                            @click="toggle(flag)"
                                        >
                                            <span
                                                class="flex h-6 w-11 items-center rounded-full p-0.5 transition-colors group-focus-visible:ring-[3px] group-focus-visible:ring-ring/50"
                                                :class="
                                                    flag.is_active
                                                        ? 'justify-end bg-primary'
                                                        : 'justify-start bg-border'
                                                "
                                            >
                                                <span
                                                    class="size-5 rounded-full bg-card shadow-sm"
                                                />
                                            </span>
                                        </button>
                                        {{
                                            flag.is_active
                                                ? t('admin.features.on')
                                                : t('admin.features.off')
                                        }}
                                    </div>
                                </td>
                                <td
                                    class="p-4 text-[13px] leading-[18px] text-muted-foreground"
                                >
                                    {{ changedShort(flag) }}
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-4">
                                        <Link
                                            v-if="flag.edit_url"
                                            :href="flag.edit_url"
                                            class="font-medium text-primary hover:underline"
                                            :data-testid="`admin-features__edit-${flag.key}`"
                                        >
                                            {{ t('admin.features.edit') }}
                                        </Link>
                                        <button
                                            v-if="flag.delete_url"
                                            type="button"
                                            class="font-medium text-primary hover:underline"
                                            :data-testid="`admin-features__delete-${flag.key}`"
                                            @click="pending = flag"
                                        >
                                            {{ t('admin.features.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tablet and mobile: cards. -->
                <ul
                    class="flex flex-col gap-4 lg:hidden"
                    data-testid="admin-features__cards"
                >
                    <li
                        v-for="flag in flags"
                        :key="flag.key"
                        class="flex flex-col gap-2 rounded-lg border bg-card p-4"
                        :data-testid="`admin-features__card-${flag.key}`"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-bold break-all">{{
                                flag.key
                            }}</span>
                            <div class="flex items-center gap-2 text-sm">
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="flag.is_active"
                                    :aria-label="
                                        t('admin.features.toggle_label', {
                                            key: flag.key,
                                        })
                                    "
                                    :disabled="
                                        !flag.activate_url ||
                                        switching === flag.key
                                    "
                                    class="group inline-flex min-h-11 items-center outline-none disabled:cursor-wait"
                                    @click="toggle(flag)"
                                >
                                    <span
                                        class="flex h-6 w-11 items-center rounded-full p-0.5 transition-colors group-focus-visible:ring-[3px] group-focus-visible:ring-ring/50"
                                        :class="
                                            flag.is_active
                                                ? 'justify-end bg-primary'
                                                : 'justify-start bg-border'
                                        "
                                    >
                                        <span
                                            class="size-5 rounded-full bg-card shadow-sm"
                                        />
                                    </span>
                                </button>
                                {{
                                    flag.is_active
                                        ? t('admin.features.on')
                                        : t('admin.features.off')
                                }}
                            </div>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ flag.description }}
                        </p>
                        <p
                            class="text-[13px] leading-[18px] text-muted-foreground"
                        >
                            {{ changedFull(flag) }}
                        </p>
                        <div class="flex gap-4 pt-2 text-sm">
                            <Link
                                v-if="flag.edit_url"
                                :href="flag.edit_url"
                                class="inline-flex min-h-11 items-center font-medium text-primary hover:underline"
                            >
                                {{ t('admin.features.edit') }}
                            </Link>
                            <button
                                v-if="flag.delete_url"
                                type="button"
                                class="inline-flex min-h-11 items-center font-medium text-primary hover:underline"
                                @click="pending = flag"
                            >
                                {{ t('admin.features.delete') }}
                            </button>
                        </div>
                    </li>
                </ul>
            </template>
        </main>

        <Dialog
            :open="pending !== null"
            @update:open="(open) => !open && (pending = null)"
        >
            <DialogContent data-testid="admin-features__dialog">
                <DialogHeader>
                    <DialogTitle>
                        {{ t('admin.features.delete_title') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            t('admin.features.delete_text', {
                                key: pending?.key,
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        type="button"
                        @click="pending = null"
                    >
                        {{ t('admin.features.cancel') }}
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        :disabled="processing"
                        data-testid="admin-features__confirm-delete"
                        @click="confirmDelete"
                    >
                        {{ t('admin.features.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminShell>
</template>
