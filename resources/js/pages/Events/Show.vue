<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, MapPin, Route, Users } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import type { Event } from '@/types';

defineProps<{
    event: Event;
    isFavorite: boolean;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
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
    <Head :title="event.name" />

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <header
            class="mx-auto mb-8 flex max-w-4xl items-center justify-between"
        >
            <h1 class="text-lg font-semibold">Events</h1>
            <nav class="flex items-center gap-4 text-sm">
                <Link
                    v-if="user"
                    :href="dashboard()"
                    class="text-muted-foreground hover:text-foreground"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <div class="mx-auto max-w-4xl">
            <Link
                :href="eventsIndex()"
                class="mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                Back to events
            </Link>

            <Card class="overflow-hidden pt-0">
                <div
                    class="relative aspect-video overflow-hidden sm:aspect-[21/9]"
                >
                    <img
                        :src="event.image"
                        :alt="event.name"
                        class="h-full w-full object-cover"
                    />
                    <Badge class="absolute top-4 right-4">{{
                        event.category
                    }}</Badge>
                    <FavoriteButton
                        :event-id="event.id"
                        :favorited="isFavorite"
                        class="absolute top-4 left-4"
                    />
                </div>

                <CardContent class="flex flex-col gap-6">
                    <div>
                        <h2 class="text-2xl font-semibold">{{ event.name }}</h2>
                        <p class="mt-2 text-muted-foreground">
                            {{ event.description }}
                        </p>
                    </div>

                    <div
                        class="grid gap-4 border-t pt-6 text-sm sm:grid-cols-2"
                    >
                        <div class="flex items-center gap-2">
                            <Calendar
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span>{{ formatDate(event.date) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span>{{ event.location }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Route
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span>{{ event.distance }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Users
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span
                                >{{
                                    formatParticipants(event.participants)
                                }}
                                participants</span
                            >
                        </div>
                    </div>

                    <Button class="w-full sm:w-auto"
                        >Register for this event</Button
                    >
                </CardContent>
            </Card>
        </div>
    </div>
</template>
