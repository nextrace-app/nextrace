<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import { destroy } from '@/routes/profile';

// The copy the /profile page renders; the settings section has DeleteUser.
const { t } = useI18n();
const { localeRoute } = useLocale();
const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6" data-testid="profile-page-delete-user">
        <Heading
            variant="small"
            :title="t('account.delete.title')"
            :description="t('account.delete.description')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
            data-testid="profile-page-delete-user__warning"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('account.delete.warning') }}</p>
                <p class="text-sm">
                    {{ t('account.delete.warning_text') }}
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-testid="profile-page-delete-user__trigger"
                        >{{ t('account.delete.title') }}</Button
                    >
                </DialogTrigger>
                <DialogContent data-testid="profile-page-delete-user__dialog">
                    <Form
                        v-bind="localeRoute(destroy.form())"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        data-testid="profile-page-delete-user__form"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{
                                t('account.delete.confirm_title')
                            }}</DialogTitle>
                            <DialogDescription>
                                {{ t('account.delete.confirm_text') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">{{
                                t('form.password')
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                data-testid="profile-page-delete-user__password"
                                ref="passwordInput"
                                :placeholder="t('form.password')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    data-testid="profile-page-delete-user__cancel"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('form.cancel') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-testid="profile-page-delete-user__confirm"
                            >
                                {{ t('account.delete.title') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
