<script setup lang="ts">
import { ref, computed } from 'vue';
import type {
    KecamatanData,
    MetricDefinition
} from '../../data/banyumasData';
import {
    formatAngka,
    calculateQuantiles,
    getClassLevel,
    KECAMATAN_LIST
} from '../../data/banyumasData';
import { getDesaByKecamatan, type DesaData } from '../../data/banyumasDesaData';
import {
    X,
    ExternalLink,
    Building2,
    Users,
    MapPin,
    ArrowRight,
    ShieldCheck,
    Info,
    AlertTriangle,
    CheckCircle2,
    Search,
    ChevronLeft
} from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    kecamatan: KecamatanData;
    selectedMetric: MetricDefinition;
    currentRole?: 'publik' | 'dinas' | 'admin';
    selectedDesaId?: string | null;
}>(), {
    currentRole: 'publik',
    selectedDesaId: null
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'drillDown', kec: KecamatanData): void;
    (e: 'focusDesa', desa: DesaData): void;
    (e: 'openRules'): void;
}>();

// Tab kategori pada card Rule / Aturan
const activeRuleTab = ref<'Populasi' | 'Penyakit' | 'Fasilitas'>('Populasi');

// Tampilan list desa
const showDesaView = ref(false);
const searchDesaQuery = ref('');

// Data Desa & Kelurahan di Kecamatan ini
const desaList = computed(() => {
    return getDesaByKecamatan(props.kecamatan.id);
});

const filteredDesaList = computed(() => {
    if (!searchDesaQuery.value.trim()) return desaList.value;
    const q = searchDesaQuery.value.toLowerCase().trim();
    return desaList.value.filter(d =>
        d.nama.toLowerCase().includes(q) || d.kode_kd.includes(q)
    );
});

// Luas Wilayah lookup resmi (km²)
const KECAMATAN_LUAS: Record<string, string> = {
    wangon: '60,78 km²',
    lumbir: '102,66 km²',
    jatilawang: '48,16 km²',
    rawalo: '49,64 km²',
    kebasen: '53,64 km²',
    kemranjen: '60,71 km²',
    sumpiuh: '56,50 km²',
    tambak: '52,03 km²',
    somagede: '40,11 km²',
    kalibagor: '35,73 km²',
    banyumas: '38,09 km²',
    patikraja: '43,23 km²',
    purwojati: '37,86 km²',
    ajibarang: '66,50 km²',
    gumelar: '93,95 km²',
    pekuncen: '92,70 km²',
    cilongok: '105,34 km²',
    karanglewas: '32,50 km²',
    kedungbanteng: '60,22 km²',
    baturraden: '45,53 km²',
    sumbang: '53,42 km²',
    kembaran: '25,92 km²',
    sokaraja: '29,92 km²',
    purwokerto_selatan: '13,75 km²',
    purwokerto_barat: '7,40 km²',
    purwokerto_timur: '8,42 km²',
    purwokerto_utara: '9,01 km²',
};

const formattedArea = computed(() => {
    const kId = props.kecamatan.id.toLowerCase();
    if (KECAMATAN_LUAS[kId]) return KECAMATAN_LUAS[kId];
    return `${(desaList.value.length * 3.65).toFixed(2).replace('.', ',')} km²`;
});

// Estimasi peternak resmi terdata
const estimatedFarmers = computed(() => {
    const k = props.kecamatan;
    const totalTernak = (k.sapi_potong || 0) + (k.sapi_perah || 0) + Math.round(((k.kambing || 0) + (k.domba || 0)) / 2.5);
    return Math.max(120, Math.round(totalTernak / 4));
});

// Nilai metrik aktif
const activeValue = computed(() => {
    return Number(props.kecamatan[props.selectedMetric.id]) || 0;
});

// Status zona wilayah
const zonaConfig = computed(() => {
    switch (props.kecamatan.zona) {
        case 'bahaya':
            return {
                label: 'Bahaya',
                badgeClass: 'bg-[#DC625F]/15 border-[#DC625F]/40 text-[#DC625F]'
            };
        case 'waspada':
            return {
                label: 'Waspada',
                badgeClass: 'bg-[#E8B84C]/15 border-[#E8B84C]/40 text-[#E8B84C]'
            };
        default:
            return {
                label: 'Aman',
                badgeClass: 'bg-[#20B88A]/15 border-[#20B88A]/40 text-[#20B88A]'
            };
    }
});

// Kualitatif level & label untuk Publik
const qualitativeStatus = computed(() => {
    const quantiles = calculateQuantiles(KECAMATAN_LIST, props.selectedMetric.id);
    const lvl = getClassLevel(activeValue.value, quantiles);
    const labels = [
        'Kategori Sangat Rendah',
        'Kategori Rendah',
        'Kategori Sedang',
        'Kategori Tinggi',
        'Sentra Unggulan'
    ];
    return {
        level: lvl,
        label: labels[lvl - 1] || 'Kategori Sedang'
    };
});
</script>

<template>
    <div
        class="absolute top-4 right-4 z-30 w-80 md:w-84 max-w-[calc(100vw-2rem)] max-h-[calc(100vh-5rem)] overflow-y-auto pr-1 custom-scroll flex flex-col gap-3 pointer-events-auto select-none animate-in fade-in slide-in-from-top-2 duration-200"
    >
        <!-- CARD 1: DETAIL WILAYAH -->
        <div
            class="dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] rounded-2xl p-4 shadow-2xl flex flex-col gap-3"
        >
            <!-- View Mode Normal -->
            <template v-if="!showDesaView">
                <!-- Header Card -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#00D084] inline-block"></span>
                        <span class="text-[11px] font-semibold uppercase tracking-wider dark:text-[#B3B3B3] text-[#6E776D]">
                            Detail Wilayah
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="$emit('drillDown', kecamatan)"
                            class="w-7 h-7 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:text-[#00D084] dark:hover:border-[#00D084]/40 flex items-center justify-center transition-colors cursor-pointer"
                            title="Buka Rincian Lengkap"
                            aria-label="Buka Rincian Lengkap"
                        >
                            <ExternalLink class="w-3.5 h-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="w-7 h-7 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:text-[#DC625F] dark:hover:border-[#DC625F]/40 flex items-center justify-center transition-colors cursor-pointer"
                            title="Tutup Panel (Esc)"
                            aria-label="Tutup Panel"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Region Title & Status Badge -->
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-xl font-display font-bold dark:text-[#F5F5F5] text-[#171B17] tracking-tight leading-tight">
                            {{ kecamatan.nama }}
                        </h2>
                        <span class="text-[10px] font-mono dark:text-[#B3B3B3] text-[#6E776D] tabular-nums">
                            Kode: {{ kecamatan.kode_kec }}
                        </span>
                    </div>
                    <div
                        class="px-2.5 py-1 rounded-full border text-xs font-semibold flex items-center gap-1.5 shrink-0"
                        :class="zonaConfig.badgeClass"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <span>{{ zonaConfig.label }}</span>
                    </div>
                </div>

                <!-- Main Metric Display -->
                <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 flex flex-col gap-1">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="dark:text-[#B3B3B3] text-[#6E776D] font-medium">
                            {{ selectedMetric.kategori === 'Populasi' ? 'Populasi Ternak' : selectedMetric.label }}
                        </span>
                        <span class="text-[#00D084] font-semibold">
                            {{ selectedMetric.label }}
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between mt-0.5">
                        <template v-if="currentRole === 'publik'">
                            <div class="text-sm font-bold text-[#00D084] flex items-center gap-1.5 py-0.5">
                                <span class="w-2 h-2 rounded-full bg-[#00D084]"></span>
                                <span>{{ qualitativeStatus.label }}</span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="text-lg font-bold font-mono text-[#00D084] tabular-nums">
                                {{ formatAngka(activeValue) }}
                                <span class="text-xs font-normal dark:text-[#B3B3B3] text-[#6E776D]">{{ selectedMetric.satuan }}</span>
                            </div>
                        </template>
                    </div>

                    <div v-if="currentRole !== 'publik'" class="text-[11px] text-[#39D6C0] font-medium flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#39D6C0]"></span>
                        <span>{{ qualitativeStatus.label }}</span>
                    </div>
                </div>

                <!-- 3-Stat Grid: Desa, Peternak, Luas Wilayah -->
                <div class="grid grid-cols-3 gap-2">
                    <!-- Desa -->
                    <button
                        type="button"
                        @click="showDesaView = true"
                        class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:border-[#171B17] dark:hover:border-[#00D084]/50 rounded-xl p-2.5 flex flex-col items-center text-center transition-all cursor-pointer group"
                        title="Klik untuk melihat daftar desa"
                    >
                        <Building2 class="w-4 h-4 text-[#00D084] mb-1 group-hover:scale-110 transition-transform" />
                        <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums leading-tight">
                            {{ desaList.length }}
                        </span>
                        <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">Desa</span>
                    </button>

                    <!-- Peternak -->
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex flex-col items-center text-center">
                        <Users class="w-4 h-4 text-[#39D6C0] mb-1" />
                        <template v-if="currentRole === 'publik'">
                            <span class="text-[11px] font-bold text-[#00D084] leading-tight">
                                Terdata
                            </span>
                        </template>
                        <template v-else>
                            <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums leading-tight">
                                {{ formatAngka(estimatedFarmers) }}
                            </span>
                        </template>
                        <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">Peternak</span>
                    </div>

                    <!-- Luas Wilayah -->
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex flex-col items-center text-center">
                        <MapPin class="w-4 h-4 text-[#39D6C0] mb-1" />
                        <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums leading-tight truncate max-w-full">
                            {{ formattedArea.replace(' km²', '') }}
                        </span>
                        <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">km² Luas</span>
                    </div>
                </div>

                <!-- Action Button -->
                <button
                    type="button"
                    @click="$emit('drillDown', kecamatan)"
                    class="w-full py-2.5 px-3 rounded-xl bg-[#00D084] text-[#062117] hover:bg-[#00F0A0] text-xs font-bold flex items-center justify-center gap-1.5 shadow-md cursor-pointer transition-all active:scale-[0.99]"
                >
                    <span>Lihat Detail Lengkap</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </button>
            </template>

            <!-- View Mode: Daftar Desa (Bila diklik dari box Desa) -->
            <template v-else>
                <div class="flex items-center justify-between pb-2 border-b dark:border-[#1E1E1E] border-[#E0E4DD]">
                    <button
                        type="button"
                        @click="showDesaView = false"
                        class="text-xs dark:text-[#B3B3B3] text-[#6E776D] hover:text-[#00D084] flex items-center gap-1 transition-colors cursor-pointer"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        <span>Kembali</span>
                    </button>
                    <span class="text-xs font-semibold dark:text-[#F5F5F5] text-[#171B17]">
                        Desa di {{ kecamatan.nama }} ({{ desaList.length }})
                    </span>
                </div>

                <!-- Input Cari Desa -->
                <div class="relative">
                    <input
                        v-model="searchDesaQuery"
                        type="text"
                        :placeholder="`Cari desa...`"
                        class="w-full h-8 dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] focus:border-[#00D084] rounded-lg px-2.5 pr-7 text-xs dark:text-[#F5F5F5] text-[#171B17] outline-none placeholder:text-[#858585]"
                    />
                    <Search class="w-3.5 h-3.5 dark:text-[#B3B3B3] text-[#6E776D] absolute right-2.5 top-2.5 pointer-events-none" />
                </div>

                <!-- Scroll List Desa -->
                <div class="max-h-56 overflow-y-auto space-y-1.5 pr-1 custom-scroll">
                    <div
                        v-for="d in filteredDesaList"
                        :key="d.id"
                        @click="$emit('focusDesa', d)"
                        class="p-2 rounded-xl flex items-center justify-between transition-all cursor-pointer group"
                        :class="selectedDesaId === d.id
                            ? 'border-2 border-[#171B17] bg-white dark:bg-[#181818] dark:border-[#00D084] shadow-xs'
                            : 'border dark:bg-[#181818] bg-[#F4F5F2] dark:border-[#1E1E1E] border-[#E0E4DD] hover:border-[#171B17]/40 dark:hover:border-[#00D084]/40'"
                    >
                        <div class="min-w-0 pr-2">
                            <div class="text-xs font-semibold dark:text-[#F5F5F5] text-[#171B17] group-hover:text-[#00D084] truncate flex items-center gap-1.5">
                                <CheckCircle2 v-if="selectedDesaId === d.id" class="w-3.5 h-3.5 text-[#00D084] shrink-0" />
                                <span class="truncate">{{ d.nama }}</span>
                            </div>
                            <div class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] font-mono">
                                {{ d.jenis }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold font-mono text-[#00D084] tabular-nums">
                                {{ currentRole === 'publik' ? 'Aktif' : formatAngka(Number(d[selectedMetric.id as keyof DesaData] || 0)) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="filteredDesaList.length === 0" class="py-3 text-center text-xs dark:text-[#B3B3B3] text-[#6E776D]">
                        Tidak ada desa ditemukan
                    </div>
                </div>
            </template>
        </div>

        <!-- CARD 2: RULE / ATURAN -->
        <div
            class="dark:bg-[#111111]/95 bg-white/95 border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] rounded-2xl p-4 shadow-2xl backdrop-blur-md flex flex-col gap-3"
        >
            <!-- Header Card -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="w-4 h-4 text-[#00D084]" />
                    <h3 class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17]">Rule / Aturan</h3>
                </div>
                <Info
                    class="w-3.5 h-3.5 dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#F5F5F5] hover:text-[#171B17] cursor-pointer transition-colors"
                    title="Klasifikasi EWS & ambang batas metrik geospasial peternakan Banyumas"
                />
            </div>

            <!-- Category Tab Chips -->
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    @click="activeRuleTab = 'Populasi'"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer"
                    :class="activeRuleTab === 'Populasi'
                        ? 'bg-[#171B17] text-white font-bold shadow-sm border border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#00D084]/40'
                        : 'bg-white text-[#444C43] border border-[#E0E4DD] hover:bg-[#EDEFEB] hover:text-[#171B17] dark:bg-[#181818] dark:border-[#1E1E1E] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]'"
                >
                    Populasi
                </button>
                <button
                    type="button"
                    @click="activeRuleTab = 'Penyakit'"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer"
                    :class="activeRuleTab === 'Penyakit'
                        ? 'bg-[#171B17] text-white font-bold shadow-sm border border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#00D084]/40'
                        : 'bg-white text-[#444C43] border border-[#E0E4DD] hover:bg-[#EDEFEB] hover:text-[#171B17] dark:bg-[#181818] dark:border-[#1E1E1E] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]'"
                >
                    Penyakit
                </button>
                <button
                    type="button"
                    @click="activeRuleTab = 'Fasilitas'"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer"
                    :class="activeRuleTab === 'Fasilitas'
                        ? 'bg-[#171B17] text-white font-bold shadow-sm border border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#00D084]/40'
                        : 'bg-white text-[#444C43] border border-[#E0E4DD] hover:bg-[#EDEFEB] hover:text-[#171B17] dark:bg-[#181818] dark:border-[#1E1E1E] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]'"
                >
                    Fasilitas
                </button>
            </div>

            <!-- Content Tab: Populasi -->
            <div v-if="activeRuleTab === 'Populasi'" class="space-y-2.5">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wider dark:text-[#B3B3B3] text-[#6E776D] mb-1.5">
                        Kondisi Populasi Ternak (ekor)
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between py-0.5">
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] font-mono text-[11px]">&gt; 1.000</span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#D9784D]"></span>
                                <span class="text-[#D9784D] font-medium text-[11px]">Sangat Tinggi</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] font-mono text-[11px]">501 - 1.000</span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#D4A34E]"></span>
                                <span class="text-[#D4A34E] font-medium text-[11px]">Tinggi</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] font-mono text-[11px]">201 - 500</span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#86A65A]"></span>
                                <span class="text-[#86A65A] font-medium text-[11px]">Sedang</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] font-mono text-[11px]">51 - 200</span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#38A69A]"></span>
                                <span class="text-[#38A69A] font-medium text-[11px]">Rendah</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] font-mono text-[11px]">1 - 50</span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#3B82A0]"></span>
                                <span class="text-[#3B82A0] font-medium text-[11px]">Sangat Rendah</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Status Wilayah (Penyakit) -->
                <div class="pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD]">
                    <div class="text-[10px] font-semibold uppercase tracking-wider dark:text-[#B3B3B3] text-[#6E776D] mb-1.5">
                        Status Wilayah (Penyakit)
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs py-0.5">
                            <div class="flex items-center gap-1.5">
                                <AlertTriangle class="w-3.5 h-3.5 text-[#DC625F] fill-[#DC625F]/20" />
                                <span class="text-[#DC625F] font-medium text-[11px]">Zona Merah</span>
                            </div>
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px]">Waspada tinggi</span>
                        </div>
                        <div class="flex items-center justify-between text-xs py-0.5">
                            <div class="flex items-center gap-1.5">
                                <AlertTriangle class="w-3.5 h-3.5 text-[#E8B84C] fill-[#E8B84C]/20" />
                                <span class="text-[#E8B84C] font-medium text-[11px]">Zona Kuning</span>
                            </div>
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px]">Perlu perhatian</span>
                        </div>
                        <div class="flex items-center justify-between text-xs py-0.5">
                            <div class="flex items-center gap-1.5">
                                <CheckCircle2 class="w-3.5 h-3.5 text-[#20B88A]" />
                                <span class="text-[#20B88A] font-medium text-[11px]">Zona Hijau</span>
                            </div>
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px]">Aman</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Tab: Penyakit -->
            <div v-else-if="activeRuleTab === 'Penyakit'" class="space-y-2 text-xs">
                <div class="text-[10px] font-semibold uppercase tracking-wider dark:text-[#B3B3B3] text-[#6E776D]">
                    Protokol EWS PMK &amp; LSD
                </div>
                <div class="p-2.5 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] space-y-1.5">
                    <div class="flex items-center gap-1.5 text-[#DC625F] font-semibold text-[11px]">
                        <AlertTriangle class="w-3.5 h-3.5" />
                        <span>Karantina &amp; Pengetatan Pasar</span>
                    </div>
                    <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed">
                        Wilayah zona merah dengan kasus aktif PMK &gt; 5 diwajibkan desinfeksi berkala dan pembatasan lalu lintas ternak antar kecamatan.
                    </p>
                </div>
            </div>

            <!-- Content Tab: Fasilitas -->
            <div v-else class="space-y-2 text-xs">
                <div class="text-[10px] font-semibold uppercase tracking-wider dark:text-[#B3B3B3] text-[#6E776D]">
                    Standar Layanan Poskeswan / RPH
                </div>
                <div class="p-2.5 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] space-y-1.5">
                    <div class="flex items-center gap-1.5 text-[#00D084] font-semibold text-[11px]">
                        <Building2 class="w-3.5 h-3.5" />
                        <span>Cakupan Wilayah Satelit</span>
                    </div>
                    <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed">
                        1 unit Poskeswan mengampu maksimal 3 kecamatan berdekatan dengan dokter hewan dinas siaga 24 jam.
                    </p>
                </div>
            </div>

            <!-- Footer link -->
            <button
                type="button"
                @click="$emit('openRules')"
                class="w-full pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD] text-[11px] text-[#00D084] hover:text-[#00F0A0] font-semibold flex items-center justify-between cursor-pointer transition-colors"
            >
                <span>Lihat detail rule lainnya</span>
                <ArrowRight class="w-3.5 h-3.5" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #1E1E1E;
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #00D084;
}
</style>
