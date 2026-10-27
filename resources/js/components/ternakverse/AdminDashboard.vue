<script setup lang="ts">
import { ref, computed } from 'vue';
import { KecamatanData, formatAngka } from '../../data/banyumasData';
import { Plus, Download, Upload, Search, Edit2, Trash2, TrendingUp, TrendingDown, Activity, AlertTriangle, ShieldCheck, Map, Check, ArrowRight } from 'lucide-vue-next';

const props = defineProps<{
    kecamatanList: KecamatanData[];
}>();

const emit = defineEmits<{
    (e: 'updateData', list: KecamatanData[]): void;
    (e: 'openMap'): void;
}>();

// Search & Filter
const tableSearch = ref('');
const selectedZonaFilter = ref<'semua' | 'bahaya' | 'waspada' | 'aman'>('semua');

// State Dialog Tambah/Edit Data
const isModalOpen = ref(false);
const editingKecamatan = ref<KecamatanData | null>(null);
const form = ref({
    nama: '',
    zona: 'aman' as 'bahaya' | 'waspada' | 'aman',
    sapi_potong: 0,
    sapi_perah: 0,
    kambing: 0,
    domba: 0,
    pmk: 0,
    lsd: 0,
});

// State Dialog Konfirmasi Hapus Spesifik (Bab 6)
const isDeleteOpen = ref(false);
const deletingKecamatan = ref<KecamatanData | null>(null);

// State Line Chart Hover Guide (Bab 7)
const hoveredLineIndex = ref<number | null>(null);

// Feed Aktivitas Terkini (Audit Trail, Bab 7)
const activityFeed = ref([
    { id: 1, user: 'Budi (Dinas)', desc: 'memperbarui kasus PMK Ajibarang jadi 12 kasus', time: '2 menit lalu', type: 'pmk' },
    { id: 2, user: 'Siti (Admin)', desc: 'menambahkan data sapi potong Wangon (+120 ekor)', time: '14 menit lalu', type: 'populasi' },
    { id: 3, user: 'Sistem Sinkron', desc: 'verifikasi status zona waspada Sokaraja', time: '1 jam lalu', type: 'sistem' },
    { id: 4, user: 'Dr. Hendra', desc: 'laporan vaksinasi PMK Baturraden 850 dosis', time: '3 jam lalu', type: 'vaksin' },
]);

// Toast Notification
const toastMsg = ref<string | null>(null);
function showToast(msg: string) {
    toastMsg.value = msg;
    setTimeout(() => {
        toastMsg.value = null;
    }, 4000);
}

// 4 Kartu KPI Ringkasan
const kpiStats = computed(() => {
    const totalSapi = props.kecamatanList.reduce((acc, k) => acc + k.sapi_potong, 0);
    const totalKambing = props.kecamatanList.reduce((acc, k) => acc + k.kambing, 0);
    const totalPmk = props.kecamatanList.reduce((acc, k) => acc + k.pmk, 0);
    const totalLsd = props.kecamatanList.reduce((acc, k) => acc + k.lsd, 0);
    return { totalSapi, totalKambing, totalPmk, totalLsd };
});

// Top 5 Bar Chart Data
const top5BarData = computed(() => {
    return [...props.kecamatanList]
        .sort((a, b) => b.sapi_potong - a.sapi_potong)
        .slice(0, 5);
});

// Filter Data Tabel
const filteredList = computed(() => {
    return props.kecamatanList.filter(k => {
        const matchesSearch = k.nama.toLowerCase().includes(tableSearch.value.toLowerCase());
        const matchesZona = selectedZonaFilter.value === 'semua' || k.zona === selectedZonaFilter.value;
        return matchesSearch && matchesZona;
    });
});

function openAddModal() {
    editingKecamatan.value = null;
    form.value = {
        nama: '',
        zona: 'aman',
        sapi_potong: 1000,
        sapi_perah: 200,
        kambing: 1500,
        domba: 500,
        pmk: 0,
        lsd: 0,
    };
    isModalOpen.value = true;
}

function openEditModal(kec: KecamatanData) {
    editingKecamatan.value = kec;
    form.value = {
        nama: kec.nama,
        zona: kec.zona,
        sapi_potong: kec.sapi_potong,
        sapi_perah: kec.sapi_perah,
        kambing: kec.kambing,
        domba: kec.domba,
        pmk: kec.pmk,
        lsd: kec.lsd,
    };
    isModalOpen.value = true;
}

function saveFormData() {
    if (!form.value.nama) return;

    const list = [...props.kecamatanList];
    if (editingKecamatan.value) {
        // Update existing
        const idx = list.findIndex(k => k.id === editingKecamatan.value?.id);
        if (idx !== -1) {
            list[idx] = {
                ...list[idx],
                zona: form.value.zona,
                sapi_potong: Number(form.value.sapi_potong),
                sapi_perah: Number(form.value.sapi_perah),
                kambing: Number(form.value.kambing),
                domba: Number(form.value.domba),
                pmk: Number(form.value.pmk),
                lsd: Number(form.value.lsd),
                last_updated: 'Baru saja'
            };
            activityFeed.value.unshift({
                id: Date.now(),
                user: 'Kamu (Admin)',
                desc: `memperbarui data ${form.value.nama}`,
                time: 'baru saja',
                type: 'populasi'
            });
            showToast(`Data ${form.value.nama} berhasil diperbarui.`);
        }
    } else {
        // Add new
        const newId = form.value.nama.toLowerCase().replace(/\s+/g, '_');
        list.push({
            id: newId,
            nama: form.value.nama,
            zona: form.value.zona,
            sapi_potong: Number(form.value.sapi_potong),
            sapi_perah: Number(form.value.sapi_perah),
            kambing: Number(form.value.kambing),
            domba: Number(form.value.domba),
            ayam_broiler: 15000,
            pmk: Number(form.value.pmk),
            lsd: Number(form.value.lsd),
            center: [400, 300],
            path: 'M 350 250 L 450 250 L 450 350 L 350 350 Z',
            last_updated: 'Baru saja'
        });
        showToast(`Kecamatan ${form.value.nama} berhasil ditambahkan.`);
    }

    emit('updateData', list);
    isModalOpen.value = false;
}

function confirmDelete(kec: KecamatanData) {
    deletingKecamatan.value = kec;
    isDeleteOpen.value = true;
}

function executeDelete() {
    if (!deletingKecamatan.value) return;
    const list = props.kecamatanList.filter(k => k.id !== deletingKecamatan.value?.id);
    activityFeed.value.unshift({
        id: Date.now(),
        user: 'Kamu (Admin)',
        desc: `menghapus data ${deletingKecamatan.value.nama}`,
        time: 'baru saja',
        type: 'hapus'
    });
    showToast(`Data ${deletingKecamatan.value.nama} berhasil dihapus.`);
    emit('updateData', list);
    isDeleteOpen.value = false;
    deletingKecamatan.value = null;
}
</script>

<template>
    <!-- CONTAINER SATU KOLOM (Maksimal 1160px, Bab 4.2) -->
    <div class="w-full max-w-[1160px] mx-auto px-4 py-8 space-y-6 select-none font-ui">
        
        <!-- 1. HEADER (Judul + Tombol Cari, Impor CSV/Excel, Tambah Data, Bab 4.2) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b-2 border-[#243034]">
            <div>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-[#F4F7F5] tracking-tight">
                    Kelola Data Peternakan
                </h1>
                <p class="text-sm text-[#8FA1A0] mt-1">
                    Panel kendali data statistik peternakan & kewaspadaan wabah Kabupaten Banyumas.
                </p>
            </div>

            <div class="flex items-center flex-wrap gap-2.5">
                <button
                    @click="showToast('Fitur impor CSV: format data Banyumas valid.')"
                    class="btn-secondary-tactile text-xs font-bold gap-2"
                >
                    <Upload class="w-4 h-4 text-[#19F5D4]" />
                    <span>Impor CSV / Excel</span>
                </button>
                <button
                    @click="openAddModal"
                    class="btn-primary-tactile text-xs font-bold gap-2"
                >
                    <Plus class="w-4 h-4 stroke-[3]" />
                    <span>+ Tambah Data</span>
                </button>
            </div>
        </div>

        <!-- 2. ANALITIK (Bab 7: 4 Kartu KPI + Bar Chart + Line Chart + Donut + Activity) -->
        <section class="space-y-4">
            <!-- 2.1 Empat Kartu KPI Statistik Full Width -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- KPI 1: Sapi Potong -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-[#8FA1A0]">Total Sapi Potong</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#39FF14]/20 text-[#39FF14] border border-[#39FF14]">
                                +4,2%
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1.5 mt-2">
                            <span class="font-display font-extrabold text-3xl text-[#F4F7F5]">
                                {{ formatAngka(kpiStats.totalSapi) }}
                            </span>
                            <span class="text-xs text-[#8FA1A0]">ekor</span>
                        </div>
                    </div>
                    <!-- Sparkline SVG (Garis 2px aksen + area 12% opacity, Bab 7.1) -->
                    <div class="h-10 w-full mt-3">
                        <svg class="w-full h-full" viewBox="0 0 100 30" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="spark-sapi" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#39FF14" stop-opacity="0.25" />
                                    <stop offset="100%" stop-color="#39FF14" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="M 0 25 Q 25 15 50 18 T 100 5 L 100 30 L 0 30 Z" fill="url(#spark-sapi)" />
                            <path d="M 0 25 Q 25 15 50 18 T 100 5" fill="none" stroke="#39FF14" stroke-width="2.5" />
                        </svg>
                    </div>
                </div>

                <!-- KPI 2: Total Kambing -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-[#8FA1A0]">Total Ternak Kambing</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#39FF14]/20 text-[#39FF14] border border-[#39FF14]">
                                +6,1%
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1.5 mt-2">
                            <span class="font-display font-extrabold text-3xl text-[#F4F7F5]">
                                {{ formatAngka(kpiStats.totalKambing) }}
                            </span>
                            <span class="text-xs text-[#8FA1A0]">ekor</span>
                        </div>
                    </div>
                    <div class="h-10 w-full mt-3">
                        <svg class="w-full h-full" viewBox="0 0 100 30" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="spark-kambing" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#19F5D4" stop-opacity="0.25" />
                                    <stop offset="100%" stop-color="#19F5D4" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="M 0 20 Q 30 25 60 10 T 100 4 L 100 30 L 0 30 Z" fill="url(#spark-kambing)" />
                            <path d="M 0 20 Q 30 25 60 10 T 100 4" fill="none" stroke="#19F5D4" stroke-width="2.5" />
                        </svg>
                    </div>
                </div>

                <!-- KPI 3: Kasus PMK Aktif -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-[#8FA1A0]">Kasus Aktif PMK</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#39FF14]/20 text-[#39FF14] border border-[#39FF14]">
                                -18,5% (Membaik)
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1.5 mt-2">
                            <span class="font-display font-extrabold text-3xl text-[#FF5C5C]">
                                {{ kpiStats.totalPmk }}
                            </span>
                            <span class="text-xs text-[#8FA1A0]">kasus aktif</span>
                        </div>
                    </div>
                    <div class="h-10 w-full mt-3">
                        <svg class="w-full h-full" viewBox="0 0 100 30" preserveAspectRatio="none">
                            <path d="M 0 5 Q 35 10 70 20 T 100 26 L 100 30 L 0 30 Z" fill="rgba(255, 92, 92, 0.15)" />
                            <path d="M 0 5 Q 35 10 70 20 T 100 26" fill="none" stroke="#FF5C5C" stroke-width="2.5" />
                        </svg>
                    </div>
                </div>

                <!-- KPI 4: Kasus LSD Aktif -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-[#8FA1A0]">Kasus Aktif LSD</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#FFC53D]/20 text-[#FFC53D] border border-[#FFC53D]">
                                Stabil Waspada
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1.5 mt-2">
                            <span class="font-display font-extrabold text-3xl text-[#FFC53D]">
                                {{ kpiStats.totalLsd }}
                            </span>
                            <span class="text-xs text-[#8FA1A0]">kasus aktif</span>
                        </div>
                    </div>
                    <div class="h-10 w-full mt-3">
                        <svg class="w-full h-full" viewBox="0 0 100 30" preserveAspectRatio="none">
                            <path d="M 0 15 Q 40 18 70 14 T 100 12 L 100 30 L 0 30 Z" fill="rgba(255, 197, 61, 0.15)" />
                            <path d="M 0 15 Q 40 18 70 14 T 100 12" fill="none" stroke="#FFC53D" stroke-width="2.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 2.2 Baris Chart Atas: Bar Chart Top-5 (Cyan->Neon) & Line Chart Penyakit (PMK Merah vs LSD Kuning) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Bar Chart: Populasi Sapi Potong Top-5 -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-display font-bold text-lg text-[#F4F7F5]">Top 5 Kecamatan (Sapi Potong)</h3>
                            <p class="text-xs text-[#8FA1A0]">Komparasi populasi tertinggi di Banyumas</p>
                        </div>
                        <span class="text-xs font-mono text-[#39FF14] bg-[#0A0D0E] px-2.5 py-1 rounded-full border border-[#243034]">
                            Live SVG
                        </span>
                    </div>

                    <!-- SVG Bar Chart -->
                    <div class="h-56 w-full">
                        <svg class="w-full h-full" viewBox="0 0 450 200">
                            <defs>
                                <!-- Gradien cyan -> neon vertikal (satu-satunya gradien yg diizinkan Bab 2) -->
                                <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#39FF14" />
                                    <stop offset="100%" stop-color="#19F5D4" />
                                </linearGradient>
                            </defs>
                            <!-- Grid Putus-putus Opacity 0.28 (Bab 7) -->
                            <line x1="50" y1="40" x2="430" y2="40" stroke="#3A4A4F" stroke-dasharray="3 6" opacity="0.3" />
                            <line x1="50" y1="90" x2="430" y2="90" stroke="#3A4A4F" stroke-dasharray="3 6" opacity="0.3" />
                            <line x1="50" y1="140" x2="430" y2="140" stroke="#3A4A4F" stroke-dasharray="3 6" opacity="0.3" />

                            <!-- 5 Batang -->
                            <g v-for="(item, idx) in top5BarData" :key="item.id">
                                <rect
                                    :x="75 + idx * 72"
                                    :y="160 - (item.sapi_potong / 3500) * 120"
                                    width="42"
                                    :height="(item.sapi_potong / 3500) * 120"
                                    rx="6"
                                    fill="url(#barGradient)"
                                    stroke="#F4F7F5"
                                    stroke-width="1.5"
                                />
                                <!-- Nilai di atas batang (Bab 7) -->
                                <text
                                    :x="75 + idx * 72 + 21"
                                    :y="150 - (item.sapi_potong / 3500) * 120"
                                    text-anchor="middle"
                                    class="fill-[#19F5D4] font-display font-extrabold text-[12px]"
                                >
                                    {{ formatAngka(item.sapi_potong) }}
                                </text>
                                <!-- Label Kecamatan -->
                                <text
                                    :x="75 + idx * 72 + 21"
                                    y="180"
                                    text-anchor="middle"
                                    class="fill-[#8FA1A0] font-ui font-bold text-[11px]"
                                >
                                    {{ item.nama }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- Line Chart: Tren Kasus Penyakit PMK Merah vs LSD Kuning -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-display font-bold text-lg text-[#F4F7F5]">Tren Kasus Penyakit (6 Bulan)</h3>
                            <p class="text-xs text-[#8FA1A0]">Perkembangan kasus PMK vs LSD</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="flex items-center gap-1.5 text-[#FF5C5C] font-bold">
                                <span class="w-3 h-0.5 bg-[#FF5C5C]"></span> PMK
                            </span>
                            <span class="flex items-center gap-1.5 text-[#FFC53D] font-bold">
                                <span class="w-3 h-0.5 bg-[#FFC53D]"></span> LSD
                            </span>
                        </div>
                    </div>

                    <!-- SVG Multi-line Chart -->
                    <div class="h-56 w-full">
                        <svg class="w-full h-full" viewBox="0 0 450 200">
                            <!-- Grid Putus-putus -->
                            <line x1="40" y1="40" x2="430" y2="40" stroke="#3A4A4F" stroke-dasharray="3 6" opacity="0.3" />
                            <line x1="40" y1="90" x2="430" y2="90" stroke="#3A4A4F" stroke-dasharray="3 6" opacity="0.3" />
                            <line x1="40" y1="140" x2="430" y2="140" stroke="#3A4A4F" stroke-dasharray="3 6" opacity="0.3" />

                            <!-- Line PMK Merah -->
                            <path
                                d="M 60 70 L 130 50 L 205 90 L 280 120 L 355 135 L 415 145"
                                fill="none"
                                stroke="#FF5C5C"
                                stroke-width="3"
                                stroke-linecap="round"
                            />
                            <!-- Line LSD Kuning -->
                            <path
                                d="M 60 130 L 130 115 L 205 125 L 280 110 L 355 120 L 415 130"
                                fill="none"
                                stroke="#FFC53D"
                                stroke-width="3"
                                stroke-linecap="round"
                            />

                            <!-- Titik Fokus -->
                            <circle cx="415" cy="145" r="5" fill="#FF5C5C" stroke="#F4F7F5" stroke-width="2" />
                            <circle cx="415" cy="130" r="5" fill="#FFC53D" stroke="#F4F7F5" stroke-width="2" />

                            <!-- Label Bulan -->
                            <text x="60" y="175" class="fill-[#8FA1A0] text-[11px] font-bold" text-anchor="middle">Mei</text>
                            <text x="130" y="175" class="fill-[#8FA1A0] text-[11px] font-bold" text-anchor="middle">Jun</text>
                            <text x="205" y="175" class="fill-[#8FA1A0] text-[11px] font-bold" text-anchor="middle">Jul</text>
                            <text x="280" y="175" class="fill-[#8FA1A0] text-[11px] font-bold" text-anchor="middle">Agu</text>
                            <text x="355" y="175" class="fill-[#8FA1A0] text-[11px] font-bold" text-anchor="middle">Sep</text>
                            <text x="415" y="175" class="fill-[#19F5D4] text-[11px] font-bold" text-anchor="middle">Okt</text>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 2.3 Baris Bawah: Donut 210px & Feed Aktivitas Terkini (Audit Trail, Bab 7.3) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Donut Chart Komposisi Komoditas -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5 flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="relative w-48 h-48 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <!-- Sapi Potong (42%) -->
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#39FF14" stroke-width="14" stroke-dasharray="100 138" stroke-dashoffset="0" />
                            <!-- Kambing (38%) -->
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#19F5D4" stroke-width="14" stroke-dasharray="90 148" stroke-dashoffset="-100" />
                            <!-- Domba (12%) -->
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#FFC53D" stroke-width="14" stroke-dasharray="30 208" stroke-dashoffset="-190" />
                            <!-- Sapi Perah (8%) -->
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#16BDB8" stroke-width="14" stroke-dasharray="18 220" stroke-dashoffset="-220" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] text-[#8FA1A0] font-bold uppercase tracking-wider">Total Hewan</span>
                            <span class="font-display font-extrabold text-xl text-[#F4F7F5]">122.4K</span>
                        </div>
                    </div>

                    <!-- Legenda Donut -->
                    <div class="space-y-2 text-xs flex-1">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#39FF14]"></span> Sapi Potong</span>
                            <span class="font-bold text-[#F4F7F5]">48.970 (40%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#19F5D4]"></span> Kambing</span>
                            <span class="font-bold text-[#F4F7F5]">59.870 (48%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#FFC53D]"></span> Domba</span>
                            <span class="font-bold text-[#F4F7F5]">21.450 (8%)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#16BDB8]"></span> Sapi Perah</span>
                            <span class="font-bold text-[#F4F7F5]">5.650 (4%)</span>
                        </div>
                    </div>
                </div>

                <!-- Feed Aktivitas Terkini (Audit Trail) -->
                <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-display font-bold text-lg text-[#F4F7F5] flex items-center gap-2">
                            <Activity class="w-4 h-4 text-[#19F5D4]" />
                            Log Aktivitas Perubahan Data
                        </h3>
                        <span class="text-[11px] text-[#8FA1A0]">Real-time</span>
                    </div>

                    <ul class="space-y-2.5">
                        <li
                            v-for="act in activityFeed.slice(0, 4)"
                            :key="act.id"
                            class="text-xs p-2.5 rounded-12 bg-[#0A0D0E] border border-[#243034] flex items-start justify-between gap-2"
                        >
                            <div>
                                <span class="font-bold text-[#19F5D4]">{{ act.user }}</span>
                                <span class="text-[#F4F7F5] ml-1">{{ act.desc }}.</span>
                            </div>
                            <span class="text-[10px] text-[#8FA1A0] flex-shrink-0 italic mt-0.5">{{ act.time }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- 3. BARIS RINGKASAN RAMPING (Bab 4.2: Satu Panel, Item Dipisah Border Kanan, Angka 24px) -->
        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-[#243034]">
            <div class="px-4 py-2 sm:py-0 flex items-center gap-3">
                <span class="font-display font-extrabold text-2xl text-[#19F5D4]">{{ kecamatanList.length }}</span>
                <div>
                    <div class="text-xs font-bold text-[#F4F7F5]">Catatan Terdata</div>
                    <div class="text-[11px] text-[#8FA1A0]">Basis data terverifikasi</div>
                </div>
            </div>
            <div class="px-4 py-2 sm:py-0 flex items-center gap-3">
                <span class="font-display font-extrabold text-2xl text-[#39FF14]">27/27</span>
                <div>
                    <div class="text-xs font-bold text-[#F4F7F5]">Kecamatan Lengkap</div>
                    <div class="text-[11px] text-[#8FA1A0]">Cakupan wilayah 100%</div>
                </div>
            </div>
            <div class="px-4 py-2 sm:py-0 flex items-center gap-3">
                <span class="font-display font-extrabold text-2xl text-[#FF5C5C]">
                    {{ kecamatanList.filter(k => k.zona === 'bahaya').length }}
                </span>
                <div>
                    <div class="text-xs font-bold text-[#F4F7F5]">Zona Merah Aktif</div>
                    <div class="text-[11px] text-[#8FA1A0]">Prioritas tindakan darurat</div>
                </div>
            </div>
        </div>

        <!-- 4. TABEL DATA (Panel Penuh, Bab 4.2 & Bab 6) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5">
            <!-- Header Filter Tabel -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4">
                <div class="relative w-full sm:w-72">
                    <Search class="w-4 h-4 absolute left-3 top-3.5 text-[#8FA1A0]" />
                    <input
                        v-model="tableSearch"
                        type="text"
                        placeholder="Cari nama kecamatan..."
                        class="input-tactile w-full pl-9 text-xs"
                    />
                </div>

                <!-- Filter Zona -->
                <div class="flex items-center gap-1.5 w-full sm:w-auto">
                    <button
                        v-for="z in (['semua', 'bahaya', 'waspada', 'aman'] as const)"
                        :key="z"
                        @click="selectedZonaFilter = z"
                        class="px-3 py-1.5 rounded-full text-xs font-bold capitalize transition-all"
                        :class="selectedZonaFilter === z ? 'bg-[#243034] text-[#19F5D4] border border-[#19F5D4]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                    >
                        {{ z }}
                    </button>
                </div>
            </div>

            <!-- Tabel Data Tanpa Zebra, Pemisah 1px Garis, Hover Garis Radius 12px (Bab 6) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#243034] text-[13px] font-medium text-[#8FA1A0]">
                            <th class="py-3 px-3">Kecamatan</th>
                            <th class="py-3 px-3">Status Zona</th>
                            <th class="py-3 px-3 text-right">Sapi Potong</th>
                            <th class="py-3 px-3 text-right">Sapi Perah</th>
                            <th class="py-3 px-3 text-right">Kambing</th>
                            <th class="py-3 px-3 text-right">PMK</th>
                            <th class="py-3 px-3 text-right">LSD</th>
                            <th class="py-3 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#243034]">
                        <tr
                            v-for="kec in filteredList"
                            :key="kec.id"
                            class="group hover:bg-[#243034] transition-colors rounded-12"
                        >
                            <td class="py-3.5 px-3 font-bold text-[#F4F7F5]">{{ kec.nama }}</td>
                            <td class="py-3.5 px-3">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                    :class="{
                                        'bg-[#FF5C5C]/20 text-[#FF5C5C] border-[#FF5C5C]': kec.zona === 'bahaya',
                                        'bg-[#FFC53D]/20 text-[#FFC53D] border-[#FFC53D]': kec.zona === 'waspada',
                                        'bg-[#39FF14]/20 text-[#39FF14] border-[#39FF14]': kec.zona === 'aman',
                                    }"
                                >
                                    {{ kec.zona === 'bahaya' ? 'Zona Bahaya' : kec.zona === 'waspada' ? 'Zona Waspada' : 'Aman' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-display font-bold text-[#F4F7F5]">
                                {{ formatAngka(kec.sapi_potong) }} <span class="text-[11px] text-[#8FA1A0] font-normal">ekor</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-display font-bold text-[#F4F7F5]">
                                {{ formatAngka(kec.sapi_perah) }} <span class="text-[11px] text-[#8FA1A0] font-normal">ekor</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-display font-bold text-[#F4F7F5]">
                                {{ formatAngka(kec.kambing) }} <span class="text-[11px] text-[#8FA1A0] font-normal">ekor</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-display font-extrabold" :class="kec.pmk > 0 ? 'text-[#FF5C5C]' : 'text-[#8FA1A0]'">
                                {{ kec.pmk }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-display font-extrabold" :class="kec.lsd > 0 ? 'text-[#FFC53D]' : 'text-[#8FA1A0]'">
                                {{ kec.lsd }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        @click="openEditModal(kec)"
                                        class="w-8 h-8 rounded-10 bg-[#0A0D0E] border border-[#243034] text-[#8FA1A0] hover:text-[#19F5D4] hover:border-[#19F5D4] flex items-center justify-center transition-all"
                                        title="Ubah Data"
                                    >
                                        <Edit2 class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="confirmDelete(kec)"
                                        class="w-8 h-8 rounded-10 bg-[#0A0D0E] border border-[#243034] text-[#8FA1A0] hover:text-[#FF5C5C] hover:border-[#FF5C5C] flex items-center justify-center transition-all"
                                        title="Hapus Data"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 5. PANEL PETA WILAYAH HORIZONTAL (Bab 4.2) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">Tinjau Peta Persebaran Wilayah</h3>
                <p class="text-xs text-[#8FA1A0] mt-0.5">
                    Lihat hasil pembaruan data secara visual spasial pada kanvas WebGIS tematik 27 kecamatan.
                </p>
            </div>
            <button
                @click="$emit('openMap')"
                class="btn-primary-tactile text-xs font-bold gap-2 flex-shrink-0"
            >
                <Map class="w-4 h-4 stroke-[2.5]" />
                <span>Buka WebGIS Peta</span>
                <ArrowRight class="w-4 h-4" />
            </button>
        </section>

        <!-- MODAL FORM TAMBAH / UBAH DATA (Bab 6) -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 bg-[#0A0D0E]/80 backdrop-blur-none flex items-center justify-center p-4"
        >
            <div class="bg-[#10171A] border-2 border-[#F4F7F5] rounded-24 p-6 w-full max-w-lg shadow-[4px_4px_0_#19F5D4] animate-in zoom-in-95 duration-150">
                <h3 class="font-display font-extrabold text-xl text-[#F4F7F5] mb-1">
                    {{ editingKecamatan ? `Ubah Data ${editingKecamatan.nama}` : 'Tambah Data Wilayah Baru' }}
                </h3>
                <p class="text-xs text-[#8FA1A0] mb-4">Masukkan data atribut kuantitatif resmi peternakan.</p>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-[#F4F7F5] mb-1">Nama Kecamatan</label>
                        <input v-model="form.nama" type="text" placeholder="Contoh: Wangon" class="input-tactile w-full text-xs" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#F4F7F5] mb-1">Sapi Potong (ekor)</label>
                            <input v-model="form.sapi_potong" type="number" class="input-tactile w-full text-xs" />
                        </div>
                        <div>
                            <label class="block font-bold text-[#F4F7F5] mb-1">Sapi Perah (ekor)</label>
                            <input v-model="form.sapi_perah" type="number" class="input-tactile w-full text-xs" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#F4F7F5] mb-1">Kambing (ekor)</label>
                            <input v-model="form.kambing" type="number" class="input-tactile w-full text-xs" />
                        </div>
                        <div>
                            <label class="block font-bold text-[#F4F7F5] mb-1">Domba (ekor)</label>
                            <input v-model="form.domba" type="number" class="input-tactile w-full text-xs" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-[#FF5C5C] mb-1">Kasus PMK Aktif</label>
                            <input v-model="form.pmk" type="number" class="input-tactile w-full text-xs border-[#FF5C5C]/50" />
                        </div>
                        <div>
                            <label class="block font-bold text-[#FFC53D] mb-1">Kasus LSD Aktif</label>
                            <input v-model="form.lsd" type="number" class="input-tactile w-full text-xs border-[#FFC53D]/50" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-[#F4F7F5] mb-1">Status Zona</label>
                        <select v-model="form.zona" class="input-tactile w-full text-xs">
                            <option value="aman">Zona Aman (Hijau)</option>
                            <option value="waspada">Zona Waspada (Kuning)</option>
                            <option value="bahaya">Zona Bahaya (Merah Garis)</option>
                        </select>
                    </div>
                </div>

                <!-- Tombol Aksi Dialog (Batal Kiri, Simpan Kanan, Bab 6) -->
                <div class="flex items-center justify-between gap-3 mt-6 pt-4 border-t border-[#243034]">
                    <button @click="isModalOpen = false" class="btn-secondary-tactile text-xs">
                        Batal
                    </button>
                    <button @click="saveFormData" class="btn-primary-tactile text-xs">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS DATA SPESIFIK (Bab 6: Pertanyaan Spesifik) -->
        <div
            v-if="isDeleteOpen && deletingKecamatan"
            class="fixed inset-0 z-50 bg-[#0A0D0E]/80 backdrop-blur-none flex items-center justify-center p-4"
        >
            <div class="bg-[#10171A] border-2 border-[#FF5C5C] rounded-24 p-6 w-full max-w-md shadow-[4px_4px_0_#FF5C5C] animate-in zoom-in-95 duration-150">
                <h3 class="font-display font-extrabold text-lg text-[#F4F7F5] mb-2">
                    Hapus data {{ deletingKecamatan.nama }}?
                </h3>
                <p class="text-xs text-[#8FA1A0] mb-5 leading-relaxed">
                    Data populasi ({{ formatAngka(deletingKecamatan.sapi_potong) }} sapi potong, {{ deletingKecamatan.pmk }} kasus PMK) akan dihapus dari sistem analitik. Aksi ini tidak dapat dibatalkan.
                </p>
                <div class="flex items-center justify-between gap-3">
                    <button @click="isDeleteOpen = false" class="btn-secondary-tactile text-xs">
                        Batal
                    </button>
                    <button @click="executeDelete" class="btn-danger-tactile text-xs">
                        Ya, Hapus Data
                    </button>
                </div>
            </div>
        </div>

        <!-- TOAST NOTIFICATION (Bab 6: Pil Tengah Bawah) -->
        <div
            v-if="toastMsg"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-[#10171A] border-2 border-[#19F5D4] rounded-full px-5 py-2.5 text-xs font-bold text-[#F4F7F5] shadow-[3px_3px_0_#19F5D4] flex items-center gap-2 animate-in slide-in-from-bottom-3 duration-200"
        >
            <Check class="w-4 h-4 text-[#39FF14]" />
            <span>{{ toastMsg }}</span>
        </div>
    </div>
</template>
