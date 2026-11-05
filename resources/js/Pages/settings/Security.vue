<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/security';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Keamanan & 2FA',
        href: edit().url,
    },
];

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPasswordUpdate = (): void => {
    form.put(SecurityController.update.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () =>
            form.reset('password', 'password_confirmation', 'current_password'),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Keamanan & 2FA" />

        <h1 class="sr-only">Keamanan &amp; 2FA</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Perbarui Kata Sandi"
                    description="Kelola keamanan autentikasi dan pembaruan kata sandi akun Anda."
                />

                <form
                    @submit.prevent="submitPasswordUpdate"
                    class="space-y-6"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Kata Sandi Saat Ini</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            :value="form.current_password"
                            @input="(e: Event) => (form.current_password = (e.target as HTMLInputElement).value)"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi saat ini"
                        />
                        <InputError :message="form.errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Kata Sandi Baru</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            :value="form.password"
                            @input="(e: Event) => (form.password = (e.target as HTMLInputElement).value)"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Masukkan kata sandi baru"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmasi Kata Sandi</Label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            :value="form.password_confirmation"
                            @input="(e: Event) => (form.password_confirmation = (e.target as HTMLInputElement).value)"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Konfirmasi kata sandi baru"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="form.processing"
                            data-test="update-password-button"
                        >
                            Simpan
                        </Button>
                    </div>
                </form>
            </div>

            <ManageTwoFactor
                :canManageTwoFactor="canManageTwoFactor"
                :requiresConfirmation="requiresConfirmation"
                :twoFactorEnabled="twoFactorEnabled"
            />

            <ManagePasskeys
                :canManagePasskeys="canManagePasskeys"
                :passkeys="passkeys"
            />
        </SettingsLayout>
    </AppLayout>
</template>
