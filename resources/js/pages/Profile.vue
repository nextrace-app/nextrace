<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Mail, TriangleAlert, User } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useLocale } from '@/composables/useLocale';
import { edit as editProfile } from '@/routes/profile';

const { t } = useI18n();
const { locale, localeRoute } = useLocale();
const page = usePage();

const user = computed(() => page.props.auth.user);

const memberSince = computed(() =>
    new Date(user.value.created_at).toLocaleDateString(locale.value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }),
);
</script>

<template>
    <Head :title="t('profile.title')" />

    <div class="min-h-screen bg-background p-6 lg:p-8">
        <DefaultHeader />

        <div class="mx-auto flex max-w-4xl flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <SettingsNav />
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <h2 class="mb-6 text-2xl font-semibold">
                    {{ t('profile.title') }}
                </h2>

                <Card>
                    <CardContent class="flex flex-col gap-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex size-14 items-center justify-center rounded-full bg-muted"
                            >
                                <User class="size-7 text-muted-foreground" />
                            </div>
                            <div>
                                <p class="text-lg font-medium">
                                    {{ user.name }}
                                </p>
                                <p
                                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                >
                                    <Mail class="size-4" />
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>

                        <dl
                            class="grid gap-4 border-t pt-6 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <dt class="text-muted-foreground">
                                    {{ t('profile.member_since') }}
                                </dt>
                                <dd class="mt-1 font-medium">
                                    {{ memberSince }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">
                                    {{ t('profile.email_status') }}
                                </dt>
                                <dd
                                    class="mt-1 flex items-center gap-1.5 font-medium"
                                >
                                    <template v-if="user.email_verified_at">
                                        <BadgeCheck
                                            class="size-4 text-green-600"
                                        />
                                        {{ t('profile.verified') }}
                                    </template>
                                    <template v-else>
                                        <TriangleAlert
                                            class="size-4 text-amber-600"
                                        />
                                        {{ t('profile.unverified') }}
                                    </template>
                                </dd>
                            </div>
                        </dl>

                        <div class="border-t pt-6">
                            <Button as-child variant="outline">
                                <Link :href="localeRoute(editProfile())">
                                    {{ t('profile.edit') }}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
