<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    Check,
    AlertTriangle,
    ShieldAlert,
    Building2,
    Package,
} from 'lucide-vue-next';
import type { KecamatanData } from '../../../../data/banyumasData';
import type { RphFacility as RphItem } from '../../../../composables/ternakverse/admin/useAdminPortal';
import { KECAMATAN_LIST, formatAngka } from '../../../../data/banyumasData';

export interface KpiCardItem {
    id: 'card-populasi' | 'card-produksi' | 'card-penyakit' | 'card-fasilitas' | string;
    title: string;
    value: string | number;
    unit?: string;
    trend: string;
    trendSuffix: string;
    trendColorClass: string;
    iconBoxClass: string;
    iconType: 'populasi' | 'produksi' | 'penyakit' | 'fasilitas' | 'cow' | 'farmer' | 'pmk' | 'lsd' | 'rph';
    sparklineColor?: string;
    sparklineCurve?: string;
    sparklineArea?: string;
}

/**
 * Kontrak Kompatibilitas Analisis KPI Dashboard Admin:
 * - 'Total Populasi Sapi Potong' (kini mencakup Total Populasi Ternak)
 * - 'Total Peternak' (kini mencakup Total Produksi Pangan)
 * - 'Kasus Aktif PMK' (kini disatukan dalam Kesehatan Hewan & Kasus Wabah)
 * - 'Kasus Aktif LSD' (kini disatukan dalam Kesehatan Hewan & Kasus Wabah)
 * - 'Fasilitas RPH' (kini mencakup Fasilitas Peternakan: RPH & Poskeswan)
 */
interface Props {
    cards?: KpiCardItem[];
    selectedCardId?: string;
    kecamatanList?: KecamatanData[];
    rphList?: RphItem[];
    selectedYear?: number | 'all';
    dateRangeLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    selectedCardId: 'card-populasi',
    kecamatanList: () => [],
    rphList: () => [],
    selectedYear: 2026,
    dateRangeLabel: 'Tahun 2026 (Terbaru)',
});

const emit = defineEmits<{
    (e: 'selectCard', id: string): void;
}>();

const currentSelectedId = ref(props.selectedCardId);

watch(
    () => props.selectedCardId,
    (newVal) => {
        if (newVal) currentSelectedId.value = newVal;
    }
);

function handleCardClick(id: string): void {
    currentSelectedId.value = id;
    emit('selectCard', id);
}

const activeList = computed<KecamatanData[]>(() => {
    return props.kecamatanList && props.kecamatanList.length > 0
        ? props.kecamatanList
        : KECAMATAN_LIST;
});

// Card 1: Total Populasi
const totalPopulasi = computed(() => {
    return activeList.value.reduce((acc, k) => {
        const itemTotal = k.populasi_total ?? (
            (k.sapi_potong || 0) +
            (k.sapi_perah || 0) +
            (k.kambing || 0) +
            (k.domba || 0) +
            (k.ayam_potong || 0) +
            (k.ayam_petelur || 0)
        );
        return acc + itemTotal;
    }, 0);
});

// Card 2: Produksi Pangan
const totalDaging = computed(() => {
    return activeList.value.reduce((acc, k) => {
        return acc + (k.daging_sapi || 0) + (k.daging_ayam || 0) + (k.daging_kambing || 0) + (k.daging_domba || 0);
    }, 0);
});

const totalSusu = computed(() => {
    return activeList.value.reduce((acc, k) => {
        return acc + (k.susu_sapi || 0) + (k.susu_kambing || 0) + (k.susu_domba || 0);
    }, 0);
});

const produksiFormatted = computed(() => {
    const dagingJt = totalDaging.value / 1_000_000;
    return `${dagingJt.toFixed(2).replace('.', ',')} jt`;
});

// Card 3: Penyakit & Kasus Wabah
const totalPmk = computed(() => {
    return activeList.value.reduce((acc, k) => acc + (k.pmk || 0), 0);
});

const totalLsd = computed(() => {
    return activeList.value.reduce((acc, k) => acc + (k.lsd || 0), 0);
});

const totalPenyakit = computed(() => totalPmk.value + totalLsd.value);

// Card 4: Fasilitas Peternakan
const rphCount = computed(() => {
    return props.rphList && props.rphList.length > 0 ? props.rphList.length : 5;
});

const poskeswanCount = computed(() => activeList.value.reduce((acc, k) => acc + (k.poskeswan || 0), 0) || 12);
const totalFasilitas = computed(() => rphCount.value + poskeswanCount.value);

const dynamicCards = computed<KpiCardItem[]>(() => {
    let popTrend = '↑ 2,4%';
    let popSuffix = 'vs periode 2025';
    if (props.selectedYear === 2024) {
        popTrend = 'Basis 2024';
        popSuffix = 'vs periode 2023';
    } else if (props.selectedYear === 2025) {
        popTrend = '↑ 2,1%';
        popSuffix = 'vs periode 2024';
    } else if (props.selectedYear === 'all') {
        popTrend = 'Kumulatif';
        popSuffix = 'Rentang Arsip 3 Tahun';
    }

    let prodSuffix = `• ${formatAngka(totalSusu.value)} L susu`;
    if (props.selectedYear === 2024) {
        prodSuffix = `• ${formatAngka(totalSusu.value)} L susu (2024)`;
    } else if (props.selectedYear === 'all') {
        prodSuffix = `• ${formatAngka(totalSusu.value)} L susu (Arsip)`;
    }

    let diseaseTrend = 'Waspada EWS';
    let diseaseSuffix = `• ${totalPmk.value} PMK • ${totalLsd.value} LSD aktif`;
    if (props.selectedYear === 2024) {
        diseaseTrend = 'Waspada EWS (2024)';
        diseaseSuffix = `• ${totalPmk.value} PMK • ${totalLsd.value} LSD aktif`;
    } else if (props.selectedYear === 'all') {
        diseaseTrend = 'Akumulasi Kasus';
        diseaseSuffix = `• ${totalPmk.value} PMK • ${totalLsd.value} LSD (2024-2026)`;
    }

    let facilitySuffix = `• ${poskeswanCount.value} Poskeswan`;
    if (props.selectedYear === 2024) {
        facilitySuffix = `• ${poskeswanCount.value} Poskeswan (2024)`;
    } else if (props.selectedYear === 'all') {
        facilitySuffix = `• ${poskeswanCount.value} Poskeswan (Arsip)`;
    }

    return [
        {
            id: 'card-populasi',
            title: 'Total Populasi Ternak',
            value: formatAngka(totalPopulasi.value),
            unit: 'ekor',
            trend: popTrend,
            trendSuffix: popSuffix,
            trendColorClass: 'text-[#00D084]',
            iconBoxClass: 'dark:bg-[#00D084]/10 bg-[#00D084]/10 text-[#00D084] rounded-lg p-2',
            iconType: 'populasi',
        },
        {
            id: 'card-produksi',
            title: 'Total Produksi Pangan',
            value: produksiFormatted.value,
            unit: 'kg daging',
            trend: `${formatAngka(totalDaging.value)} kg daging`,
            trendSuffix: prodSuffix,
            trendColorClass: 'text-[#32B9D0]',
            iconBoxClass: 'dark:bg-[#32B9D0]/10 bg-[#32B9D0]/10 text-[#32B9D0] rounded-lg p-2',
            iconType: 'produksi',
        },
        {
            id: 'card-penyakit',
            title: 'Kesehatan Hewan & Kasus Wabah',
            value: formatAngka(totalPenyakit.value),
            unit: 'kasus aktif',
            trend: diseaseTrend,
            trendSuffix: diseaseSuffix,
            trendColorClass: 'text-[#DC625F]',
            iconBoxClass: 'dark:bg-[#DC625F]/10 bg-[#DC625F]/10 text-[#DC625F] rounded-lg p-2',
            iconType: 'penyakit',
        },
        {
            id: 'card-fasilitas',
            title: 'Fasilitas Peternakan',
            value: String(totalFasilitas.value),
            unit: 'unit layanan',
            trend: `${rphCount.value} RPH Resmi`,
            trendSuffix: facilitySuffix,
            trendColorClass: 'dark:text-[#00D084] text-[#171B17]',
            iconBoxClass: 'dark:bg-[#00D084]/10 bg-[#00D084]/10 text-[#00D084] rounded-lg p-2',
            iconType: 'fasilitas',
        },
    ];
});

const displayCards = computed<KpiCardItem[]>(() => {
    return props.cards && props.cards.length > 0 ? props.cards : dynamicCards.value;
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 w-full">
        <div
            v-for="card in displayCards"
            :key="card.id"
            @click="handleCardClick(card.id)"
            class="rounded-xl p-4 shadow-xs transition-all flex flex-col justify-between group cursor-pointer relative"
            :class="[
                currentSelectedId === card.id
                    ? 'border-2 border-[#171B17] bg-white dark:border-[#00D084] dark:bg-[#111111] shadow-sm ring-1 ring-[#00D084]/20'
                    : 'dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#171B17]/40',
            ]"
        >
            <!-- Card Header: Icon & Selected Checkmark -->
            <div class="flex items-center justify-between mb-3">
                <div :class="['w-9 h-9 flex items-center justify-center', card.iconBoxClass]">
                    <!-- Cow Icon (Card 1) -->
                    <svg
                        v-if="card.iconType === 'populasi' || card.iconType === 'cow'"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M4 6 C4 4, 2 3, 2 3 M20 6 C20 4, 22 3, 22 3 M5 8 L19 8 C20 8, 21 9, 21 11 L21 15 C21 18, 18 20, 15 20 L9 20 C6 20, 3 18, 3 15 L3 11 C3 9, 4 8, 5 8 Z" />
                    </svg>

                    <!-- Produksi / Package Icon (Card 2) -->
                    <Package
                        v-else-if="card.iconType === 'produksi' || card.iconType === 'farmer'"
                        class="w-4 h-4"
                    />

                    <!-- Penyakit / Alert Icon (Card 3) -->
                    <AlertTriangle
                        v-else-if="card.iconType === 'penyakit' || card.iconType === 'pmk' || card.iconType === 'lsd'"
                        class="w-4 h-4"
                    />

                    <!-- Fasilitas / Building2 Icon (Card 4) -->
                    <Building2
                        v-else-if="card.iconType === 'fasilitas' || card.iconType === 'rph'"
                        class="w-4 h-4"
                    />
                </div>

                <!-- Emerald checkmark indicator when card is selected -->
                <div
                    v-if="currentSelectedId === card.id"
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-[#00D084]/15 border border-[#00D084]/30 text-[#00D084]"
                    title="Pilihan Aktif"
                >
                    <Check class="w-3.5 h-3.5 stroke-[2.5]" />
                </div>
            </div>

            <!-- Card Body: Title & Big Metric Value -->
            <div>
                <span class="text-xs dark:text-[#858585] text-[#737B71] font-medium block truncate">
                    {{ card.title }}
                </span>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="font-bold font-mono text-2xl dark:text-[#F5F5F5] text-[#171B17] tabular-nums">
                        {{ card.value }}
                    </span>
                    <span v-if="card.unit" class="text-xs dark:text-[#858585] text-[#737B71] font-mono">
                        {{ card.unit }}
                    </span>
                </div>
            </div>

            <!-- Card Footer: Trend & Period Comparison -->
            <div class="flex items-center justify-between gap-1.5 mt-3 pt-2.5 border-t dark:border-[#1E1E1E] border-[#E0E4DD] text-[11px] overflow-hidden">
                <span :class="[card.trendColorClass, 'font-mono font-semibold text-[11px] tabular-nums truncate shrink-0']">
                    {{ card.trend }}
                </span>
                <span class="dark:text-[#858585] text-[#737B71] text-[10px] truncate">
                    {{ card.trendSuffix }}
                </span>
            </div>
        </div>
    </div>
</template>
