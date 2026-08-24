<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, MapPin, Users } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { show as eventsShow } from '@/routes/events';
import type { Event } from '@/types';

defineProps<{
    event: Event;
    favorited: boolean;
}>();

const { t } = useI18n();
const { locale, localeRoute } = useLocale();

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(locale.value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function formatParticipants(count: number): string {
    return new Intl.NumberFormat(locale.value).format(count);
}
</script>

<template>
    <Card class="overflow-hidden pt-0">
        <div class="relative aspect-video overflow-hidden">
            <img
                :src="event.image"
                :alt="event.name"
                class="h-full w-full object-cover"
            />
            <Badge class="absolute top-3 right-3">{{ event.category }}</Badge>
            <FavoriteButton
                :event-id="event.id"
                :favorited="favorited"
                class="absolute top-3 left-3"
            />
        </div>

        <CardHeader>
            <CardTitle>{{ event.name }}</CardTitle>
            <CardDescription>{{ event.description }}</CardDescription>
        </CardHeader>

        <CardContent class="flex flex-col gap-2 text-sm text-muted-foreground">
            <div class="flex items-center gap-2">
                <Calendar class="size-4 shrink-0" />
                <span>{{ formatDate(event.date) }}</span>
            </div>
            <div class="flex items-center gap-2">
                <MapPin class="size-4 shrink-0" />
                <span>{{ event.location }}</span>
            </div>
            <div class="flex items-center gap-2">
                <Users class="size-4 shrink-0" />
                <span>
                    {{ formatParticipants(event.participants) }}
                    {{ t('events.participants') }} &middot; {{ event.distance }}
                </span>
            </div>
        </CardContent>

        <CardFooter>
            <Button as-child class="w-full">
                <Link :href="localeRoute(eventsShow(event.id))">
                    {{ t('events.details') }}
                </Link>
            </Button>
        </CardFooter>
    </Card>
</template>
