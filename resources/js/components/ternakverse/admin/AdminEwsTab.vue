<script setup lang="ts">
import { ShieldAlert, RotateCcw, Bell } from 'lucide-vue-next';
import type { KecamatanData } from '../../../data/banyumasData';
import type { EwsParameters } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = defineProps<{
    ewsParams: EwsParameters;
    kecamatanList: KecamatanData[];
}>();

const emit = defineEmits<{
    (e: 'recalculate'): void;
    (e: 'broadcastAlert'): void;
}>();
</script>

<template>
    <div class="w-full space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                    Monitoring Kewaspadaan Dini Wabah (EWS)
                </h1>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                    Sistem Peringatan Dini Penyakit Mulut dan Kuku (PMK) &amp; Lumpy Skin Disease (LSD)
                </p>
            </div>
            <button
                type="button"
                @click="emit('broadcastAlert')"
                class="h-9 px-4 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2 self-start sm:self-auto transition-colors cursor-pointer"
            >
                <Bell class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                <span>Siarkan Peringatan Zona Merah</span>
            </button>
        </div>

        <!-- Parameter Konfigurasi Ambang Batas (PRD FR-SYS-02) -->
        <div class="w-full bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                <h3 class="font-display font-bold text-lg text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                    <ShieldAlert class="w-5 h-5 text-[#00D084]" />
                    <span>Konfigurasi Ambang Batas Zonasi Risiko (FR-SYS-02)</span>
                </h3>
                <button
                    type="button"
                    @click="emit('recalculate')"
                    class="h-9 px-4 rounded-lg bg-[#171B17] dark:bg-[#00D084] hover:bg-[#2A2E2A] dark:hover:bg-[#00B98B] text-white dark:text-[#090909] text-xs font-bold flex items-center gap-1.5 transition-colors self-start sm:self-auto cursor-pointer shadow-xs"
                >
                    <RotateCcw class="w-3.5 h-3.5" />
                    <span>Terapkan &amp; Rekalkulasi Batch</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs w-full">
                <div class="p-4 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl">
                    <span class="text-[#6E776D] dark:text-[#B3B3B3] font-semibold block mb-1">Jendela Hari Analisis</span>
                    <input
                        v-model.number="props.ewsParams.jendelaHari"
                        type="number"
                        class="w-full bg-transparent text-[#171B17] dark:text-[#F5F5F5] font-mono tabular-nums font-extrabold text-2xl outline-none"
                    />
                    <span class="text-[10px] text-[#6E776D] dark:text-[#B3B3B3] font-mono tabular-nums">Default: 14 hari terakhir</span>
                </div>

                <div class="p-4 bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/30 rounded-xl">
                    <span class="text-amber-700 dark:text-amber-400 font-semibold block mb-1">Ambang Kuning (Waspada)</span>
                    <input
                        v-model.number="props.ewsParams.ambangKuningMaks"
                        type="number"
                        class="w-full bg-transparent text-amber-700 dark:text-amber-400 font-mono tabular-nums font-extrabold text-2xl outline-none"
                    />
                    <span class="text-[10px] text-[#6E776D] dark:text-[#B3B3B3] font-mono tabular-nums">1 s.d. 5 kasus aktif</span>
                </div>

                <div class="p-4 bg-rose-500/5 dark:bg-rose-500/10 border border-rose-500/30 rounded-xl">
                    <span class="text-rose-700 dark:text-rose-400 font-semibold block mb-1">Ambang Merah (Tertular)</span>
                    <input
                        v-model.number="props.ewsParams.ambangMerahMin"
                        type="number"
                        class="w-full bg-transparent text-rose-700 dark:text-rose-400 font-mono tabular-nums font-extrabold text-2xl outline-none"
                    />
                    <span class="text-[10px] text-[#6E776D] dark:text-[#B3B3B3] font-mono tabular-nums">&gt; 5 kasus aktif</span>
                </div>
            </div>
        </div>

        <!-- Snapshot 27 Kecamatan Zonasi -->
        <div class="w-full bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 shadow-sm">
            <h3 class="font-display font-bold text-lg text-[#171B17] dark:text-[#F5F5F5] mb-4">
                Snapshot Zonasi 27 Kecamatan Banyumas (Tahun 2026)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5 text-xs w-full">
                <div
                    v-for="kec in props.kecamatanList"
                    :key="kec.id"
                    class="p-2.5 rounded-lg border flex flex-col justify-between gap-1 transition-colors"
                    :class="
                        kec.zona === 'bahaya'
                            ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-300'
                            : kec.zona === 'waspada'
                              ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-700 dark:text-amber-300'
                              : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-300'
                    "
                >
                    <div class="flex items-center justify-between gap-1">
                        <span class="font-semibold truncate">{{ kec.nama }}</span>
                        <span class="text-[9px] uppercase font-bold tracking-wider opacity-85">{{ kec.zona }}</span>
                    </div>
                    <div class="text-[10px] font-mono tabular-nums opacity-75">
                        PMK: {{ kec.pmk }} • LSD: {{ kec.lsd }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
