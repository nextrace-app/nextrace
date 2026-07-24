<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Calendar, MapPin, Users } from '@lucide/vue';
import { computed } from 'vue';
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
import { dashboard, login, register } from '@/routes';
import { show as eventsShow } from '@/routes/events';
import type { Event } from '@/types';

defineProps<{
    events: Event[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function formatParticipants(count: number): string {
    return new Intl.NumberFormat('en-US').format(count);
}
</script>

<template>
    <Head title="Events" />

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <header class="mx-auto mb-8 flex max-w-6xl items-center justify-between">
            <h1 class="text-lg font-semibold">Events</h1>
            <nav class="flex items-center gap-4 text-sm">
                <Link v-if="user" :href="dashboard()" class="text-muted-foreground hover:text-foreground">
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="login()" class="text-muted-foreground hover:text-foreground">
                        Log in
                    </Link>
                    <Link :href="register()" class="text-muted-foreground hover:text-foreground">
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <div class="mx-auto grid max-w-6xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="event in events" :key="event.id" class="overflow-hidden pt-0">
                <div class="relative aspect-video overflow-hidden">
                    <img
                        :src="event.image"
                        :alt="event.name"
                        class="h-full w-full object-cover"
                    />
                    <Badge class="absolute top-3 right-3">{{ event.category }}</Badge>
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
                        <span>{{ formatParticipants(event.participants) }} participants &middot; {{ event.distance }}</span>
                    </div>
                </CardContent>

                <CardFooter>
                    <Button as-child class="w-full">
                        <Link :href="eventsShow(event.id)">View details</Link>
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
