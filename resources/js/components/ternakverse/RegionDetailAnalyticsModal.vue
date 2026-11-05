<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { KecamatanData, MetricDefinition } from '../../data/banyumasData';
import { formatAngka, calculateQuantiles, getClassLevel, KECAMATAN_LIST } from '../../data/banyumasData';
import { getDesaByKecamatan, getDesaStatus, type DesaData } from '../../data/banyumasDesaData';
import {
    X,
    Building2,
    Users,
    MapPin,
    ShieldAlert,
    ShieldCheck,
    Download,
    Pencil,
    Search,
    Layers,
    Globe,
    Info,
    AlertTriangle,
    CheckCircle2,
    ArrowRight,
    Activity,
    FileSpreadsheet,
    Lock,
    ExternalLink,
    Leaf
} from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    isOpen: boolean;
    kecamatan: KecamatanData | null;
    selectedMetric: MetricDefinition;
    currentRole?: 'publik' | 'dinas' | 'admin';
}>(), {
    currentRole: 'publik'
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'editRegion', kec: KecamatanData): void;
    (e: 'focusDesa', desa: DesaData): void;
}>();

// Luas Wilayah resmi (km²)
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

// State Tab Internal
const activeTab = ref<'ringkasan' | 'desa' | 'surveilans' | 'spasial'>('ringkasan');
const searchDesaQuery = ref('');
const filterDesaZona = ref<'all' | 'bahaya' | 'waspada' | 'aman'>('all');

// Daftar Desa di Kecamatan ini
const desaList = computed<DesaData[]>(() => {
    if (!props.kecamatan) return [];
    return getDesaByKecamatan(props.kecamatan.id);
});

// Luas Wilayah numerik
const numericArea = computed<number>(() => {
    if (!props.kecamatan) return 50;
    const kId = props.kecamatan.id.toLowerCase();
    const str = KECAMATAN_LUAS[kId];
    if (str) {
        return parseFloat(str.replace(' km²', '').replace(',', '.'));
    }
    return Math.max(10, desaList.value.length * 3.65);
});

const formattedArea = computed<string>(() => {
    if (!props.kecamatan) return '0 km²';
    const kId = props.kecamatan.id.toLowerCase();
    if (KECAMATAN_LUAS[kId]) return KECAMATAN_LUAS[kId];
    return `${numericArea.value.toFixed(2).replace('.', ',')} km²`;
});

// Estimasi peternak resmi terdata
const estimatedFarmers = computed<number>(() => {
    if (!props.kecamatan) return 0;
    const k = props.kecamatan;
    const totalTernak = (k.sapi_potong || 0) + (k.sapi_perah || 0) + Math.round(((k.kambing || 0) + (k.domba || 0)) / 2.5);
    return Math.max(120, Math.round(totalTernak / 4));
});

// Total Populasi Ternak Semua Komoditas
const totalPopulasi = computed<number>(() => {
    if (!props.kecamatan) return 0;
    const k = props.kecamatan;
    return (
        (k.sapi_potong || 0) +
        (k.sapi_perah || 0) +
        (k.kambing || 0) +
        (k.kambing_perah || 0) +
        (k.domba || 0) +
        (k.ayam_potong || 0) +
        (k.ayam_petelur || 0)
    );
});

// Densitas Ternak per km²
const animalDensity = computed<number>(() => {
    if (numericArea.value <= 0) return 0;
    return Math.round(totalPopulasi.value / numericArea.value);
});

// Total Ruminansia Besar & Kecil
const totalRuminansia = computed<number>(() => {
    if (!props.kecamatan) return 0;
    const k = props.kecamatan;
    return (
        (k.sapi_potong || 0) +
        (k.sapi_perah || 0) +
        (k.kambing || 0) +
        (k.kambing_perah || 0) +
        (k.domba || 0)
    );
});

const totalUnggas = computed<number>(() => {
    if (!props.kecamatan) return 0;
    const k = props.kecamatan;
    return (k.ayam_potong || 0) + (k.ayam_petelur || 0);
});

// 3 Kategori Mandiri (Eliminasi Dominasi Skala Unggas)
const categoryGroups = computed(() => {
    if (!props.kecamatan) return null;
    const k = props.kecamatan;

    // A. Ternak Ruminansia Besar
    const sapiPotong = k.sapi_potong || 0;
    const sapiPerah = k.sapi_perah || 0;
    const totalBesar = sapiPotong + sapiPerah;
    const besarItems = [
        {
            label: 'Sapi Potong',
            sublabel: 'Simmental, Limousin, PO (Pedaging)',
            value: sapiPotong,
            percentage: totalBesar > 0 ? ((sapiPotong / totalBesar) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#00D084] bg-[#171B17]',
            textColor: 'dark:text-[#00D084] text-[#171B17]'
        },
        {
            label: 'Sapi Perah',
            sublabel: 'Friesian Holstein (Penghasil Susu)',
            value: sapiPerah,
            percentage: totalBesar > 0 ? ((sapiPerah / totalBesar) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#39D6C0] bg-[#0E7490]',
            textColor: 'dark:text-[#39D6C0] text-[#0E7490]'
        }
    ];

    // B. Ternak Ruminansia Kecil
    const kambingPotong = k.kambing || 0;
    const kambingPerah = k.kambing_perah || 0;
    const domba = k.domba || 0;
    const totalKecil = kambingPotong + kambingPerah + domba;
    const kecilItems = [
        {
            label: 'Kambing Potong',
            sublabel: 'Jawa Randu / Kacang (Pedaging)',
            value: kambingPotong,
            percentage: totalKecil > 0 ? ((kambingPotong / totalKecil) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#20D6A0] bg-[#0F766E]',
            textColor: 'dark:text-[#20D6A0] text-[#0F766E]'
        },
        {
            label: 'Kambing Perah',
            sublabel: 'Saanen, Sapera, PE (Penghasil Susu)',
            value: kambingPerah,
            percentage: totalKecil > 0 ? ((kambingPerah / totalKecil) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#38BDF8] bg-[#0284C7]',
            textColor: 'dark:text-[#38BDF8] text-[#0284C7]'
        },
        {
            label: 'Domba',
            sublabel: 'Batur / Ekor Gemuk / Garut',
            value: domba,
            percentage: totalKecil > 0 ? ((domba / totalKecil) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#FBBF24] bg-[#D97706]',
            textColor: 'dark:text-[#FBBF24] text-[#D97706]'
        }
    ];

    // C. Unggas
    const ayamPotong = k.ayam_potong || 0;
    const ayamPetelur = k.ayam_petelur || 0;
    const totalUnggasVal = ayamPotong + ayamPetelur;
    const unggasItems = [
        {
            label: 'Ayam Potong / Broiler',
            sublabel: 'Ayam ras pedaging komersial',
            value: ayamPotong,
            percentage: totalUnggasVal > 0 ? ((ayamPotong / totalUnggasVal) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#FB923C] bg-[#EA580C]',
            textColor: 'dark:text-[#FB923C] text-[#EA580C]'
        },
        {
            label: 'Ayam Petelur / Layer',
            sublabel: 'Ayam ras produksi konsumsi telur',
            value: ayamPetelur,
            percentage: totalUnggasVal > 0 ? ((ayamPetelur / totalUnggasVal) * 100).toFixed(1) : '0.0',
            barColor: 'dark:bg-[#FACC15] bg-[#CA8A04]',
            textColor: 'dark:text-[#FACC15] text-[#CA8A04]'
        }
    ];

    return {
        besar: {
            title: 'Ternak Ruminansia Besar',
            badge: 'Bovidae Besar',
            total: totalBesar,
            items: besarItems
        },
        kecil: {
            title: 'Ternak Ruminansia Kecil',
            badge: 'Kambing & Domba',
            total: totalKecil,
            items: kecilItems
        },
        unggas: {
            title: 'Unggas Komersial',
            badge: 'Aves Pedaging & Petelur',
            total: totalUnggasVal,
            items: unggasItems
        }
    };
});

// Dedicated Status Daya Dukung Pakan (Carrying Capacity)
const carryingCapacity = computed(() => {
    if (!props.kecamatan) return null;
    const k = props.kecamatan;

    const sapi = (k.sapi_potong || 0) + (k.sapi_perah || 0);
    const kecil = (k.kambing || 0) + (k.domba || 0) + (k.kambing_perah || 0);
    const unggas = (k.ayam_potong || 0) + (k.ayam_petelur || 0);

    const stSapi = sapi * 1.0;
    const stKecil = kecil * 0.14;
    const stUnggas = unggas * 0.01;
    const totalST = Number((stSapi + stKecil + stUnggas).toFixed(1));
    const stRuminansia = Number((stSapi + stKecil).toFixed(1));

    const luasHmt = k.luas_hmt_ha || 0;
    const kapasitasTampungST = Number((luasHmt * 6).toFixed(1));
    const indeks = totalST > 0 ? Number((kapasitasTampungST / totalST).toFixed(2)) : 1.0;

    let statusLabel = 'Surplus Pakan Hijauan';
    let statusDesc = 'Kapasitas lahan pakan mencukupi seluruh kebutuhan ternak wilayah';
    let badgeClass = 'bg-[#20D6A0]/15 border-[#20D6A0]/40 text-[#20D6A0] dark:bg-[#00D084]/15 dark:border-[#00D084]/40 dark:text-[#00D084]';
    let dotClass = 'bg-[#20D6A0] dark:bg-[#00D084]';
    let indicatorColor = 'text-emerald-500 dark:text-[#00D084]';

    if (indeks < 0.7) {
        statusLabel = 'Defisit Pakan (Perlu Suplai Eksternal / Konsentrat)';
        statusDesc = 'Daya tampung lahan pakan hijauan di bawah kebutuhan populasi, membutuhkan suplai konsentrat atau pakan dari luar wilayah';
        badgeClass = 'bg-[#DC625F]/15 border-[#DC625F]/40 text-[#DC625F] dark:bg-rose-500/15 dark:border-rose-500/40 dark:text-rose-400';
        dotClass = 'bg-[#DC625F] dark:bg-rose-400';
        indicatorColor = 'text-rose-500 dark:text-rose-400';
    } else if (indeks < 1.0) {
        statusLabel = 'Waspada / Daya Tampung Pas-pasan';
        statusDesc = 'Daya tampung pakan mendekati batas optimal, disarankan peremajaan kebun rumput unggul dan pemanfaatan limbah pertanian';
        badgeClass = 'bg-[#E8B84C]/15 border-[#E8B84C]/40 text-[#E8B84C] dark:bg-amber-500/15 dark:border-amber-500/40 dark:text-amber-400';
        dotClass = 'bg-[#E8B84C] dark:bg-amber-400';
        indicatorColor = 'text-amber-500 dark:text-amber-400';
    }

    return {
        totalST,
        stRuminansia,
        stSapi,
        stKecil,
        stUnggas,
        luasHmt,
        kapasitasTampungST,
        indeks,
        statusLabel,
        statusDesc,
        badgeClass,
        dotClass,
        indicatorColor
    };
});

// Status Zonasi
const zonaConfig = computed(() => {
    if (!props.kecamatan) {
        return { label: 'Aman', badgeClass: 'bg-[#20D6A0]/15 border-[#20D6A0]/40 text-[#20D6A0]', dotClass: 'bg-[#20D6A0]' };
    }
    switch (props.kecamatan.zona) {
        case 'bahaya':
            return {
                label: 'Zona Bahaya',
                badgeClass: 'bg-[#FB536B]/15 border-[#FB536B]/40 text-[#FB536B]',
                dotClass: 'bg-[#FB536B]'
            };
        case 'waspada':
            return {
                label: 'Zona Waspada',
                badgeClass: 'bg-[#FBBF24]/15 border-[#FBBF24]/40 text-[#FBBF24]',
                dotClass: 'bg-[#FBBF24]'
            };
        default:
            return {
                label: 'Zona Bebas / Aman',
                badgeClass: 'bg-[#20D6A0]/15 border-[#20D6A0]/40 text-[#20D6A0]',
                dotClass: 'bg-[#20D6A0]'
            };
    }
});

// Status Kualitatif Publik
const qualitativeStatus = computed(() => {
    if (!props.kecamatan) return { level: 3, label: 'Kategori Sedang' };
    const val = Number(props.kecamatan[props.selectedMetric.id]) || 0;
    const quantiles = calculateQuantiles(KECAMATAN_LIST, props.selectedMetric.id);
    const lvl = getClassLevel(val, quantiles);
    const labels = [
        'Kategori Sangat Rendah',
        'Kategori Rendah',
        'Kategori Sedang',
        'Kategori Tinggi',
        'Sentra Unggulan Utama'
    ];
    return {
        level: lvl,
        label: labels[lvl - 1] || 'Kategori Sedang'
    };
});

// Filter Desa
const filteredDesaList = computed(() => {
    return desaList.value.filter(desa => {
        const matchesQuery = !searchDesaQuery.value.trim() ||
            desa.nama.toLowerCase().includes(searchDesaQuery.value.toLowerCase().trim()) ||
            desa.kode_kd.includes(searchDesaQuery.value.trim());

        const desaZona = getDesaStatus(desa);

        const matchesZona = filterDesaZona.value === 'all' || desaZona === filterDesaZona.value;
        return matchesQuery && matchesZona;
    });
});

// Helper EWS status per Desa
function getDesaEwsStatus(desa: DesaData) {
    const status = getDesaStatus(desa);
    if (status === 'bahaya') {
        return { label: 'Bahaya', badgeClass: 'bg-rose-500/15 border-rose-500/30 text-rose-400' };
    }
    if (status === 'waspada') {
        return { label: 'Waspada', badgeClass: 'bg-amber-500/15 border-amber-500/30 text-amber-400' };
    }
    return { label: 'Aman', badgeClass: 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' };
}

function sanitizeCsvCell(val: unknown): string {
    const str = String(val ?? '');
    const escaped = str.replace(/"/g, '""');
    if (/^[=+\-@\t\r]/.test(escaped)) {
        return `"'${escaped}"`;
    }
    return `"${escaped}"`;
}

// Client-side UTF-8 CSV Export
function exportRegionCsv() {
    if (!props.kecamatan) return;
    const k = props.kecamatan;
    const desas = desaList.value;

    const headers = [
        'Tingkat Administrasi',
        'Kode BPS / Kemendagri',
        'Nama Wilayah',
        'Tipe Wilayah',
        'Status Zonasi EWS',
        'Sapi Potong (ekor)',
        'Sapi Perah (ekor)',
        'Kambing Potong (ekor)',
        'Kambing Perah (ekor)',
        'Domba (ekor)',
        'Ayam Potong (ekor)',
        'Ayam Petelur (ekor)',
        'Kasus Aktif PMK',
        'Kasus Aktif LSD',
        'Fasilitas Poskeswan / RPH',
        'Luas Kebun HMT (Ha)',
        'Latitude Centroid',
        'Longitude Centroid'
    ];

    const rows: (string | number)[][] = [];

    // Baris Agregat Kecamatan
    rows.push([
        'Kecamatan',
        k.kode_kec,
        k.nama,
        'Kecamatan',
        k.zona.toUpperCase(),
        k.sapi_potong || 0,
        k.sapi_perah || 0,
        k.kambing || 0,
        k.kambing_perah || 0,
        k.domba || 0,
        k.ayam_potong || 0,
        k.ayam_petelur || 0,
        k.pmk || 0,
        k.lsd || 0,
        k.poskeswan || 0,
        k.luas_hmt_ha || 0,
        k.lat,
        k.lng
    ]);

    // Baris Tiap Desa / Kelurahan
    for (const d of desas) {
        const ews = getDesaStatus(d).toUpperCase();
        rows.push([
            'Desa / Kelurahan',
            d.kode_kd,
            d.nama,
            d.jenis,
            ews,
            d.sapi_potong || 0,
            d.sapi_perah || 0,
            d.kambing || 0,
            0,
            d.domba || 0,
            d.ayam_potong || 0,
            d.ayam_petelur || 0,
            d.pmk || 0,
            d.lsd || 0,
            d.poskeswan || 0,
            0,
            d.lat,
            d.lng
        ]);
    }

    const csvContent = '\uFEFF' + [
        headers.map(sanitizeCsvCell).join(','),
        ...rows.map(r => r.map(sanitizeCsvCell).join(','))
    ].join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `rekap_spasial_${k.id}_ternakverse.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.isOpen) {
        emit('close');
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <!-- MODAL DIALOG WRAPPER WITH DARK GEOSPATIAL COMMAND CENTER BACKDROP -->
    <div
        v-if="isOpen && kecamatan"
        class="fixed inset-0 z-50 bg-black/60 dark:bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 select-none"
        @click.self="emit('close')"
    >
        <div
            class="dark:bg-[#111111] bg-[#FFFFFF] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in zoom-in-95 duration-150 font-ui dark:text-[#F5F5F5] text-[#171B17] transition-colors"
        >
            <!-- ==================================================== -->
            <!-- MODAL HEADER                                         -->
            <!-- ==================================================== -->
            <header class="p-4 sm:p-5 border-b dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#FFFFFF] shrink-0 transition-colors">
                <div class="flex items-start justify-between gap-3">
                    <!-- Left: Region Title, Code & EWS Zone Badge -->
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full inline-block border border-black/10 dark:border-white/15" :class="zonaConfig.dotClass"></span>
                            <h2 class="text-xl sm:text-2xl font-display font-bold dark:text-white text-[#171B17] tracking-tight leading-tight">
                                Kecamatan {{ kecamatan.nama }}
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold flex items-center gap-1.5" :class="zonaConfig.badgeClass">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                <span>{{ zonaConfig.label }}</span>
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs dark:text-[#B3B3B3] text-[#6E776D] font-mono">
                            <span>Kode BPS: <strong class="dark:text-slate-200 text-[#171B17]">{{ kecamatan.kode_kec }}</strong></span>
                            <span>•</span>
                            <span>Pembaruan: <strong class="dark:text-slate-200 text-[#171B17]">{{ kecamatan.last_updated }}</strong></span>
                            <span>•</span>
                            <span class="px-2 py-0.5 rounded dark:bg-[#063D2D] bg-[#F4F5F2] dark:text-[#00D084] text-[#171B17] font-semibold border dark:border-[#00D084]/30 border-[#E0E4DD]">
                                {{ currentRole === 'admin' ? 'Akses Super Admin' : currentRole === 'dinas' ? 'Akses Dinas Peternakan' : 'Portal Publik' }}
                            </span>
                        </div>
                    </div>

                    <!-- Right: Quick Action & Close Buttons -->
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Ekspor CSV (Dinas & Admin) -->
                        <button
                            v-if="currentRole !== 'publik'"
                            type="button"
                            @click="exportRegionCsv"
                            class="h-9 px-3 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:hover:border-[#00D084]/40 hover:border-[#171B17] dark:text-[#F5F5F5] text-[#171B17] dark:hover:text-[#00D084] hover:text-black text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                            title="Ekspor Data Wilayah (CSV)"
                        >
                            <Download class="w-3.5 h-3.5 dark:text-[#00D084] text-[#171B17]" />
                            <span class="hidden sm:inline">Ekspor CSV</span>
                        </button>

                        <!-- Edit Data Wilayah (Admin Only) -->
                        <button
                            v-if="currentRole === 'admin'"
                            type="button"
                            @click="emit('editRegion', kecamatan); emit('close');"
                            class="h-9 px-3 rounded-xl dark:bg-[#00D084] dark:text-[#062117] hover:dark:bg-[#00F0A0] bg-[#171B17] text-white hover:bg-[#2A312A] font-bold text-xs inline-flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                            title="Edit Data Wilayah Ini"
                        >
                            <Pencil class="w-3.5 h-3.5 stroke-[2.2]" />
                            <span class="hidden sm:inline">Edit Data</span>
                        </button>

                        <!-- Close Button -->
                        <button
                            type="button"
                            @click="emit('close')"
                            class="w-9 h-9 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:text-rose-500 hover:border-rose-500/40 flex items-center justify-center transition-colors cursor-pointer"
                            title="Tutup (Esc)"
                            aria-label="Tutup Detail Wilayah"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- 3-Stat Key Metrics Header Strip -->
                <div class="grid grid-cols-3 gap-2.5 sm:gap-3 mt-4 pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD]">
                    <!-- Luas Wilayah -->
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg dark:bg-[#181818] dark:text-[#00D084] dark:border-[#1E1E1E] bg-[#F4F5F2] text-[#171B17] border border-[#E0E4DD] flex items-center justify-center shrink-0">
                            <MapPin class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] uppercase font-semibold dark:text-[#B3B3B3] text-[#6E776D] block tracking-wider">Luas Wilayah</span>
                            <span class="text-sm sm:text-base font-bold font-mono dark:text-white text-[#171B17] tabular-nums truncate block">
                                {{ formattedArea }}
                            </span>
                        </div>
                    </div>

                    <!-- Total Desa -->
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg dark:bg-[#181818] dark:text-[#00D084] dark:border-[#1E1E1E] bg-[#F4F5F2] text-[#171B17] border border-[#E0E4DD] flex items-center justify-center shrink-0">
                            <Building2 class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] uppercase font-semibold dark:text-[#B3B3B3] text-[#6E776D] block tracking-wider">Total Desa</span>
                            <span class="text-sm sm:text-base font-bold font-mono dark:text-white text-[#171B17] tabular-nums block">
                                {{ desaList.length }} Desa/Kel.
                            </span>
                        </div>
                    </div>

                    <!-- Total Peternak -->
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg dark:bg-[#181818] dark:text-[#00D084] dark:border-[#1E1E1E] bg-[#F4F5F2] text-[#171B17] border border-[#E0E4DD] flex items-center justify-center shrink-0">
                            <Users class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] uppercase font-semibold dark:text-[#B3B3B3] text-[#6E776D] block tracking-wider">Peternak Terdata</span>
                            <span class="text-sm sm:text-base font-bold font-mono dark:text-white text-[#171B17] tabular-nums block">
                                {{ formatAngka(estimatedFarmers) }} Peternak
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Internal Tab Navigation -->
                <div class="flex items-center gap-2 mt-4 pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD] overflow-x-auto">
                    <button
                        type="button"
                        @click="activeTab = 'ringkasan'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap"
                        :class="activeTab === 'ringkasan'
                            ? 'dark:bg-[#063D2D] bg-[#171B17] text-white border dark:border-[#00D084]/40 border-[#171B17] dark:text-[#00D084]'
                            : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#F5F5F5] hover:text-[#171B17]'"
                    >
                        {{ currentRole === 'publik' ? 'Potensi & Komoditas' : 'Ringkasan & Surveilans' }}
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'desa'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'desa'
                            ? 'dark:bg-[#063D2D] bg-[#171B17] text-white border dark:border-[#00D084]/40 border-[#171B17] dark:text-[#00D084]'
                            : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#F5F5F5] hover:text-[#171B17]'"
                    >
                        <span>Daftar Desa ({{ desaList.length }})</span>
                    </button>

                    <button
                        v-if="currentRole !== 'publik'"
                        type="button"
                        @click="activeTab = 'surveilans'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'surveilans'
                            ? 'dark:bg-[#063D2D] bg-[#171B17] text-white border dark:border-[#00D084]/40 border-[#171B17] dark:text-[#00D084]'
                            : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#F5F5F5] hover:text-[#171B17]'"
                    >
                        <ShieldAlert class="w-3.5 h-3.5" />
                        <span>Kesehatan Hewan &amp; EWS</span>
                    </button>

                    <button
                        v-if="currentRole === 'admin'"
                        type="button"
                        @click="activeTab = 'spasial'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'spasial'
                            ? 'dark:bg-[#063D2D] bg-[#171B17] text-white border dark:border-[#00D084]/40 border-[#171B17] dark:text-[#00D084]'
                            : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#F5F5F5] hover:text-[#171B17]'"
                    >
                        <Globe class="w-3.5 h-3.5" />
                        <span>Integritas Geodesi</span>
                    </button>
                </div>
            </header>

            <!-- ==================================================== -->
            <!-- MODAL BODY                                           -->
            <!-- ==================================================== -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 custom-scroll">
                
                <!-- TAB 1: RINGKASAN & KOMODITAS -->
                <div v-if="activeTab === 'ringkasan'" class="space-y-5">
                    
                    <!-- Kategori Sentra Unggulan Callout -->
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Activity class="w-4 h-4 dark:text-[#00D084] text-[#171B17]" />
                                <span class="text-xs font-bold uppercase tracking-wider dark:text-[#F5F5F5] text-[#171B17]">
                                    Status Kluster Spasial Kabupaten Banyumas
                                </span>
                            </div>
                            <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D]">
                                Berdasarkan evaluasi metode Natural Breaks (Kuantil 5 Kelas) resmi Pemerintah Kabupaten Banyumas.
                            </p>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl dark:bg-[#063D2D] dark:text-[#00D084] dark:border-[#00D084]/30 bg-[#F4F5F2] text-[#171B17] border border-[#E0E4DD] font-bold text-xs flex items-center gap-2 shrink-0">
                            <span class="w-2 h-2 rounded-full dark:bg-[#00D084] bg-[#171B17]"></span>
                            <span>{{ qualitativeStatus.label }}</span>
                        </div>
                    </div>

                    <!-- Proporsi & Distribusi Komoditas (3 Kategori Mandiri) -->
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 px-0.5">
                            <div>
                                <h3 class="font-display font-bold text-sm sm:text-base dark:text-white text-[#171B17]">
                                    Proporsi &amp; Distribusi Komoditas Peternakan
                                </h3>
                                <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">
                                    Visualisasi terpisah per kelompok komoditas untuk representasi proporsi yang proporsional tanpa distorsi skala populasi unggas.
                                </p>
                            </div>
                            <span class="text-xs font-mono font-semibold dark:text-[#00D084] text-[#171B17] shrink-0 self-start sm:self-center">
                                Total: {{ formatAngka(totalPopulasi) }} ekor
                            </span>
                        </div>

                        <!-- 3 Cards Grid: Besar, Kecil, Unggas -->
                        <div v-if="categoryGroups" class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                            <!-- Card A: Ternak Ruminansia Besar -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 flex flex-col justify-between space-y-3 shadow-xs">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full dark:bg-[#00D084] bg-[#171B17]"></span>
                                            <h4 class="font-display font-bold text-xs uppercase tracking-wider dark:text-white text-[#171B17]">
                                                {{ categoryGroups.besar.title }}
                                            </h4>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold dark:bg-[#00D084]/15 dark:text-[#00D084] bg-[#171B17]/10 text-[#171B17]">
                                            {{ formatAngka(categoryGroups.besar.total) }} ekor
                                        </span>
                                    </div>
                                    <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D]">
                                        Proporsi komparatif sapi potong dan sapi perah
                                    </p>
                                </div>

                                <div class="space-y-3 pt-1">
                                    <div
                                        v-for="item in categoryGroups.besar.items"
                                        :key="item.label"
                                        class="space-y-1.5"
                                    >
                                        <div class="flex items-start justify-between gap-2 text-xs">
                                            <div class="min-w-0">
                                                <span class="font-semibold dark:text-[#F5F5F5] text-[#171B17] block truncate">
                                                    {{ item.label }}
                                                </span>
                                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">
                                                    {{ item.sublabel }}
                                                </span>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="font-mono font-bold dark:text-white text-[#171B17] block tabular-nums">
                                                    {{ formatAngka(item.value) }} ekor
                                                </span>
                                                <span class="font-mono text-[11px] font-bold" :class="item.textColor">
                                                    {{ item.percentage }}%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="h-2 w-full dark:bg-[#111111] bg-[#E0E4DD] rounded-full overflow-hidden border dark:border-[#1E1E1E] border-[#C8CEC4]">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="item.barColor"
                                                :style="{ width: `${item.percentage}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card B: Ternak Ruminansia Kecil -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 flex flex-col justify-between space-y-3 shadow-xs">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full dark:bg-[#39D6C0] bg-[#0E7490]"></span>
                                            <h4 class="font-display font-bold text-xs uppercase tracking-wider dark:text-white text-[#171B17]">
                                                {{ categoryGroups.kecil.title }}
                                            </h4>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold dark:bg-[#39D6C0]/15 dark:text-[#39D6C0] bg-[#0E7490]/10 text-[#0E7490]">
                                            {{ formatAngka(categoryGroups.kecil.total) }} ekor
                                        </span>
                                    </div>
                                    <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D]">
                                        Kambing potong, kambing perah, dan domba
                                    </p>
                                </div>

                                <div class="space-y-3 pt-1">
                                    <div
                                        v-for="item in categoryGroups.kecil.items"
                                        :key="item.label"
                                        class="space-y-1.5"
                                    >
                                        <div class="flex items-start justify-between gap-2 text-xs">
                                            <div class="min-w-0">
                                                <span class="font-semibold dark:text-[#F5F5F5] text-[#171B17] block truncate">
                                                    {{ item.label }}
                                                </span>
                                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">
                                                    {{ item.sublabel }}
                                                </span>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="font-mono font-bold dark:text-white text-[#171B17] block tabular-nums">
                                                    {{ formatAngka(item.value) }} ekor
                                                </span>
                                                <span class="font-mono text-[11px] font-bold" :class="item.textColor">
                                                    {{ item.percentage }}%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="h-2 w-full dark:bg-[#111111] bg-[#E0E4DD] rounded-full overflow-hidden border dark:border-[#1E1E1E] border-[#C8CEC4]">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="item.barColor"
                                                :style="{ width: `${item.percentage}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card C: Unggas Komersial -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 flex flex-col justify-between space-y-3 shadow-xs">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full dark:bg-[#FB923C] bg-[#EA580C]"></span>
                                            <h4 class="font-display font-bold text-xs uppercase tracking-wider dark:text-white text-[#171B17]">
                                                {{ categoryGroups.unggas.title }}
                                            </h4>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold dark:bg-[#FB923C]/15 dark:text-[#FB923C] bg-[#EA580C]/10 text-[#EA580C]">
                                            {{ formatAngka(categoryGroups.unggas.total) }} ekor
                                        </span>
                                    </div>
                                    <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D]">
                                        Ayam ras pedaging (broiler) dan petelur (layer)
                                    </p>
                                </div>

                                <div class="space-y-3 pt-1">
                                    <div
                                        v-for="item in categoryGroups.unggas.items"
                                        :key="item.label"
                                        class="space-y-1.5"
                                    >
                                        <div class="flex items-start justify-between gap-2 text-xs">
                                            <div class="min-w-0">
                                                <span class="font-semibold dark:text-[#F5F5F5] text-[#171B17] block truncate">
                                                    {{ item.label }}
                                                </span>
                                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">
                                                    {{ item.sublabel }}
                                                </span>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="font-mono font-bold dark:text-white text-[#171B17] block tabular-nums">
                                                    {{ formatAngka(item.value) }} ekor
                                                </span>
                                                <span class="font-mono text-[11px] font-bold" :class="item.textColor">
                                                    {{ item.percentage }}%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="h-2 w-full dark:bg-[#111111] bg-[#E0E4DD] rounded-full overflow-hidden border dark:border-[#1E1E1E] border-[#C8CEC4]">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="item.barColor"
                                                :style="{ width: `${item.percentage}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Daya Dukung Pakan Hijauan (Carrying Capacity) -->
                    <div v-if="carryingCapacity" class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 sm:p-5 space-y-4 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <Leaf class="w-4 h-4 dark:text-[#00D084] text-[#171B17]" />
                                    <h3 class="font-display font-bold text-sm sm:text-base dark:text-white text-[#171B17]">
                                        Status Daya Dukung Pakan Hijauan (Carrying Capacity)
                                    </h3>
                                </div>
                                <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D] mt-0.5">
                                    Analisis keseimbangan beban populasi ternak berbasis Satuan Ternak (ST) terhadap ketersediaan luas kebun HMT.
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full border text-xs font-semibold flex items-center gap-1.5 shrink-0 self-start sm:self-center" :class="carryingCapacity.badgeClass">
                                <span class="w-2 h-2 rounded-full" :class="carryingCapacity.dotClass"></span>
                                <span>{{ carryingCapacity.statusLabel }}</span>
                            </span>
                        </div>

                        <!-- 4 Metric Cards Strip -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider block">Luas Kebun HMT</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-[#00D084] text-[#171B17] tabular-nums">
                                    {{ formatAngka(carryingCapacity.luasHmt) }} <span class="text-xs font-normal dark:text-[#B3B3B3] text-[#6E776D]">Ha</span>
                                </div>
                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">Kebun Rumput Pakan</span>
                            </div>

                            <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider block">Kapasitas Tampung</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-[#39D6C0] text-[#0E7490] tabular-nums">
                                    {{ formatAngka(carryingCapacity.kapasitasTampungST) }} <span class="text-xs font-normal dark:text-[#B3B3B3] text-[#6E776D]">ST</span>
                                </div>
                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">Standar 6 ST / Ha HMT</span>
                            </div>

                            <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider block">Total Beban Ternak</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-white text-[#171B17] tabular-nums">
                                    {{ formatAngka(carryingCapacity.totalST) }} <span class="text-xs font-normal dark:text-[#B3B3B3] text-[#6E776D]">ST</span>
                                </div>
                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">Sapi 1.0, Kecil 0.14, Ayam 0.01</span>
                            </div>

                            <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider block">Indeks Daya Dukung</span>
                                <div class="text-base sm:text-lg font-bold font-mono tabular-nums" :class="carryingCapacity.indicatorColor">
                                    {{ carryingCapacity.indeks }}
                                </div>
                                <span class="text-[10px] dark:text-[#888888] text-[#8C968B] block truncate">Kapasitas ÷ Beban ST</span>
                            </div>
                        </div>

                        <!-- Carrying capacity explanation note -->
                        <div class="text-xs dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed dark:bg-[#111111]/60 bg-white/60 rounded-xl p-3 border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-start gap-2">
                            <Info class="w-4 h-4 dark:text-[#00D084] text-[#171B17] shrink-0 mt-0.5" />
                            <div>
                                <strong class="dark:text-white text-[#171B17] font-semibold">{{ carryingCapacity.statusLabel }}:</strong>
                                <span class="ml-1">{{ carryingCapacity.statusDesc }}.</span>
                                <span class="block text-[11px] mt-1 dark:text-[#888888] text-[#8C968B]">
                                    Formula baku: Sapi (1.0 ST), Kambing/Domba (0.14 ST), Unggas (0.01 ST). Kapasitas kebun hijauan: 6 ST/Ha/Tahun.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role-Specific Data Blocks -->
                    <!-- PUBLIK ROLE CONTENT -->
                    <template v-if="currentRole === 'publik'">
                        <!-- Fasilitas Layanan Umum -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 space-y-2">
                                <div class="flex items-center gap-2 dark:text-[#00D084] text-[#171B17]">
                                    <Building2 class="w-4 h-4" />
                                    <h4 class="font-bold text-xs uppercase tracking-wider dark:text-slate-200 text-[#171B17]">
                                        Poskeswan &amp; RPH Wilayah
                                    </h4>
                                </div>
                                <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed">
                                    Wilayah ini dilayani oleh <strong class="dark:text-[#00D084] text-[#171B17] font-mono">{{ kecamatan.poskeswan }} Unit</strong> Pos Pelayanan Kesehatan Hewan (Poskeswan) dan Rumah Potong Hewan (RPH) berstandar ASUH (Aman, Sehat, Utuh, dan Halal).
                                </p>
                                <div class="text-[11px] dark:text-slate-300 text-[#444C43] font-mono pt-1">
                                    ✓ Layanan Vaksinasi PMK &amp; LSD Gratis<br />
                                    ✓ Pemeriksaan Kebuntingan &amp; Inseminasi Buatan (IB)
                                </div>
                            </div>

                            <!-- Perlindungan Privasi Perseorangan (UU PDP) -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 space-y-2">
                                <div class="flex items-center gap-2 dark:text-teal-400 text-[#171B17]">
                                    <Lock class="w-4 h-4" />
                                    <h4 class="font-bold text-xs uppercase tracking-wider dark:text-slate-200 text-[#171B17]">
                                        Perlindungan Data Pribadi
                                    </h4>
                                </div>
                                <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed">
                                    Sesuai UU No. 27/2022 tentang Pelindungan Data Pribadi (UU PDP), identitas nama dan alamat peternak individual dilindungi dan tidak dipublikasikan ke ranah publik.
                                </p>
                                <span class="text-[11px] dark:text-teal-400 text-[#6E776D] block pt-1 font-medium">
                                    Data agregat spasial disajikan untuk transparansi dan edukasi publik.
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- DINAS & ADMIN ROLE CONTENT: QUANTITATIVE SURVEILLANCE OVERVIEW -->
                    <template v-else>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <!-- Densitas Ternak -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider">Densitas Wilayah</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-[#00D084] text-[#171B17] tabular-nums">
                                    {{ animalDensity }} <span class="text-xs dark:text-[#B3B3B3] text-[#6E776D] font-normal">ekor/km²</span>
                                </div>
                                <span class="text-[10px] dark:text-[#B3B3B3] text-[#8C968B] block">Kepadatan Spasial</span>
                            </div>

                            <!-- Rasio Jantan/Betina -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider">Rasio Seks Ternak</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-cyan-400 text-[#171B17] tabular-nums">
                                    1 : 4,2
                                </div>
                                <span class="text-[10px] dark:text-[#B3B3B3] text-[#8C968B] block">Jantan : Betina Produktif</span>
                            </div>

                            <!-- Ruminansia Total -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider">Total Ruminansia</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-white text-[#171B17] tabular-nums">
                                    {{ formatAngka(totalRuminansia) }}
                                </div>
                                <span class="text-[10px] dark:text-[#B3B3B3] text-[#8C968B] block">Sapi, Kambing, Domba</span>
                            </div>

                            <!-- Unggas Total -->
                            <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold dark:text-[#B3B3B3] text-[#6E776D] tracking-wider">Total Unggas</span>
                                <div class="text-base sm:text-lg font-bold font-mono dark:text-white text-[#171B17] tabular-nums">
                                    {{ formatAngka(totalUnggas) }}
                                </div>
                                <span class="text-[10px] dark:text-[#B3B3B3] text-[#8C968B] block">Pedaging &amp; Petelur</span>
                            </div>
                        </div>

                        <!-- Produksi Peternakan Wilayah -->
                        <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 sm:p-5 space-y-3">
                            <h4 class="font-display font-bold text-xs uppercase tracking-wider dark:text-slate-200 text-[#171B17]">
                                Kapasitas Produksi Bahan Pangan Asal Hewan (Tahunan)
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                                <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5">
                                    <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block">Daging Sapi:</span>
                                    <span class="font-mono font-bold dark:text-[#00D084] text-[#171B17] text-sm tabular-nums">
                                        {{ formatAngka(kecamatan.daging_sapi || 0) }} kg
                                    </span>
                                </div>
                                <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5">
                                    <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block">Daging Ayam:</span>
                                    <span class="font-mono font-bold dark:text-cyan-400 text-[#171B17] text-sm tabular-nums">
                                        {{ formatAngka(kecamatan.daging_ayam || 0) }} kg
                                    </span>
                                </div>
                                <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5">
                                    <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block">Telur Ayam:</span>
                                    <span class="font-mono font-bold dark:text-amber-400 text-[#171B17] text-sm tabular-nums">
                                        {{ formatAngka(kecamatan.telur_ayam || 0) }} kg
                                    </span>
                                </div>
                                <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5">
                                    <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block">Daging Kambing/Domba:</span>
                                    <span class="font-mono font-bold dark:text-teal-400 text-[#171B17] text-sm tabular-nums">
                                        {{ formatAngka((kecamatan.daging_kambing || 0) + (kecamatan.daging_domba || 0)) }} kg
                                    </span>
                                </div>
                                <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5">
                                    <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block">Susu Sapi Segar:</span>
                                    <span class="font-mono font-bold dark:text-indigo-400 text-[#171B17] text-sm tabular-nums">
                                        {{ formatAngka(kecamatan.susu_sapi || 0) }} Liter
                                    </span>
                                </div>
                                <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-2.5">
                                    <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block">Poskeswan / RPH:</span>
                                    <span class="font-mono font-bold dark:text-white text-[#171B17] text-sm tabular-nums">
                                        {{ kecamatan.poskeswan || 0 }} Unit Aktif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 2: DAFTAR DESA / KELURAHAN (INTERAKTIF DENGAN FOCUS DESA) -->
                <div v-else-if="activeTab === 'desa'" class="space-y-4">
                    <!-- Filter & Search Controls -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="relative flex-1">
                            <input
                                v-model="searchDesaQuery"
                                type="text"
                                placeholder="Cari desa atau kelurahan"
                                class="w-full h-9 dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] focus:dark:border-[#00D084] focus:border-[#171B17] rounded-xl px-3 pr-8 text-xs dark:text-[#F5F5F5] text-[#171B17] outline-none placeholder:dark:text-[#B3B3B3] placeholder:text-[#A2AEA2]"
                            />
                            <Search class="w-4 h-4 dark:text-[#B3B3B3] text-[#A2AEA2] absolute right-2.5 top-2.5 pointer-events-none" />
                        </div>

                        <!-- Zona Filter Pills -->
                        <div class="flex items-center gap-1.5 shrink-0 overflow-x-auto">
                            <button
                                type="button"
                                @click="filterDesaZona = 'all'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer transition-colors"
                                :class="filterDesaZona === 'all'
                                    ? 'dark:bg-[#1E1E1E] bg-[#171B17] text-white'
                                    : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#F5F5F5] hover:text-[#171B17]'"
                            >
                                Semua ({{ desaList.length }})
                            </button>
                            <button
                                type="button"
                                @click="filterDesaZona = 'bahaya'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer transition-colors"
                                :class="filterDesaZona === 'bahaya'
                                    ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40'
                                    : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:text-rose-400'"
                            >
                                Bahaya
                            </button>
                            <button
                                type="button"
                                @click="filterDesaZona = 'waspada'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer transition-colors"
                                :class="filterDesaZona === 'waspada'
                                    ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40'
                                    : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:text-amber-400'"
                            >
                                Waspada
                            </button>
                            <button
                                type="button"
                                @click="filterDesaZona = 'aman'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer transition-colors"
                                :class="filterDesaZona === 'aman'
                                    ? 'dark:bg-[#063D2D] bg-[#171B17] text-white border dark:border-[#00D084]/40 border-[#171B17] dark:text-[#00D084]'
                                    : 'dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] hover:dark:text-[#00D084] hover:text-[#171B17]'"
                            >
                                Aman
                            </button>
                        </div>
                    </div>

                    <!-- Desa Table / List -->
                    <div class="border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl overflow-hidden dark:bg-[#111111] bg-white">
                        <div class="overflow-x-auto max-h-[380px] custom-scroll">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="dark:bg-[#181818] bg-[#EDEFEB] border-b dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#6E776D] sticky top-0 z-10 font-mono">
                                    <tr>
                                        <th class="p-3 font-semibold">Nama Desa / Kelurahan</th>
                                        <th class="p-3 font-semibold">Kode BPS</th>
                                        <th class="p-3 font-semibold text-center">Status EWS</th>
                                        <th v-if="currentRole !== 'publik'" class="p-3 font-semibold text-right">Kasus PMK</th>
                                        <th v-if="currentRole !== 'publik'" class="p-3 font-semibold text-right">Kasus LSD</th>
                                        <th class="p-3 font-semibold text-right">Sapi Potong</th>
                                        <th class="p-3 font-semibold text-center">Aksi Peta</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-[#1E1E1E] divide-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17]">
                                    <tr
                                        v-for="desa in filteredDesaList"
                                        :key="desa.id"
                                        class="hover:dark:bg-[#1E1E1E] hover:bg-[#F4F5F2] transition-colors group"
                                    >
                                        <td class="p-3">
                                            <div class="font-bold dark:text-white text-[#171B17] group-hover:dark:text-[#00D084] group-hover:text-black transition-colors">
                                                {{ desa.nama }}
                                            </div>
                                            <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] font-mono">{{ desa.jenis }}</span>
                                        </td>
                                        <td class="p-3 font-mono text-[11px] dark:text-[#B3B3B3] text-[#6E776D]">
                                            {{ desa.kode_kd }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <span
                                                class="px-2 py-0.5 rounded-full border text-[10px] font-semibold"
                                                :class="getDesaEwsStatus(desa).badgeClass"
                                            >
                                                {{ getDesaEwsStatus(desa).label }}
                                            </span>
                                        </td>
                                        <td v-if="currentRole !== 'publik'" class="p-3 text-right font-mono tabular-nums">
                                            <span :class="desa.pmk > 0 ? 'text-rose-400 font-bold' : 'dark:text-[#B3B3B3] text-[#6E776D]'">
                                                {{ desa.pmk }}
                                            </span>
                                        </td>
                                        <td v-if="currentRole !== 'publik'" class="p-3 text-right font-mono tabular-nums">
                                            <span :class="desa.lsd > 0 ? 'text-amber-400 font-bold' : 'dark:text-[#B3B3B3] text-[#6E776D]'">
                                                {{ desa.lsd }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-mono tabular-nums dark:text-[#00D084] text-[#171B17] font-bold">
                                            {{ formatAngka(desa.sapi_potong || 0) }} ekor
                                        </td>
                                        <td class="p-3 text-center">
                                            <button
                                                type="button"
                                                @click="emit('focusDesa', desa); emit('close');"
                                                class="h-7 px-2.5 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] hover:dark:bg-[#00D084] hover:bg-[#171B17] hover:text-white hover:dark:text-[#062117] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084] hover:border-[#171B17] dark:text-[#F5F5F5] text-[#171B17] text-[11px] font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer shadow-xs"
                                                title="Fokuskan Peta ke Desa ini"
                                            >
                                                <ExternalLink class="w-3.5 h-3.5" />
                                                <span>Fokus</span>
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredDesaList.length === 0">
                                        <td colspan="7" class="p-8 text-center dark:text-[#B3B3B3] text-[#6E776D]">
                                            Tidak ditemukan desa dengan kriteria pencarian "{{ searchDesaQuery }}".
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: SURVEILANS KESEHATAN HEWAN & EWS (DINAS & ADMIN ONLY) -->
                <div v-else-if="activeTab === 'surveilans' && currentRole !== 'publik'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Card Status PMK -->
                        <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                                        <AlertTriangle class="w-4 h-4" />
                                    </div>
                                    <h4 class="font-display font-bold text-sm dark:text-white text-[#171B17]">
                                        Penyakit Mulut &amp; Kuku (PMK)
                                    </h4>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold" :class="kecamatan.pmk > 0 ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30 bg-[#F4F5F2] text-[#171B17] border border-[#E0E4DD]'">
                                    {{ kecamatan.pmk > 0 ? `${kecamatan.pmk} Kasus Aktif` : 'Terkendali / Nol Kasus' }}
                                </span>
                            </div>
                            <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed">
                                Pengawasan terhadap lalu lintas ternak berkuku belah (sapi, kerbau, kambing, domba, dan babi). Pemberian booster vaksinasi dilakukan terjadwal di seluruh unit peternakan rakyat.
                            </p>
                            <div class="p-3 dark:bg-[#181818] bg-white rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] text-[11px] dark:text-slate-300 text-[#444C43] space-y-1 font-mono">
                                <div class="flex justify-between">
                                    <span>Cakupan Vaksinasi:</span>
                                    <strong class="dark:text-[#00D084] text-[#171B17]">92,4% Terpenuhi</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>Isolasi Terpadu:</span>
                                    <strong class="dark:text-white text-[#171B17]">{{ kecamatan.pmk > 0 ? 'Aktif di Desa Terpapar' : 'Siaga Wilayah' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Card Status LSD -->
                        <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                        <ShieldAlert class="w-4 h-4" />
                                    </div>
                                    <h4 class="font-display font-bold text-sm dark:text-white text-[#171B17]">
                                        Lumpy Skin Disease (LSD)
                                    </h4>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold" :class="kecamatan.lsd > 0 ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30 bg-[#F4F5F2] text-[#171B17] border border-[#E0E4DD]'">
                                    {{ kecamatan.lsd > 0 ? `${kecamatan.lsd} Kasus Aktif` : 'Terkendali / Bebas LSD' }}
                                </span>
                            </div>
                            <p class="text-xs dark:text-[#B3B3B3] text-[#6E776D] leading-relaxed">
                                Surveilans vektor serangga penular (lalat penghisap darah, nyamuk, dan caplak). Biosecurity kandang dan desinfeksi berkala dikoordinasikan bersama medik veteriner setempat.
                            </p>
                            <div class="p-3 dark:bg-[#181818] bg-white rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] text-[11px] dark:text-slate-300 text-[#444C43] space-y-1 font-mono">
                                <div class="flex justify-between">
                                    <span>Pengendalian Vektor:</span>
                                    <strong class="dark:text-teal-400 text-[#171B17]">Spraying Desinfektan Rutin</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>Kesiagaan Obat:</span>
                                    <strong class="dark:text-white text-[#171B17]">Stok Poskeswan Aman</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: INTEGRITAS GEODESI & SPASIAL (ADMIN ONLY) -->
                <div v-else-if="activeTab === 'spasial' && currentRole === 'admin'" class="space-y-4">
                    <div class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-4 sm:p-5 space-y-4">
                        <div class="flex items-center gap-2">
                            <Globe class="w-4 h-4 dark:text-[#00D084] text-[#171B17]" />
                            <h4 class="font-display font-bold text-sm dark:text-white text-[#171B17]">
                                Verifikasi Geospasial &amp; Standar BIG/BPS
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block font-mono">Centroid Latitude:</span>
                                <span class="font-mono font-bold dark:text-[#00D084] text-[#171B17] text-sm">
                                    {{ kecamatan.lat }}
                                </span>
                            </div>
                            <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block font-mono">Centroid Longitude:</span>
                                <span class="font-mono font-bold dark:text-[#00D084] text-[#171B17] text-sm">
                                    {{ kecamatan.lng }}
                                </span>
                            </div>
                            <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block font-mono">Datum &amp; CRS:</span>
                                <span class="font-mono font-semibold dark:text-slate-200 text-[#171B17] text-xs">
                                    EPSG:4326 - WGS 84 (GeoJSON Standar Nasional)
                                </span>
                            </div>
                            <div class="dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-3 space-y-1">
                                <span class="dark:text-[#B3B3B3] text-[#6E776D] text-[11px] block font-mono">Validitas Topologi:</span>
                                <span class="font-mono font-semibold dark:text-[#00D084] text-[#171B17] text-xs flex items-center gap-1.5">
                                    <CheckCircle2 class="w-3.5 h-3.5 dark:text-[#00D084] text-[#171B17]" />
                                    100% Sinkron Poligon BIG/BPS
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="emit('editRegion', kecamatan); emit('close');"
                                class="h-9 px-4 rounded-xl dark:bg-[#00D084] dark:text-[#062117] hover:dark:bg-[#00F0A0] bg-[#171B17] text-white hover:bg-[#2A312A] font-bold text-xs inline-flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                            >
                                <Pencil class="w-3.5 h-3.5 stroke-[2.2]" />
                                <span>Buka Form Edit Administrasi Spasial</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ==================================================== -->
            <!-- MODAL FOOTER                                         -->
            <!-- ==================================================== -->
            <footer class="p-3 sm:p-4 border-t dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#FFFFFF] flex items-center justify-between gap-3 shrink-0 transition-colors">
                <div class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] font-mono hidden sm:block">
                    Ternakverse Banyumas • Sistem WebGIS Intelijen Peternakan Terpadu
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="h-9 px-5 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#181818] bg-[#EDEFEB] dark:text-[#F5F5F5] text-[#171B17] hover:dark:bg-[#1E1E1E] hover:bg-[#E0E4DD] text-xs font-semibold transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                    <button
                        v-if="currentRole !== 'publik'"
                        type="button"
                        @click="exportRegionCsv"
                        class="h-9 px-4 rounded-xl dark:bg-[#00D084] dark:text-[#062117] hover:dark:bg-[#00F0A0] bg-[#171B17] text-white hover:bg-[#2A312A] font-semibold text-xs inline-flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                    >
                        <FileSpreadsheet class="w-3.5 h-3.5" />
                        <span>Unduh CSV</span>
                    </button>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #1E1E1E;
    border-radius: 4px;
}
:root:not(.dark) .custom-scroll::-webkit-scrollbar-thumb {
    background: #E0E4DD;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #00D084;
}
</style>
