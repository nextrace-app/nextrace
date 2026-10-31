<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

// The frame of every admin page: a sidebar from lg up, a top bar with a menu
// below it, as in the public header.
const { t } = useI18n();
const page = usePage();
const menuOpen = ref(false);

watch(
    () => page.url,
    () => {
        menuOpen.value = false;
    },
);
</script>

<template>
    <div
        class="min-h-screen bg-background text-foreground lg:flex"
        data-testid="admin-shell"
    >
        <aside
            class="hidden w-64 shrink-0 border-r bg-card lg:block"
            data-testid="admin-shell__sidebar"
        >
            <div class="sticky top-0 flex h-screen flex-col gap-6 p-4 py-6">
                <AdminBrand />
                <AdminNav />
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header
                class="flex h-[70px] items-center justify-between border-b bg-card px-4 sm:px-6 lg:hidden"
                data-testid="admin-shell__topbar"
            >
                <AdminBrand />
                <Sheet v-model:open="menuOpen">
                    <SheetTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-11"
                            :aria-label="t('nav.menu')"
                            data-testid="admin-shell__menu-toggle"
                        >
                            <Menu class="size-6" aria-hidden="true" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        class="w-full gap-0 overflow-y-auto p-0 sm:max-w-sm"
                        data-testid="admin-shell__menu"
                    >
                        <SheetHeader class="border-b px-4 py-3">
                            <SheetTitle><AdminBrand /></SheetTitle>
                            <SheetDescription class="sr-only">
                                {{ t('admin.nav.label') }}
                            </SheetDescription>
                        </SheetHeader>
                        <div class="flex flex-1 flex-col p-4">
                            <AdminNav />
                        </div>
                    </SheetContent>
                </Sheet>
            </header>

            <div class="p-4 sm:p-6 lg:p-12">
                <slot />
            </div>
        </div>
    </div>
</template>
