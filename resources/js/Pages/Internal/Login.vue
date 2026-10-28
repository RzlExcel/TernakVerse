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

    <div class="min-h-screen bg-[#0B0F17] text-slate-100 flex flex-col justify-center items-center p-4 font-ui select-none relative overflow-hidden">
        <!-- Ambient Background Glows -->
        <div class="absolute -top-32 -left-32 w-80 h-80 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Logo & Header (BBPTUHPT Style) -->
        <div class="text-center mb-8 relative z-10">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-blue-600 items-center justify-center text-white font-black text-2xl shadow-lg shadow-blue-600/30 mb-3">
                T
            </div>
            <h1 class="font-display font-black text-2xl text-white tracking-tight">
                TERNAKVERSE INTERNAL
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Kabupaten Banyumas • Balai Besar Pembibitan Ternak Unggul
            </p>
        </div>

        <!-- Card Login (BBPTUHPT Solid High-Contrast Slate) -->
        <div class="w-full max-w-md bg-[#111827] border border-[#1E293B] rounded-2xl p-6 sm:p-8 shadow-2xl relative z-10">
            <h2 class="font-display font-bold text-lg text-white mb-1">Masuk Sesi Petugas</h2>
            <p class="text-xs text-slate-400 mb-5">Pilih peran terotorisasi untuk mengakses modul operasional.</p>

            <!-- Pilih Peran Sesi -->
            <div class="grid grid-cols-2 gap-2 mb-5 p-1 bg-[#0B0F17] rounded-xl border border-[#1E293B]">
                <button
                    type="button"
                    @click="selectedRole = 'dinas'; username = 'drh.sulistyo@banyumaskab.go.id'"
                    class="py-2 px-3 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                    :class="selectedRole === 'dinas' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-white'"
                >
                    <UserCheck class="w-3.5 h-3.5" />
                    <span>Petugas Dinas</span>
                </button>
                <button
                    type="button"
                    @click="selectedRole = 'admin'; username = 'admin.diskanak@banyumaskab.go.id'"
                    class="py-2 px-3 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                    :class="selectedRole === 'admin' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-white'"
                >
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>Super Admin</span>
                </button>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-4 text-xs">
                <div>
                    <label class="block font-medium text-slate-300 mb-1.5">Identitas Pegawai / NIP</label>
                    <div class="relative">
                        <User class="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                        <input
                            v-model="username"
                            type="text"
                            required
                            class="w-full h-10 bg-[#0B0F17] border border-[#1E293B] rounded-lg pl-9 pr-3 text-xs text-white placeholder-slate-500 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all font-mono"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-300 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <Lock class="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                        <input
                            v-model="password"
                            type="password"
                            required
                            class="w-full h-10 bg-[#0B0F17] border border-[#1E293B] rounded-lg pl-9 pr-3 text-xs text-white placeholder-slate-500 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all font-mono"
                        />
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span>{{ isLoading ? 'Memverifikasi...' : 'Masuk ke Panel Operasional' }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-[#1E293B] text-center text-[11px] text-slate-500">
                Akses diawasi oleh sistem enkripsi RBAC Pemerintah Kabupaten Banyumas.
            </div>
        </div>

        <!-- Link Kembali ke Publik -->
        <a href="/" class="mt-6 text-xs text-slate-400 hover:text-blue-400 transition-colors flex items-center gap-1 font-medium relative z-10">
            &larr; Kembali ke Portal WebGIS Publik
        </a>
    </div>
</template>
