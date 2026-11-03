<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';
import { ShieldCheck, UserCheck, Lock, User, ArrowRight, AlertCircle } from 'lucide-vue-next';

const selectedRole = ref<'dinas' | 'admin'>('dinas');
const username = ref('drh.sulistyo@banyumaskab.go.id');
const password = ref('password');
const isLoading = ref(false);
const errorMessage = ref('');

function selectRole(role: 'dinas' | 'admin'): void {
    selectedRole.value = role;
    errorMessage.value = '';
    if (role === 'dinas') {
        username.value = 'drh.sulistyo@banyumaskab.go.id';
    } else {
        username.value = 'admin.diskanak@banyumaskab.go.id';
    }
}

function handleLogin(): void {
    isLoading.value = true;
    errorMessage.value = '';
    router.post('/internal/login', {
        role: selectedRole.value,
        email: username.value,
        password: password.value,
    }, {
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            errorMessage.value = (errors.email || errors.password || errors.role || 'Kredensial tidak valid. Silakan coba lagi.') as string;
        },
    });
}
</script>

<template>
    <Head title="Login Portal Internal - TernakVerse Banyumas" />

    <div class="min-h-screen bg-[#FFFFFF] dark:bg-[#090909] text-[#171B17] dark:text-[#F5F5F5] flex flex-col justify-center items-center p-4 font-ui select-none relative overflow-hidden transition-colors">
        <!-- Floating ThemeToggle Top Right -->
        <div class="absolute top-4 right-4 z-20">
            <ThemeToggle size="sm" compact />
        </div>

        <!-- Logo & Header (AgriTech Emerald & Slate Style) -->
        <div class="text-center mb-8 relative z-10">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-emerald-600 items-center justify-center text-white font-black text-2xl shadow-lg shadow-emerald-950/50 border border-emerald-500/30 mb-3">
                T
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-[#00D084] text-[11px] font-semibold tracking-wide uppercase mb-2 block mx-auto w-fit">
                Portal Otorisasi Aparatur
            </div>
            <h1 class="font-display font-black text-2xl text-[#171B17] dark:text-white tracking-tight">
                TERNAKVERSE INTERNAL
            </h1>
            <p class="text-xs text-[#444C43] dark:text-[#B3B3B3] mt-1">
                Dinas Perikanan dan Peternakan Kabupaten Banyumas
            </p>
        </div>

        <!-- Card Login (AgriTech High-Contrast Pure Obsidian & Slate) -->
        <div class="w-full max-w-md bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 sm:p-8 shadow-2xl relative z-10 transition-colors">
            <h2 class="font-display font-bold text-lg text-[#171B17] dark:text-white mb-1">Masuk Sesi Petugas</h2>
            <p class="text-xs text-[#444C43] dark:text-[#B3B3B3] mb-5">Pilih peran terotorisasi untuk mengakses modul operasional geospasial dan komoditas.</p>

            <!-- Pilih Peran Sesi -->
            <div class="grid grid-cols-2 gap-2 mb-5 p-1 bg-white dark:bg-[#181818] rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                <button
                    type="button"
                    @click="selectRole('dinas')"
                    class="py-2 px-3 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                    :class="selectedRole === 'dinas' ? 'bg-[#171B17] text-white font-bold shadow-xs dark:bg-[#181818] dark:text-[#00D084]' : 'text-[#444C43] hover:text-[#171B17] hover:bg-[#EDEFEB] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]'"
                >
                    <UserCheck class="w-3.5 h-3.5" />
                    <span>Petugas Dinas</span>
                </button>
                <button
                    type="button"
                    @click="selectRole('admin')"
                    class="py-2 px-3 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                    :class="selectedRole === 'admin' ? 'bg-[#171B17] text-white font-bold shadow-xs dark:bg-[#181818] dark:text-[#00D084]' : 'text-[#444C43] hover:text-[#171B17] hover:bg-[#EDEFEB] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]'"
                >
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>Super Admin</span>
                </button>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMessage" class="mb-4 p-3 rounded-xl bg-rose-500/10 dark:bg-rose-950/60 border border-rose-500/30 dark:border-rose-800/80 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                <AlertCircle class="w-4 h-4 shrink-0 text-rose-500 dark:text-rose-400" />
                <span>{{ errorMessage }}</span>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-4 text-xs">
                <div>
                    <label for="internal-username" class="block font-medium text-[#171B17] dark:text-[#F5F5F5] mb-1.5">Identitas Pegawai / NIP</label>
                    <div class="relative">
                        <User class="w-4 h-4 absolute left-3 top-3 text-slate-400 dark:text-[#858585]" />
                        <input
                            id="internal-username"
                            v-model="username"
                            type="text"
                            required
                            class="w-full h-10 bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg pl-9 pr-3 text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder-slate-400 dark:placeholder-[#858585] outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all font-mono"
                        />
                    </div>
                </div>

                <div>
                    <label for="internal-password" class="block font-medium text-[#171B17] dark:text-[#F5F5F5] mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <Lock class="w-4 h-4 absolute left-3 top-3 text-slate-400 dark:text-[#858585]" />
                        <input
                            id="internal-password"
                            v-model="password"
                            type="password"
                            required
                            class="w-full h-10 bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg pl-9 pr-3 text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder-slate-400 dark:placeholder-[#858585] outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all font-mono"
                        />
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full h-10 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none"
                    >
                        <span>{{ isLoading ? 'Memverifikasi...' : 'Masuk ke Panel Operasional' }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E] text-center text-[11px] text-[#444C43] dark:text-[#858585]">
                Akses diawasi oleh sistem enkripsi RBAC Pemerintah Kabupaten Banyumas.
            </div>
        </div>

        <!-- Link Kembali ke Publik -->
        <a href="/" class="mt-6 text-xs text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#00D084] transition-colors flex items-center gap-1 font-medium relative z-10">
            &larr; Kembali ke Portal WebGIS Publik
        </a>
    </div>
</template>
