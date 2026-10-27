<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ShieldCheck, UserCheck, Lock, User, ArrowRight, Compass } from 'lucide-vue-next';

const selectedRole = ref<'dinas' | 'admin'>('dinas');
const username = ref('petugas.dinas@banyumaskab.go.id');
const password = ref('••••••••');
const isLoading = ref(false);

function handleLogin() {
    isLoading.value = true;
    setTimeout(() => {
        isLoading.value = false;
        if (selectedRole.value === 'dinas') {
            router.visit('/internal/dinas');
        } else {
            router.visit('/internal/admin');
        }
    }, 600);
}
</script>

<template>
    <Head title="Login Portal Internal - Ternakverse Banyumas" />

    <div class="min-h-screen bg-[#0A0D0E] text-[#F4F7F5] flex flex-col justify-center items-center p-4 font-ui select-none">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-14 h-14 rounded-20 bg-[#10171A] border-2 border-[#19F5D4] items-center justify-center text-[#19F5D4] shadow-[4px_4px_0_#19F5D4] mb-3">
                <Compass class="w-8 h-8 stroke-[2.5]" />
            </div>
            <h1 class="font-display font-extrabold text-3xl text-[#F4F7F5] tracking-tight">
                TERNAKVERSE INTERNAL
            </h1>
            <p class="text-xs text-[#8FA1A0] mt-1">
                Portal Terbatas Dinas Perikanan dan Peternakan Kab. Banyumas
            </p>
        </div>

        <!-- Card Login (Radius 24px, Border 2px, Hard Shadow) -->
        <div class="w-full max-w-md bg-[#10171A] border-2 border-[#243034] rounded-24 p-6 sm:p-8 shadow-[4px_4px_0_#19F5D4] relative">
            <h2 class="font-display font-bold text-xl text-[#F4F7F5] mb-1">Masuk Sesi Petugas</h2>
            <p class="text-xs text-[#8FA1A0] mb-5">Pilih peran terotorisasi untuk mengakses modul internal.</p>

            <!-- Pilih Peran Sesi -->
            <div class="grid grid-cols-2 gap-2 mb-5 p-1 bg-[#0A0D0E] rounded-full border border-[#243034]">
                <button
                    type="button"
                    @click="selectedRole = 'dinas'; username = 'drh.sulistyo@banyumaskab.go.id'"
                    class="py-2 px-3 text-xs font-bold rounded-full transition-all flex items-center justify-center gap-1.5"
                    :class="selectedRole === 'dinas' ? 'bg-[#19F5D4] text-[#04110A] shadow-[2px_2px_0_#243034]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                >
                    <UserCheck class="w-3.5 h-3.5" />
                    <span>Petugas Dinas</span>
                </button>
                <button
                    type="button"
                    @click="selectedRole = 'admin'; username = 'admin.diskanak@banyumaskab.go.id'"
                    class="py-2 px-3 text-xs font-bold rounded-full transition-all flex items-center justify-center gap-1.5"
                    :class="selectedRole === 'admin' ? 'bg-[#39FF14] text-[#04110A] shadow-[2px_2px_0_#243034]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                >
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>Super Admin</span>
                </button>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-[#F4F7F5] mb-1">Identitas Pegawai / NIP</label>
                    <div class="relative">
                        <User class="w-4 h-4 absolute left-3 top-3.5 text-[#8FA1A0]" />
                        <input
                            v-model="username"
                            type="text"
                            required
                            class="input-tactile w-full pl-9 text-xs"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-[#F4F7F5] mb-1">Kata Sandi</label>
                    <div class="relative">
                        <Lock class="w-4 h-4 absolute left-3 top-3.5 text-[#8FA1A0]" />
                        <input
                            v-model="password"
                            type="password"
                            required
                            class="input-tactile w-full pl-9 text-xs font-mono"
                        />
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="btn-primary-tactile w-full gap-2 text-xs"
                    >
                        <span>{{ isLoading ? 'Memverifikasi...' : 'Masuk ke Portal Internal' }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-[#243034] text-center text-[11px] text-[#8FA1A0]">
                Akses diawasi oleh sistem enkripsi RBAC Pemerintah Kabupaten Banyumas.
            </div>
        </div>

        <!-- Link Kembali ke Publik -->
        <a href="/" class="mt-6 text-xs text-[#8FA1A0] hover:text-[#19F5D4] underline flex items-center gap-1">
            &larr; Kembali ke Peta Portal Publik
        </a>
    </div>
</template>
