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
    ChevronLeft,
    ChevronRight,
    Leaf
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
    const totalTernak = (k.sapi_potong || 0) + (k.sapi_perah || 0) + Math.round(((k.kambing || 0) + (k.domba || 0) + (k.kambing_perah || 0)) / 2.5);
    return Math.max(120, Math.round(totalTernak / 4));
});

// Nilai metrik aktif
const activeValue = computed(() => {
    const k = props.kecamatan;
    const mId = props.selectedMetric.id;
    if (mId === 'populasi_total') {
        return (
            k.populasi_total ??
            ((k.sapi_potong || 0) +
                (k.sapi_perah || 0) +
                (k.kambing || 0) +
                (k.domba || 0) +
                (k.kambing_perah || 0) +
                (k.ayam_potong || 0) +
                (k.ayam_petelur || 0))
        );
    }
    if (mId === 'kasus_total') {
        return k.kasus_total ?? ((k.pmk || 0) + (k.lsd || 0));
    }
    if (mId === 'produksi_total') {
        return (
            k.produksi_total ??
            ((k.telur_ayam || 0) +
                (k.daging_ayam || 0) +
                (k.daging_sapi || 0) +
                (k.daging_kambing || 0) +
                (k.daging_domba || 0) +
                (k.susu_sapi || 0) +
                (k.susu_kambing || 0) +
                (k.susu_domba || 0))
        );
    }
    if (mId === 'fasilitas_total') {
        return k.fasilitas_total ?? ((k.poskeswan || 0) + (k.rph || 0));
    }
    return Number(k[mId]) || 0;
});

function getDesaMetricValue(d: DesaData): number {
    const mId = props.selectedMetric.id;
    if (mId === 'populasi_total') {
        return (
            (d.sapi_potong || 0) +
            (d.sapi_perah || 0) +
            (d.kambing || 0) +
            (d.domba || 0) +
            (d.ayam_potong || 0) +
            (d.ayam_petelur || 0)
        );
    }
    if (mId === 'kasus_total') {
        return (d.pmk || 0) + (d.lsd || 0);
    }
    if (mId === 'produksi_total') {
        return (
            (d.telur_ayam || 0) +
            (d.daging_ayam || 0) +
            (d.daging_sapi || 0) +
            (d.daging_kambing || 0) +
            (d.daging_domba || 0) +
            (d.susu_sapi || 0) +
            (d.susu_kambing || 0) +
            (d.susu_domba || 0)
        );
    }
    if (mId === 'fasilitas_total') {
        return (d.poskeswan || 0) + ((d as any).rph || 0);
    }
    return Number(d[mId as keyof DesaData] || 0);
}

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

// Ringkasan 3 Kategori Komoditas (Ternak Besar, Ternak Kecil, Unggas)
const categoryBreakdown = computed(() => {
    const k = props.kecamatan;
    const besar = (k.sapi_potong || 0) + (k.sapi_perah || 0);
    const kecil = (k.kambing || 0) + (k.domba || 0) + (k.kambing_perah || 0);
    const unggas = (k.ayam_potong || 0) + (k.ayam_petelur || 0);
    const kambingPerah = k.kambing_perah || 0;

    return {
        besar,
        kecil,
        unggas,
        kambingPerah
    };
});

// Analisis Daya Dukung Pakan (Carrying Capacity)
const carryingCapacity = computed(() => {
    const k = props.kecamatan;
    const sapi = (k.sapi_potong || 0) + (k.sapi_perah || 0);
    const kecil = (k.kambing || 0) + (k.domba || 0) + (k.kambing_perah || 0);
    const unggas = (k.ayam_potong || 0) + (k.ayam_petelur || 0);

    const stSapi = sapi * 1.0;
    const stKecil = kecil * 0.14;
    const stUnggas = unggas * 0.01;
    const totalST = Number((stSapi + stKecil + stUnggas).toFixed(1));

    const luasHmt = k.luas_hmt_ha || 0;
    const kapasitasTampungST = Number((luasHmt * 6).toFixed(1));

    const indeks = totalST > 0 ? Number((kapasitasTampungST / totalST).toFixed(2)) : 1.0;

    let status = 'Surplus Pakan';
    let statusClass = 'text-emerald-500 bg-emerald-500/10 border-emerald-500/30';
    let dotClass = 'bg-emerald-500';

    if (indeks < 0.7) {
        status = 'Defisit Pakan';
        statusClass = 'text-rose-500 bg-rose-500/10 border-rose-500/30';
        dotClass = 'bg-rose-500';
    } else if (indeks < 1.0) {
        status = 'Waspada Pakan';
        statusClass = 'text-amber-500 bg-amber-500/10 border-amber-500/30';
        dotClass = 'bg-amber-500';
    }

    return {
        totalST,
        luasHmt,
        kapasitasTampungST,
        indeks,
        status,
        statusClass,
        dotClass
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
                        <span class="w-2 h-2 rounded-full dark:bg-[#00D084] bg-[#171B17] inline-block"></span>
                        <span class="text-xs font-semibold tracking-normal dark:text-[#B3B3B3] text-[#6E776D]">
                            Detail Wilayah
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="$emit('drillDown', kecamatan)"
                            class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] dark:hover:text-[#00D084] hover:text-[#171B17] dark:hover:border-[#00D084]/40 hover:border-[#171B17] flex items-center justify-center transition-colors cursor-pointer"
                            title="Buka Rincian Lengkap"
                            aria-label="Buka Rincian Lengkap"
                        >
                            <ExternalLink class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:text-[#DC625F] dark:hover:border-[#DC625F]/40 flex items-center justify-center transition-colors cursor-pointer"
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
                        <span class="dark:text-[#00D084] text-[#171B17] font-semibold">
                            {{ selectedMetric.label }}
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between mt-0.5">
                        <div class="text-lg font-bold font-mono dark:text-[#00D084] text-[#171B17] tabular-nums">
                            {{ formatAngka(activeValue) }}
                            <span class="text-xs font-normal dark:text-[#B3B3B3] text-[#6E776D]">{{ selectedMetric.satuan }}</span>
                        </div>
                        <span v-if="currentRole === 'publik'" class="text-[10px] px-1.5 py-0.5 rounded dark:bg-[#00D084]/10 bg-[#171B17]/10 dark:text-[#00D084] text-[#171B17] font-medium">
                            Publik
                        </span>
                    </div>

                    <div class="text-[11px] dark:text-[#39D6C0] text-[#171B17] font-medium flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full dark:bg-[#39D6C0] bg-[#171B17]"></span>
                        <span>{{ qualitativeStatus.label }}</span>
                    </div>
                </div>

                <!-- Ringkasan 3 Kategori Komoditas (Tanpa Dominasi Skala Unggas) -->
                <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex flex-col gap-2">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-semibold dark:text-[#F5F5F5] text-[#171B17] flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full dark:bg-[#00D084] bg-[#171B17]"></span>
                            Struktur Populasi Ternak
                        </span>
                        <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D]">3 Kategori</span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 text-center">
                        <!-- Ternak Besar -->
                        <div class="p-2 rounded-lg dark:bg-[#121212] bg-white border dark:border-[#222222] border-[#E5E7EB] flex flex-col items-center">
                            <span class="text-[10px] font-medium text-emerald-600 dark:text-[#00D084] truncate w-full">Ruminansia Besar</span>
                            <span class="text-xs font-bold font-mono dark:text-[#F5F5F5] text-[#171B17] tabular-nums mt-0.5">
                                {{ formatAngka(categoryBreakdown.besar) }}
                            </span>
                            <span class="text-[9px] dark:text-[#888888] text-[#888888]">Sapi (ekor)</span>
                        </div>

                        <!-- Ternak Kecil -->
                        <div class="p-2 rounded-lg dark:bg-[#121212] bg-white border dark:border-[#222222] border-[#E5E7EB] flex flex-col items-center">
                            <span class="text-[10px] font-medium text-cyan-600 dark:text-cyan-400 truncate w-full">Ruminansia Kecil</span>
                            <span class="text-xs font-bold font-mono dark:text-[#F5F5F5] text-[#171B17] tabular-nums mt-0.5">
                                {{ formatAngka(categoryBreakdown.kecil) }}
                            </span>
                            <span class="text-[9px] dark:text-[#888888] text-[#888888]">Kamb/Dmb (ekor)</span>
                            <span v-if="categoryBreakdown.kambingPerah > 0" class="text-[8px] text-amber-500 font-mono mt-0.5 truncate w-full">
                                Perah: {{ formatAngka(categoryBreakdown.kambingPerah) }}
                            </span>
                        </div>

                        <!-- Unggas -->
                        <div class="p-2 rounded-lg dark:bg-[#121212] bg-white border dark:border-[#222222] border-[#E5E7EB] flex flex-col items-center">
                            <span class="text-[10px] font-medium text-amber-600 dark:text-amber-400 truncate w-full">Unggas</span>
                            <span class="text-xs font-bold font-mono dark:text-[#F5F5F5] text-[#171B17] tabular-nums mt-0.5">
                                {{ formatAngka(categoryBreakdown.unggas) }}
                            </span>
                            <span class="text-[9px] dark:text-[#888888] text-[#888888]">Ayam (ekor)</span>
                        </div>
                    </div>
                </div>

                <!-- Status Daya Dukung Pakan Hijauan (Carrying Capacity) -->
                <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <div class="flex items-center gap-1.5">
                            <Leaf class="w-3.5 h-3.5 text-emerald-500" />
                            <span class="font-semibold dark:text-[#F5F5F5] text-[#171B17]">Daya Dukung Pakan</span>
                        </div>
                        <div
                            class="px-2 py-0.5 rounded-full border text-[10px] font-semibold flex items-center gap-1"
                            :class="carryingCapacity.statusClass"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="carryingCapacity.dotClass"></span>
                            <span>{{ carryingCapacity.status }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-1.5 mt-0.5">
                        <div class="p-1.5 rounded-lg dark:bg-[#121212] bg-white border dark:border-[#222222] border-[#E5E7EB]">
                            <div class="text-[10px] dark:text-[#888888] text-[#6E776D]">Kebun HMT</div>
                            <div class="text-xs font-bold font-mono dark:text-[#F5F5F5] text-[#171B17] tabular-nums">
                                {{ carryingCapacity.luasHmt.toLocaleString('id-ID') }} <span class="text-[10px] font-normal font-sans">Ha</span>
                            </div>
                        </div>
                        <div class="p-1.5 rounded-lg dark:bg-[#121212] bg-white border dark:border-[#222222] border-[#E5E7EB]">
                            <div class="text-[10px] dark:text-[#888888] text-[#6E776D]">Beban Ternak</div>
                            <div class="text-xs font-bold font-mono dark:text-[#F5F5F5] text-[#171B17] tabular-nums">
                                {{ formatAngka(carryingCapacity.totalST) }} <span class="text-[10px] font-normal font-sans">ST</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] flex items-center justify-between pt-1 border-t dark:border-[#222222] border-[#E5E7EB]">
                        <span>Kapasitas Lahan (6 ST/Ha):</span>
                        <span class="font-mono font-semibold dark:text-[#F5F5F5] text-[#171B17]">{{ formatAngka(carryingCapacity.kapasitasTampungST) }} ST</span>
                    </div>
                </div>

                <!-- Stat Grid: Desa button + 2-col metrics (Peternak, Luas Wilayah) -->
                <div class="flex flex-col gap-2">
                    <!-- Desa -->
                    <button
                        type="button"
                        @click="showDesaView = true"
                        class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:border-[#171B17] dark:hover:border-[#00D084]/50 rounded-xl px-3 py-2 flex items-center justify-between transition-colors cursor-pointer group"
                        title="Klik untuk melihat daftar desa"
                    >
                        <div class="flex items-center gap-2">
                            <Building2 class="w-4 h-4 dark:text-[#00D084] text-[#171B17]" />
                            <span class="text-xs font-medium dark:text-[#B3B3B3] text-[#6E776D]">Daftar Desa / Kelurahan</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums">
                                {{ desaList.length }} Desa
                            </span>
                            <ChevronRight class="w-4 h-4 dark:text-[#00D084] text-[#171B17]" />
                        </div>
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        <!-- Peternak -->
                        <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex flex-col items-center text-center">
                            <Users class="w-4 h-4 dark:text-[#39D6C0] text-[#171B17] mb-1" />
                            <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums leading-tight">
                                {{ formatAngka(estimatedFarmers) }}
                            </span>
                            <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">Peternak</span>
                        </div>

                        <!-- Luas Wilayah -->
                        <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex flex-col items-center text-center">
                            <MapPin class="w-4 h-4 dark:text-[#39D6C0] text-[#171B17] mb-1" />
                            <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums leading-tight">
                                {{ formattedArea }}
                            </span>
                            <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">Luas Wilayah</span>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="$emit('drillDown', kecamatan)"
                    class="w-full py-2.5 px-3 rounded-xl dark:bg-[#00D084] dark:text-[#062117] dark:hover:bg-[#00F0A0] dark:active:bg-[#00D084] bg-[#171B17] text-white hover:bg-[#282828] active:opacity-90 text-xs font-bold flex items-center justify-center gap-1.5 shadow-md cursor-pointer transition-colors"
                >
                    <span>Lihat Detail Lengkap</span>
                    <ArrowRight class="w-4 h-4" />
                </button>
            </template>

            <!-- View Mode: Daftar Desa (Bila diklik dari box Desa) -->
            <template v-else>
                <div class="flex items-center justify-between pb-2 border-b dark:border-[#1E1E1E] border-[#E0E4DD]">
                    <button
                        type="button"
                        @click="showDesaView = false"
                        class="text-xs dark:text-[#B3B3B3] text-[#6E776D] dark:hover:text-[#00D084] hover:text-[#171B17] flex items-center gap-1 transition-colors cursor-pointer"
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
                        class="w-full h-8 dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] focus:border-[#171B17] dark:focus:border-[#00D084] rounded-lg px-2.5 pr-7 text-xs dark:text-[#F5F5F5] text-[#171B17] outline-none placeholder:text-[#858585]"
                    />
                    <Search class="w-4 h-4 dark:text-[#B3B3B3] text-[#6E776D] absolute right-2.5 top-2 pointer-events-none" />
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
                            <div class="text-xs font-semibold dark:text-[#F5F5F5] text-[#171B17] dark:group-hover:text-[#00D084] group-hover:text-black truncate flex items-center gap-1.5">
                                <CheckCircle2 v-if="selectedDesaId === d.id" class="w-3.5 h-3.5 dark:text-[#00D084] text-[#171B17] shrink-0" />
                                <span class="truncate">{{ d.nama }}</span>
                            </div>
                            <div class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] font-mono">
                                {{ d.jenis }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold font-mono dark:text-[#00D084] text-[#171B17] tabular-nums">
                                {{ formatAngka(getDesaMetricValue(d)) }}
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
                    <ShieldCheck class="w-4 h-4 dark:text-[#00D084] text-[#171B17]" />
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
                    <div class="text-xs font-semibold tracking-normal dark:text-[#B3B3B3] text-[#6E776D] mb-1.5">
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
                    <div class="text-xs font-semibold tracking-normal dark:text-[#B3B3B3] text-[#6E776D] mb-1.5">
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
                                <CheckCircle2 class="w-3.5 h-3.5 dark:text-emerald-400 text-[#171B17]" />
                                <span class="dark:text-emerald-400 text-[#171B17] font-medium text-[11px]">Zona Hijau</span>
                            </div>
                            <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px]">Aman</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Tab: Penyakit -->
            <div v-else-if="activeRuleTab === 'Penyakit'" class="space-y-2 text-xs">
                <div class="text-xs font-semibold tracking-normal dark:text-[#B3B3B3] text-[#6E776D]">
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
                <div class="text-xs font-semibold tracking-normal dark:text-[#B3B3B3] text-[#6E776D]">
                    Standar Layanan Poskeswan / RPH
                </div>
                <div class="p-2.5 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] space-y-1.5">
                    <div class="flex items-center gap-1.5 dark:text-[#00D084] text-[#171B17] font-semibold text-[11px]">
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
                class="w-full pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD] text-[11px] dark:text-[#00D084] dark:hover:text-[#00F0A0] text-[#171B17] hover:text-black font-semibold flex items-center justify-between cursor-pointer transition-colors"
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
    background: #171B17;
}
:root.dark .custom-scroll::-webkit-scrollbar-thumb:hover,
.dark .custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #00D084;
}
</style>
