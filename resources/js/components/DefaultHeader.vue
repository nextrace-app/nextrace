<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <header class="mx-auto pb-8 flex max-w-6xl items-center justify-between">
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
</template>
