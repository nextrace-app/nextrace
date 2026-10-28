<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();
</script>

<template>
    <Breadcrumb data-testid="breadcrumbs">
        <BreadcrumbList data-testid="breadcrumbs__list">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem data-testid="breadcrumbs__item">
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage data-testid="breadcrumbs__current">{{
                            item.title
                        }}</BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link
                                :href="item.href"
                                data-testid="breadcrumbs__link"
                                >{{ item.title }}</Link
                            >
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
