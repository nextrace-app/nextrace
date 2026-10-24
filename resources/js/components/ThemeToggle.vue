<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

const { t } = useI18n();
const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', Icon: Sun },
    { value: 'dark', Icon: Moon },
    { value: 'system', Icon: Monitor },
] as const;
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="secondary"
                size="icon"
                class="size-10"
                :aria-label="t('nav.theme.label')"
                :title="t('nav.theme.label')"
                data-test="theme-toggle"
            >
                <Moon
                    v-if="resolvedAppearance === 'dark'"
                    class="size-[18px]"
                    aria-hidden="true"
                />
                <Sun v-else class="size-[18px]" aria-hidden="true" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="
                    (value) => updateAppearance(value as Appearance)
                "
            >
                <DropdownMenuRadioItem
                    v-for="{ value, Icon } in options"
                    :key="value"
                    :value="value"
                    :data-test="`theme-${value}`"
                >
                    <component :is="Icon" class="size-4" aria-hidden="true" />
                    {{ t(`nav.theme.${value}`) }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
