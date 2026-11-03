<script setup lang="ts">
import { ref, computed, watch, type Component } from 'vue';
import {
    METRIC_LIST,
    type MetricDefinition,
    type KecamatanData,
    formatAngka,
    CHOROPLETH_DARK_CLASSES,
} from '../../data/banyumasData';
import { getDesaByKecamatan, getDesaStatusCounts } from '../../data/banyumasDesaData';
import {
    Search,
    ChevronDown,
    ChevronUp,
    Trophy,
    Layers,
    SlidersHorizontal,
    Beef,
    Activity,
    TrendingUp,
    Building2,
} from 'lucide-vue-next';

interface LayerDefinition {
    id: string;
    label: string;
    sublabel: string;
    defaultState: boolean;
}

interface CategoryItem {
    id: 'Populasi' | 'Penyakit' | 'Produksi' | 'Fasilitas';
    label: string;
    icon: Component;
}

const props = withDefaults(
    defineProps<{
        selectedMetric: MetricDefinition;
        activeKategori: 'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas';
        kecamatanList: KecamatanData[];
        quantiles: number[];
        currentRole: 'publik' | 'dinas' | 'admin';
        selectedKecamatanId?: string;
        layerStates?: Record<string, boolean>;
    }>(),
    {
        currentRole: 'publik',
    }
);

const emit = defineEmits<{
    (e: 'update:activeKategori', val: 'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'): void;
    (e: 'update:selectedMetric', val: MetricDefinition): void;
    (e: 'selectKecamatan', id: string): void;
    (e: 'update:layerStates', val: Record<string, boolean>): void;
}>();

// Definisi Kategori Tier 1
const CATEGORIES: CategoryItem[] = [
    { id: 'Populasi', label: 'Populasi', icon: Beef },
    { id: 'Penyakit', label: 'Penyakit', icon: Activity },
    { id: 'Produksi', label: 'Produksi', icon: TrendingUp },
    { id: 'Fasilitas', label: 'Fasilitas', icon: Building2 },
];

// State Combobox Tier 2 & Pencarian
const isComboboxOpen = ref(false);
const commoditySearch = ref('');

// Filter daftar komoditas berdasarkan Kategori Tier 1 dan Search Query
const filteredCommodities = computed(() => {
    return METRIC_LIST.filter(m => {
        const matchesCategory = m.kategori === props.activeKategori;
        const matchesQuery = m.label.toLowerCase().includes(commoditySearch.value.toLowerCase());
        return matchesCategory && matchesQuery;
    });
});

// Definisi Layer Peta (Card B)
const LAYER_DEFINITIONS: LayerDefinition[] = [
    { id: 'batasKabupaten', label: 'Batas Kabupaten', sublabel: 'Garis batas terluar wilayah', defaultState: true },
    { id: 'batasKecamatan', label: 'Batas Kecamatan', sublabel: 'Wilayah 27 kecamatan', defaultState: true },
    { id: 'batasDesa', label: 'Batas Desa', sublabel: 'Detail batas 331 desa/kelurahan', defaultState: false },
    { id: 'heatmapPopulasi', label: 'Heatmap Populasi', sublabel: 'Distribusi intensitas densitas', defaultState: false },
    { id: 'fasilitasPeternakan', label: 'Fasilitas Peternakan', sublabel: 'Poskeswan & RPH terdaftar', defaultState: false },
    { id: 'jalurTransportasi', label: 'Jalur Transportasi', sublabel: 'Jaringan jalan utama & arteri', defaultState: false },
];

const defaultLayers: Record<string, boolean> = {
    batasKabupaten: true,
    batasKecamatan: true,
    batasDesa: false,
    heatmapPopulasi: false,
    fasilitasPeternakan: false,
    jalurTransportasi: false,
};

const internalLayers = ref<Record<string, boolean>>({
    ...defaultLayers,
    ...(props.layerStates || {}),
});

watch(
    () => props.layerStates,
    (newVal) => {
        if (newVal) {
            internalLayers.value = { ...defaultLayers, ...newVal };
        }
    },
    { deep: true }
);

function toggleLayer(key: string) {
    internalLayers.value = {
        ...internalLayers.value,
        [key]: !internalLayers.value[key],
    };
    emit('update:layerStates', { ...internalLayers.value });
}

// State Card C: Legenda Collapsible
const isLegendaCollapsed = ref(false);

const legendQuantileRanges = computed(() => {
    const q = props.quantiles;
    const s = props.selectedMetric.satuan;

    const q0 = q[0] ?? 50;
    const q1 = q[1] ?? 200;
    const q2 = q[2] ?? 500;
    const q3 = q[3] ?? 1000;

    return [
        {
            color: CHOROPLETH_DARK_CLASSES[5],
            label: 'Sangat Tinggi',
            range: `> ${formatAngka(q3)} ${s}`,
        },
        {
            color: CHOROPLETH_DARK_CLASSES[4],
            label: 'Tinggi',
            range: `${formatAngka(q2 + 1)} - ${formatAngka(q3)} ${s}`,
        },
        {
            color: CHOROPLETH_DARK_CLASSES[3],
            label: 'Sedang',
            range: `${formatAngka(q1 + 1)} - ${formatAngka(q2)} ${s}`,
        },
        {
            color: CHOROPLETH_DARK_CLASSES[2],
            label: 'Rendah',
            range: `${formatAngka(q0 + 1)} - ${formatAngka(q1)} ${s}`,
        },
        {
            color: CHOROPLETH_DARK_CLASSES[1],
            label: 'Sangat Rendah',
            range: `1 - ${formatAngka(q0)} ${s}`,
        },
    ];
});

const selectedKecamatan = computed(() => {
    if (!props.selectedKecamatanId) return null;
    return props.kecamatanList.find((k) => k.id === props.selectedKecamatanId) || null;
});

const selectedKecamatanDesaStatus = computed(() => {
    if (!props.selectedKecamatanId) return null;
    const desaList = getDesaByKecamatan(props.selectedKecamatanId);
    if (!desaList || desaList.length === 0) return null;
    const counts = getDesaStatusCounts(desaList);
    return {
        total: desaList.length,
        ...counts,
    };
});

// State Card D: Ranking & Relative Progress Bars
const isAllRankingsOpen = ref(false);

const sortedKecamatanList = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    return [...props.kecamatanList].sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0));
});

const displayedRankings = computed(() => {
    return isAllRankingsOpen.value ? sortedKecamatanList.value : sortedKecamatanList.value.slice(0, 5);
});

const maxVal = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const vals = props.kecamatanList.map(k => Number(k[key]) || 0);
    return Math.max(...vals, 1);
});

function getProgressPercent(kec: KecamatanData): number {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const val = Number(kec[key]) || 0;
    return Math.min(Math.max(Math.round((val / maxVal.value) * 100), 2), 100);
}

// Ringkasan Naratif
const summaryText = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const sorted = sortedKecamatanList.value;
    const total = sorted.reduce((acc, curr) => acc + Number(curr[key] || 0), 0);
    const top = sorted[0];

    if (!top) return '';

    if (props.selectedMetric.kategori === 'Penyakit') {
        const totalBahaya = props.kecamatanList.filter(k => k.zona === 'bahaya').length;
        return `${totalBahaya} kecamatan masuk zona merah ${props.selectedMetric.label}. Paling tinggi di ${top.nama} (${top[key]} ${props.selectedMetric.satuan}).`;
    }

    if (props.currentRole === 'publik') {
        return `Ada ${formatAngka(total)} ${props.selectedMetric.satuan} ${props.selectedMetric.label.toLowerCase()} di 27 kecamatan. Paling banyak di ${top.nama}.`;
    } else {
        return `Total ${formatAngka(total)} ${props.selectedMetric.satuan} ${props.selectedMetric.label}. Terpusat di ${top.nama} sejumlah ${formatAngka(Number(top[key]))} ${props.selectedMetric.satuan}.`;
    }
});

function changeCategory(kat: 'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas') {
    emit('update:activeKategori', kat);
    isComboboxOpen.value = false;
    commoditySearch.value = '';
    const firstMetric = METRIC_LIST.find(m => m.kategori === kat);
    if (firstMetric) {
        emit('update:selectedMetric', firstMetric);
    }
}

function selectMetric(metric: MetricDefinition) {
    emit('update:selectedMetric', metric);
    isComboboxOpen.value = false;
    commoditySearch.value = '';
}
</script>

<template>
    <aside class="w-full max-w-full shrink-0 flex flex-col gap-3 overflow-x-hidden select-none box-border">
        <!-- KARTU A: DATA PETA -->
        <section class="w-full max-w-full box-border dark:bg-[#111111] bg-[#FFFFFF] rounded-2xl p-4 border dark:border-[#1E1E1E] border-[#E0E4DD] shadow-sm flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] flex items-center gap-2">
                    Data Peta
                </h3>
                <span class="text-[10px] font-semibold text-[#00D084] uppercase tracking-wider dark:bg-[#00D084]/15 dark:text-[#00D084] dark:border-[#00D084]/30 bg-[#E8F8F0] px-2 py-0.5 rounded-full border border-[#00D084]/20">
                    Prioritas
                </span>
            </div>
            <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] -mt-1">
                Pilih komoditas dan atur tampilan data peta.
            </p>

            <!-- 4 Kategori Chips (Populasi, Penyakit, Produksi, Fasilitas) -->
            <div class="grid grid-cols-4 gap-1.5 p-1 dark:bg-[#0C0C0C] bg-[#F4F5F2] rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD]">
                <button
                    v-for="cat in CATEGORIES"
                    :key="cat.id"
                    type="button"
                    @click="changeCategory(cat.id)"
                    class="h-12 px-1 rounded-lg text-center flex flex-col items-center justify-center gap-1 transition-all cursor-pointer"
                    :class="activeKategori === cat.id
                        ? 'bg-[#171B17] text-white font-bold shadow-sm border border-[#171B17] dark:bg-[#181818] dark:text-[#F5F5F5] dark:border-[#282828]'
                        : 'bg-white text-[#444C43] border border-[#E0E4DD] hover:bg-[#EDEFEB] hover:text-[#171B17] dark:bg-transparent dark:border-transparent dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#181818]'"
                >
                    <component :is="cat.icon" class="w-4 h-4 shrink-0" />
                    <span class="text-[11px] leading-none truncate">{{ cat.label }}</span>
                </button>
            </div>

            <!-- Dropdown Selector Komoditas & Search Popover -->
            <div class="relative">
                <button
                    type="button"
                    @click="isComboboxOpen = !isComboboxOpen"
                    class="w-full h-11 px-3.5 dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:border-[#00D084] focus:border-[#00D084] rounded-xl flex items-center justify-between dark:text-[#F5F5F5] text-[#171B17] font-semibold text-xs transition-all shadow-sm focus:outline-none cursor-pointer"
                    aria-haspopup="listbox"
                    :aria-expanded="isComboboxOpen"
                >
                    <div class="flex items-center gap-2 truncate">
                        <span class="font-bold dark:text-[#F5F5F5] text-[#171B17] truncate">{{ selectedMetric.label }}</span>
                        <span class="text-xs dark:text-[#B3B3B3] text-[#6E776D] font-normal">({{ selectedMetric.satuan }})</span>
                    </div>
                    <ChevronDown class="w-4 h-4 dark:text-[#B3B3B3] text-[#6E776D] transition-transform shrink-0" :class="{ 'rotate-180': isComboboxOpen }" />
                </button>

                <!-- Search input + Filter button row -->
                <div class="flex items-center gap-2 mt-2">
                    <div class="relative flex-1">
                        <Search class="w-3.5 h-3.5 absolute left-3 top-2.5 dark:text-[#B3B3B3] text-[#6E776D]" />
                        <input
                            v-model="commoditySearch"
                            type="text"
                            placeholder="Cari komoditas..."
                            @focus="isComboboxOpen = true"
                            class="w-full h-8 pl-8 pr-3 dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-lg text-xs dark:text-[#F5F5F5] text-[#171B17] placeholder:text-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>
                    <button
                        type="button"
                        @click="isComboboxOpen = !isComboboxOpen"
                        class="h-8 px-2.5 rounded-lg border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#181818] bg-[#F4F5F2] dark:hover:bg-[#1E1E1E] hover:bg-[#EDEFEB] text-xs font-medium dark:text-[#B3B3B3] text-[#444C43] dark:hover:text-[#F5F5F5] hover:text-[#171B17] flex items-center gap-1.5 transition-colors cursor-pointer shrink-0"
                        title="Filter Komoditas"
                    >
                        <SlidersHorizontal class="w-3.5 h-3.5 text-[#00D084]" />
                        <span>Filter</span>
                    </button>
                </div>

                <!-- Popover Menu Dropdown Tier 2 -->
                <div
                    v-if="isComboboxOpen"
                    class="absolute top-full left-0 right-0 mt-1.5 z-50 dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2 shadow-2xl backdrop-blur-xl animate-in fade-in zoom-in-95 duration-150"
                >
                    <ul class="max-h-48 overflow-y-auto space-y-1 pr-1">
                        <li
                            v-for="item in filteredCommodities"
                            :key="item.id"
                            @click="selectMetric(item)"
                            class="min-h-[36px] px-3 py-2 rounded-lg text-xs font-medium flex items-center justify-between cursor-pointer transition-colors"
                            :class="selectedMetric.id === item.id 
                                ? 'bg-[#171B17] text-white font-bold border border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#00D084]/40' 
                                : 'text-[#444C43] hover:text-[#171B17] hover:bg-[#EDEFEB] dark:text-[#B3B3B3] dark:hover:bg-[#181818] dark:hover:text-[#F5F5F5]'"
                        >
                            <span>{{ item.label }}</span>
                            <span class="text-[11px] font-mono tabular-nums" :class="selectedMetric.id === item.id ? 'text-white/80 dark:text-[#00D084]/80' : 'text-[#6E776D] dark:text-[#858585]'">{{ item.satuan }}</span>
                        </li>
                        <li v-if="filteredCommodities.length === 0" class="px-3 py-3 text-center text-xs dark:text-[#B3B3B3] text-[#6E776D]">
                            Tidak ada komoditas di kategori ini.
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- KARTU B: LAYER PETA -->
        <section class="w-full max-w-full box-border dark:bg-[#111111] bg-[#FFFFFF] rounded-2xl p-4 border dark:border-[#1E1E1E] border-[#E0E4DD] shadow-sm flex flex-col gap-2.5">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] flex items-center gap-2">
                    <Layers class="w-4 h-4 text-[#00D084]" />
                    Layer Peta
                </h3>
                <span class="text-[10px] font-semibold dark:text-[#B3B3B3] text-[#6E776D] dark:bg-[#181818] bg-[#F4F5F2] px-2 py-0.5 rounded-full border dark:border-[#1E1E1E] border-[#E0E4DD]">
                    Kontrol
                </span>
            </div>
            <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] -mt-1">
                Aktifkan layer sesuai kebutuhan.
            </p>

            <div class="space-y-1 mt-1">
                <div
                    v-for="layer in LAYER_DEFINITIONS"
                    :key="layer.id"
                    @click="toggleLayer(layer.id)"
                    class="flex items-center justify-between p-2 rounded-xl dark:hover:bg-[#181818] hover:bg-[#F4F5F2] transition-colors cursor-pointer select-none border border-transparent dark:hover:border-[#1E1E1E] hover:border-[#E0E4DD]"
                >
                    <div class="flex flex-col text-left">
                        <span class="text-xs font-semibold dark:text-[#F5F5F5] text-[#171B17]">{{ layer.label }}</span>
                        <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D]">{{ layer.sublabel }}</span>
                    </div>
                    <!-- Accessible Switch Button -->
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="!!internalLayers[layer.id]"
                        @click.stop="toggleLayer(layer.id)"
                        class="w-9 h-5 rounded-full transition-colors relative focus:outline-none focus:ring-2 focus:ring-[#00D084]/50 cursor-pointer shrink-0"
                        :class="internalLayers[layer.id] ? 'bg-[#00D084]' : 'dark:bg-[#1E1E1E] bg-[#CCD2C8]'"
                    >
                        <span
                            class="w-4 h-4 rounded-full bg-white shadow-sm transform transition-transform absolute top-0.5"
                            :class="internalLayers[layer.id] ? 'translate-x-4 left-0.5' : 'translate-x-0.5 left-0'"
                        />
                    </button>
                </div>
            </div>
        </section>

        <!-- KARTU C: LEGENDA -->
        <section class="w-full max-w-full box-border dark:bg-[#111111] bg-[#FFFFFF] rounded-2xl p-4 border dark:border-[#1E1E1E] border-[#E0E4DD] shadow-sm flex flex-col gap-2.5">
            <div
                class="flex items-center justify-between cursor-pointer select-none"
                @click="isLegendaCollapsed = !isLegendaCollapsed"
            >
                <div class="flex flex-col text-left">
                    <div class="flex items-center gap-2">
                        <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17]">Legenda</h3>
                        <span class="text-[10px] font-semibold text-[#00D084] px-2 py-0.5 rounded-full dark:bg-[#00D084]/15 dark:border-[#00D084]/30 bg-[#E8F8F0] border border-[#00D084]/20">
                            {{ selectedMetric.kategori === 'Penyakit' ? 'Kasus & Zona' : '5 Kelas Kuantil' }}
                        </span>
                    </div>
                    <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">
                        {{ selectedMetric.kategori === 'Penyakit' ? 'Status risiko wilayah & sebaran kasus.' : `Klasifikasi kuantitatif ${selectedMetric.label.toLowerCase()} (${selectedMetric.satuan}).` }}
                    </p>
                </div>
                <button
                    type="button"
                    class="w-6 h-6 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-center dark:text-[#B3B3B3] text-[#6E776D] dark:hover:text-[#F5F5F5] hover:text-[#171B17] transition-colors shrink-0"
                    :aria-expanded="!isLegendaCollapsed"
                    aria-label="Toggle Legenda"
                >
                    <ChevronUp v-if="!isLegendaCollapsed" class="w-4 h-4" />
                    <ChevronDown v-else class="w-4 h-4" />
                </button>
            </div>

            <!-- Konten Legenda (Collapsible) -->
            <div v-show="!isLegendaCollapsed" class="pt-1">
                <!-- Legenda Populasi / Kuantitatif (5 Kelas Diskrit) -->
                <div v-if="selectedMetric.kategori !== 'Penyakit'" class="space-y-2">
                    <div
                        v-for="(item, idx) in legendQuantileRanges"
                        :key="idx"
                        class="flex items-center justify-between text-xs"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-4 h-4 rounded border dark:border-white/20 border-black/10 shrink-0"
                                :style="{ backgroundColor: item.color }"
                            />
                            <span class="dark:text-[#F5F5F5] text-[#171B17] font-semibold">{{ item.label }}</span>
                        </div>
                        <span class="dark:text-[#B3B3B3] text-[#6E776D] font-mono text-xs tabular-nums text-right">
                            {{ item.range }}
                        </span>
                    </div>
                </div>

                <!-- Legenda Kasus Penyakit (Simbol Proporsional & Pola Zona) -->
                <div v-else class="space-y-2">
                    <div class="text-[11px] font-semibold dark:text-[#B3B3B3] text-[#6E776D] mb-1">Status Zona Wilayah:</div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-4 h-4 rounded border border-[#DC625F] bg-[#DC625F]/20 pattern-bahaya shrink-0"></span>
                        <span class="text-[#DC625F] font-semibold">Zona Merah (Bahaya)</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-4 h-4 rounded border border-[#E8B84C] bg-[#E8B84C]/20 pattern-waspada shrink-0"></span>
                        <span class="text-[#E8B84C] font-semibold">Zona Kuning (Waspada)</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-4 h-4 rounded border border-[#20B88A] bg-[#20B88A]/20 shrink-0"></span>
                        <span class="text-[#20B88A] font-semibold">Zona Hijau (Aman)</span>
                    </div>

                    <div class="pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD] mt-2">
                        <div class="text-[11px] font-semibold dark:text-[#B3B3B3] text-[#6E776D] mb-1">Ukuran Simbol Kasus:</div>
                        <div class="flex items-center gap-3 text-xs dark:text-[#F5F5F5] text-[#171B17] tabular-nums">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#DC625F]/60 border border-[#DC625F]"></span>
                                <span>1-3</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-[#DC625F]/60 border border-[#DC625F]"></span>
                                <span>4-8</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-[#DC625F]/60 border border-[#DC625F]"></span>
                                <span class="font-bold">≥ 9 kasus</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Kasus per Desa (EWS Tingkat Desa) -->
                <div class="pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] mt-3 space-y-2">
                    <div class="text-[11px] font-semibold dark:text-[#F5F5F5] text-[#171B17] flex items-center justify-between">
                        <span>Status Kasus per Desa (EWS Tingkat Desa)</span>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-3.5 h-3.5 rounded border border-[#DC625F] bg-[#DC625F]/25 shrink-0"></span>
                            <span class="text-[#DC625F] font-semibold text-[11px]">Zona Merah Desa (Bahaya: Kasus Aktif Tinggi)</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-3.5 h-3.5 rounded border border-[#E8B84C] bg-[#E8B84C]/25 shrink-0"></span>
                            <span class="text-[#E8B84C] font-semibold text-[11px]">Zona Kuning Desa (Waspada: Terdeteksi Kasus)</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-3.5 h-3.5 rounded border border-[#20B88A] bg-[#20B88A]/25 shrink-0"></span>
                            <span class="text-[#20B88A] font-semibold text-[11px]">Zona Hijau Desa (Aman: Bebas Kasus Wabah)</span>
                        </div>
                    </div>

                    <!-- Contextual Kecamatan Desa Summary -->
                    <div
                        v-if="selectedKecamatanDesaStatus && selectedKecamatan"
                        class="mt-2.5 p-2.5 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] flex flex-col gap-1 text-[11px]"
                    >
                        <div class="font-semibold dark:text-[#F5F5F5] text-[#171B17]">
                            Ringkasan Desa di Kec. {{ selectedKecamatan.nama }}:
                        </div>
                        <div class="text-[10.5px] dark:text-[#B3B3B3] text-[#6E776D] font-medium">
                            {{ selectedKecamatanDesaStatus.aman }} Desa Aman · {{ selectedKecamatanDesaStatus.waspada }} Waspada · {{ selectedKecamatanDesaStatus.bahaya }} Bahaya
                        </div>
                        <div class="flex items-center gap-1.5 font-mono text-[10px] tabular-nums mt-0.5">
                            <span class="px-1.5 py-0.5 rounded bg-[#20B88A]/15 border border-[#20B88A]/30 text-[#20B88A] font-medium">
                                {{ selectedKecamatanDesaStatus.aman }} Aman
                            </span>
                            <span class="px-1.5 py-0.5 rounded bg-[#E8B84C]/15 border border-[#E8B84C]/30 text-[#E8B84C] font-medium">
                                {{ selectedKecamatanDesaStatus.waspada }} Waspada
                            </span>
                            <span class="px-1.5 py-0.5 rounded bg-[#DC625F]/15 border border-[#DC625F]/30 text-[#DC625F] font-medium">
                                {{ selectedKecamatanDesaStatus.bahaya }} Bahaya
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- KARTU D: PERINGKAT KECAMATAN -->
        <section class="w-full max-w-full box-border dark:bg-[#111111] bg-[#FFFFFF] rounded-2xl p-4 border dark:border-[#1E1E1E] border-[#E0E4DD] shadow-sm flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Trophy class="w-4 h-4 text-[#00D084]" />
                    <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17]">Peringkat Kecamatan</h3>
                    <span class="text-[10px] font-semibold text-[#00D084] dark:bg-[#00D084]/15 bg-[#E8F8F0] px-2 py-0.5 rounded-full border dark:border-[#00D084]/30 border-[#00D084]/20 font-mono tabular-nums">
                        {{ isAllRankingsOpen ? 'Semua' : 'Top 5' }}
                    </span>
                </div>
                <button
                    type="button"
                    @click="isAllRankingsOpen = !isAllRankingsOpen"
                    class="text-xs font-semibold text-[#00D084] hover:text-[#00F0A0] transition-colors cursor-pointer"
                >
                    {{ isAllRankingsOpen ? 'Top 5 ←' : 'Lihat semua →' }}
                </button>
            </div>

            <!-- Kalimat Ringkasan Ramah -->
            <p class="text-xs dark:text-[#B3B3B3] text-[#444C43] leading-relaxed dark:bg-[#181818] bg-[#F4F5F2] p-3 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] tabular-nums">
                {{ summaryText }}
            </p>

            <!-- Daftar Peringkat & Relative Progress Bars -->
            <ul class="space-y-2">
                <li
                    v-for="(kec, idx) in displayedRankings"
                    :key="kec.id"
                    @click="emit('selectKecamatan', kec.id)"
                    class="p-2.5 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all"
                    :class="selectedKecamatanId === kec.id 
                        ? 'border-2 border-[#171B17] bg-white dark:bg-[#181818] dark:border-[#00D084] shadow-xs' 
                        : 'dark:bg-[#181818] bg-[#F4F5F2] dark:border-[#1E1E1E] border-[#E0E4DD] dark:hover:bg-[#1E1E1E] hover:bg-[#EFF2ED]'"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span
                                class="w-6 h-6 rounded-full text-[11px] font-bold font-mono tabular-nums flex items-center justify-center shrink-0"
                                :class="idx === 0
                                    ? 'bg-[#00D084] text-[#062117] shadow-sm font-black'
                                    : 'dark:bg-[#1E1E1E] bg-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] border dark:border-[#282828] border-[#CCD2C8]'"
                            >
                                {{ idx + 1 }}
                            </span>
                            <div class="flex flex-col text-left min-w-0">
                                <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] truncate leading-tight">{{ kec.nama }}</span>
                                <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] font-mono tabular-nums leading-tight">{{ getDesaByKecamatan(kec.id).length }} Desa</span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="font-bold text-[#00D084] font-mono text-xs tabular-nums">
                                {{ formatAngka(Number(kec[selectedMetric.id as keyof KecamatanData])) }}
                            </span>
                            <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] font-normal ml-1">{{ selectedMetric.satuan }}</span>
                        </div>
                    </div>

                    <!-- Relative Progress Bar -->
                    <div class="w-full h-1.5 dark:bg-[#090909] bg-[#E0E4DD] rounded-full overflow-hidden">
                        <div
                            class="h-full bg-[#00D084] rounded-full transition-all duration-300"
                            :style="{ width: `${getProgressPercent(kec)}%` }"
                        />
                    </div>
                </li>
            </ul>
        </section>
    </aside>
</template>
