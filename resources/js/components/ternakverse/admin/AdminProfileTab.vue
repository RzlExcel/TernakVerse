<script setup lang="ts">
import { LogOut, Check } from 'lucide-vue-next';
import type { AdminProfileSettings } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = defineProps<{
    profileSettings: AdminProfileSettings;
}>();

const emit = defineEmits<{
    (e: 'saveSettings'): void;
    (e: 'logout'): void;
}>();
</script>

<template>
    <div class="w-full space-y-6">
        <div>
            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                Pengaturan &amp; Profil Admin
            </h1>
            <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                Akun Super Administrator Sistem TERNAKVERSE
            </p>
        </div>

        <div class="w-full bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 md:p-8 space-y-6 shadow-sm">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 border border-emerald-500/40 flex items-center justify-center font-display font-black text-2xl text-emerald-600 dark:text-[#00D084] shadow-md shadow-emerald-950/20">
                    S
                </div>
                <div>
                    <h3 class="font-display font-extrabold text-2xl text-[#171B17] dark:text-[#F5F5F5]">{{ props.profileSettings.nama }}</h3>
                    <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] font-mono tabular-nums mt-0.5">NIP: {{ props.profileSettings.nip }}</p>
                    <span class="inline-block mt-1 px-3 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-[#00D084] text-xs font-bold font-mono border border-emerald-500/30">
                        {{ props.profileSettings.role }}
                    </span>
                </div>
            </div>

            <div class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-xs">
                <div class="py-3.5 flex justify-between">
                    <span class="text-[#6E776D] dark:text-[#B3B3B3] font-semibold">Email Dinas:</span>
                    <span class="text-[#171B17] dark:text-[#F5F5F5] font-semibold font-mono">{{ props.profileSettings.email }}</span>
                </div>
                <div class="py-3.5 flex justify-between">
                    <span class="text-[#6E776D] dark:text-[#B3B3B3] font-semibold">Unit Kerja:</span>
                    <span class="text-[#171B17] dark:text-[#F5F5F5] font-medium">{{ props.profileSettings.unitKerja }}</span>
                </div>
                <div class="py-3.5 flex justify-between">
                    <span class="text-[#6E776D] dark:text-[#B3B3B3] font-semibold">Instansi:</span>
                    <span class="text-[#171B17] dark:text-[#F5F5F5] font-medium">{{ props.profileSettings.instansi }}</span>
                </div>
                <div class="py-3.5 flex justify-between">
                    <span class="text-[#6E776D] dark:text-[#B3B3B3] font-semibold">Status Akun:</span>
                    <span class="text-emerald-600 dark:text-[#00D084] font-bold">{{ props.profileSettings.statusAkun }}</span>
                </div>
            </div>

            <!-- Preferensi Notifikasi Operasional -->
            <div class="pt-2 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#6E776D] dark:text-[#B3B3B3]">
                    Preferensi Notifikasi &amp; Sinkronisasi
                </h4>
                <label class="flex items-center justify-between p-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs cursor-pointer hover:border-[#00D084]/40 transition-colors">
                    <span class="text-[#171B17] dark:text-[#F5F5F5] font-medium">Peringatan Dini EWS via Email Dinas</span>
                    <input
                        v-model="props.profileSettings.notifEwsEmail"
                        type="checkbox"
                        class="w-4 h-4 rounded bg-white dark:bg-[#111111] border-[#E0E4DD] dark:border-[#1E1E1E] text-[#00D084] focus:ring-0 focus:ring-offset-0"
                    />
                </label>
                <label class="flex items-center justify-between p-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs cursor-pointer hover:border-[#00D084]/40 transition-colors">
                    <span class="text-[#171B17] dark:text-[#F5F5F5] font-medium">Siaran Darurat Kasus PMK/LSD ke Puskeswan</span>
                    <input
                        v-model="props.profileSettings.notifWhatsappDarurat"
                        type="checkbox"
                        class="w-4 h-4 rounded bg-white dark:bg-[#111111] border-[#E0E4DD] dark:border-[#1E1E1E] text-[#00D084] focus:ring-0 focus:ring-offset-0"
                    />
                </label>
            </div>

            <div class="pt-2 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    @click="emit('saveSettings')"
                    class="h-10 px-5 rounded-lg bg-[#171B17] dark:bg-[#00D084] hover:bg-[#2A2E2A] dark:hover:bg-[#00B98B] text-white dark:text-[#090909] font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer shadow-xs"
                >
                    <Check class="w-4 h-4 stroke-[2.5]" />
                    <span>Simpan Preferensi</span>
                </button>
                <button
                    type="button"
                    @click="emit('logout')"
                    class="h-10 px-6 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 hover:bg-rose-500/20 font-semibold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                >
                    <LogOut class="w-4 h-4" />
                    <span>Sign Out Dari Sesi Ini</span>
                </button>
            </div>
        </div>
    </div>
</template>
