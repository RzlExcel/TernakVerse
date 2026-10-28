<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { KECAMATAN_LIST, KecamatanData, formatAngka } from '../../data/banyumasData';
import { getDesaByKecamatan } from '../../data/banyumasDesaData';
import DiagnosticBox from '../../components/ternakverse/DiagnosticBox.vue';
import {
    LayoutDashboard,
    FolderKanban,
    Plus,
    PlusSquare,
    Search,
    SlidersHorizontal,
    CheckCircle2,
    AlertTriangle,
    ShieldAlert,
    FileText,
    FileDown,
    User,
    LogOut,
    ArrowLeft,
    Edit2,
    Trash2,
    Building2,
    Activity,
    Compass,
    MapPin,
    RotateCcw,
    Check,
    X,
    Download,
    Upload,
    Layers
} from 'lucide-vue-next';

// ----------------------------------------------------
// STATE DATA & NAVIGASI TAB ADMIN
// ----------------------------------------------------
type AdminTab = 'dashboard' | 'data' | 'create' | 'ews' | 'rph' | 'laporan' | 'profile';
const currentTab = ref<AdminTab>('dashboard');

// Master Data 27 Kecamatan Banyumas (Sinkron GeoJSON BIG/BPS)
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);

// Search & Modal di Dashboard
const searchQueryDashboard = ref('');
const isImportModalOpen = ref(false);

function handleDashboardSearch() {
    if (searchQueryDashboard.value.trim()) {
        searchQuery.value = searchQueryDashboard.value.trim();
        currentTab.value = 'data';
    }
}

function handleImportSuccess() {
    isImportModalOpen.value = false;
    showToast('Data CSV/Excel berhasil diimpor & tersinkronisasi ke peta.');
}

// ----------------------------------------------------
// AUDIT TRAIL LOG FEED (PRD FR-SYS-01)
// ----------------------------------------------------
const auditLogs = ref([
    { id: 1, user: 'Satrio (Super Admin)', action: 'UPDATE', table: 'populasi_ternak', detail: 'Sinkronisasi populasi Sapi Potong Sumbang (+120 ekor)', time: '2 menit lalu', status: 'SUCCESS' },
    { id: 2, user: 'Dr. Hendra (Medik Vet)', action: 'INSERT', table: 'kasus_penyakit', detail: 'Pencatatan vaksinasi PMK Baturraden 850 dosis', time: '18 menit lalu', status: 'SUCCESS' },
    { id: 3, user: 'Sistem Sinkron', action: 'CALCULATE', table: 'zona_snapshot_kecamatan', detail: 'Kalkulasi zonasi EWS 27 kecamatan: 14 Merah, 7 Kuning, 6 Aman', time: '1 jam lalu', status: 'SUCCESS' },
    { id: 4, user: 'Satrio (Super Admin)', action: 'EXPORT', table: 'laporan_periodik', detail: 'Generate Laporan Excel Rekapitulasi Triwulan II 2026', time: '3 jam lalu', status: 'SUCCESS' },
]);

// ----------------------------------------------------
// TABLE LIST VIEW STATE (Image 3 - Data Peternakan)
// ----------------------------------------------------
const searchQuery = ref('');
const selectedCategoryFilter = ref('semua');
const selectedZonaFilter = ref('semua');
const selectedWabahFilter = ref('semua');
const selectedPoskeswanFilter = ref('semua');
const minPopulasiFilter = ref(0);

const isFilterDropdownOpen = ref(false);
const isExportDropdownOpen = ref(false);
const isFilterModalOpen = ref(false);
const isExportModalOpen = ref(false);

const selectedRowIds = ref<string[]>([]);
const rowsPerPage = ref<number>(10);
const currentPage = ref<number>(1);

// Logika Filter Komprehensif: Kategori, Zonasi EWS, Kasus Wabah, Poskeswan, & Populasi
const filteredKecamatan = computed(() => {
    return kecamatanList.value.filter(k => {
        // 1. Pencarian teks nama kecamatan atau kode wilayah
        const q = searchQuery.value.toLowerCase().trim();
        const matchesQuery = !q ||
                             k.nama.toLowerCase().includes(q) ||
                             k.kode_kec.toLowerCase().includes(q);

        // 2. Filter Kategori Komoditas Ternak
        let matchesCat = true;
        if (selectedCategoryFilter.value === 'sapi_potong') {
            matchesCat = (k.sapi_potong || 0) > 500;
        } else if (selectedCategoryFilter.value === 'sapi_perah') {
            matchesCat = (k.sapi_perah || 0) > 50;
        } else if (selectedCategoryFilter.value === 'kambing_domba') {
            matchesCat = ((k.kambing || 0) + (k.domba || 0)) > 1000;
        } else if (selectedCategoryFilter.value === 'unggas') {
            matchesCat = (k.ayam_broiler || 0) > 5000;
        }

        // 3. Filter Zonasi EWS
        let matchesZona = true;
        if (selectedZonaFilter.value !== 'semua') {
            matchesZona = k.zona === selectedZonaFilter.value;
        }

        // 4. Filter Kasus Wabah PMK / LSD
        let matchesWabah = true;
        if (selectedWabahFilter.value === 'ada_kasus') {
            matchesWabah = ((k.pmk || 0) + (k.lsd || 0)) > 0;
        } else if (selectedWabahFilter.value === 'bebas_kasus') {
            matchesWabah = ((k.pmk || 0) + (k.lsd || 0)) === 0;
        }

        // 5. Filter Fasilitas Poskeswan
        let matchesPoskeswan = true;
        if (selectedPoskeswanFilter.value === 'ada') {
            matchesPoskeswan = (k.poskeswan || 0) > 0;
        } else if (selectedPoskeswanFilter.value === 'tidak_ada') {
            matchesPoskeswan = (k.poskeswan || 0) === 0;
        }

        // 6. Filter Ambang Populasi Minimum
        let matchesPopulasi = true;
        if (minPopulasiFilter.value > 0) {
            const totalTernak = (k.sapi_potong || 0) + (k.sapi_perah || 0) + (k.kambing || 0) + (k.domba || 0);
            matchesPopulasi = totalTernak >= minPopulasiFilter.value;
        }

        return matchesQuery && matchesCat && matchesZona && matchesWabah && matchesPoskeswan && matchesPopulasi;
    });
});

const paginatedKecamatan = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value;
    return filteredKecamatan.value.slice(start, start + rowsPerPage.value);
});

const totalPages = computed(() => {
    return Math.ceil(filteredKecamatan.value.length / rowsPerPage.value) || 1;
});

const hasActiveFilters = computed(() => {
    return searchQuery.value.trim() !== '' ||
           selectedCategoryFilter.value !== 'semua' ||
           selectedZonaFilter.value !== 'semua' ||
           selectedWabahFilter.value !== 'semua' ||
           selectedPoskeswanFilter.value !== 'semua' ||
           minPopulasiFilter.value > 0;
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (searchQuery.value.trim() !== '') count++;
    if (selectedCategoryFilter.value !== 'semua') count++;
    if (selectedZonaFilter.value !== 'semua') count++;
    if (selectedWabahFilter.value !== 'semua') count++;
    if (selectedPoskeswanFilter.value !== 'semua') count++;
    if (minPopulasiFilter.value > 0) count++;
    return count;
});

// Otomatis kembalikan ke halaman 1 jika ada parameter filter yang berubah
watch(
    [searchQuery, selectedCategoryFilter, selectedZonaFilter, selectedWabahFilter, selectedPoskeswanFilter, minPopulasiFilter],
    () => {
        currentPage.value = 1;
    }
);

function toggleSelectAll() {
    if (selectedRowIds.value.length === paginatedKecamatan.value.length) {
        selectedRowIds.value = [];
    } else {
        selectedRowIds.value = paginatedKecamatan.value.map(k => k.id);
    }
}

function toggleSelectRow(id: string) {
    const idx = selectedRowIds.value.indexOf(id);
    if (idx > -1) {
        selectedRowIds.value.splice(idx, 1);
    } else {
        selectedRowIds.value.push(id);
    }
}

// Fitur Reset Filter
function resetFilters() {
    searchQuery.value = '';
    selectedCategoryFilter.value = 'semua';
    selectedZonaFilter.value = 'semua';
    selectedWabahFilter.value = 'semua';
    selectedPoskeswanFilter.value = 'semua';
    minPopulasiFilter.value = 0;
    currentPage.value = 1;
    isFilterDropdownOpen.value = false;
    isFilterModalOpen.value = false;
    showToast('Semua filter & kata kunci pencarian telah di-reset.');
}

// Fitur Export CSV / Excel
function handleExport(format: 'excel' | 'csv') {
    isExportDropdownOpen.value = false;
    isExportModalOpen.value = false;
    const dataToExport = selectedRowIds.value.length > 0
        ? kecamatanList.value.filter(k => selectedRowIds.value.includes(k.id))
        : filteredKecamatan.value;

    if (dataToExport.length === 0) {
        showToast('Tidak ada data yang tersedia untuk diekspor.');
        return;
    }

    const headers = [
        'No',
        'Kode Wilayah',
        'Nama Kecamatan',
        'Status Zonasi EWS',
        'Sapi Potong (Ekor)',
        'Sapi Perah (Ekor)',
        'Kambing (Ekor)',
        'Domba (Ekor)',
        'Ayam Broiler (Ekor)',
        'Total Ternak (Ekor)',
        'Produksi Susu (Liter/Tahun)',
        'Produksi Daging (Kg/Tahun)',
        'Kasus Aktif PMK',
        'Kasus Aktif LSD',
        'Jumlah Poskeswan',
        'Latitude',
        'Longitude',
        'Terakhir Diperbarui'
    ];

    const isExcel = format === 'excel';
    const delimiter = isExcel ? '\t' : ',';

    const rows = dataToExport.map((k, index) => {
        const total = (k.sapi_potong || 0) + (k.sapi_perah || 0) + (k.kambing || 0) + (k.domba || 0) + (k.ayam_broiler || 0);
        const escapeCell = (val: any) => {
            if (val === null || val === undefined) return '';
            const s = String(val);
            if (isExcel) return s;
            if (s.includes(',') || s.includes('"') || s.includes('\n')) {
                return `"${s.replace(/"/g, '""')}"`;
            }
            return s;
        };

        return [
            index + 1,
            escapeCell(k.kode_kec),
            escapeCell(k.nama),
            escapeCell(k.zona.toUpperCase()),
            k.sapi_potong || 0,
            k.sapi_perah || 0,
            k.kambing || 0,
            k.domba || 0,
            k.ayam_broiler || 0,
            total,
            k.susu_liter || 0,
            k.daging_sapi || 0,
            k.pmk || 0,
            k.lsd || 0,
            k.poskeswan || 0,
            k.lat || 0,
            k.lng || 0,
            escapeCell(k.last_updated || '2026-10-15')
        ].join(delimiter);
    });

    // Tambahkan UTF-8 BOM (\uFEFF) agar Microsoft Excel langsung mengenali aksen karakter & format kolom
    const content = '\uFEFF' + [headers.join(delimiter), ...rows].join('\r\n');
    const mimeType = isExcel 
        ? 'application/vnd.ms-excel;charset=utf-8' 
        : 'text/csv;charset=utf-8';
    const extension = isExcel ? 'xls' : 'csv';
    const filename = `TERNAKVERSE_Data_Peternakan_${new Date().toISOString().slice(0, 10)}.${extension}`;

    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    auditLogs.value.unshift({
        id: Date.now(),
        user: 'Satrio (Super Admin)',
        action: 'EXPORT',
        table: 'populasi_ternak',
        detail: `Ekspor ${dataToExport.length} data peternakan ke format ${extension.toUpperCase()}`,
        time: 'Baru saja',
        status: 'SUCCESS'
    });

    showToast(`Berhasil mengekspor ${dataToExport.length} baris data ke berkas ${filename}`);
}

// ----------------------------------------------------
// FORM CREATE / EDIT STATE (Image 1 - Create Message)
// ----------------------------------------------------
const isEditing = ref(false);
const formKecamatanId = ref('');
const formData = ref({
    nama: '',
    kode_kec: '',
    zona: 'aman' as 'bahaya' | 'waspada' | 'aman',
    kategori_utama: 'Sapi Potong',
    sapi_potong: 1000,
    sapi_perah: 150,
    kambing: 1200,
    domba: 300,
    ayam_broiler: 5000,
    susu_liter: 450,
    daging_sapi: 6000,
    pmk: 0,
    lsd: 0,
    poskeswan: 1,
    catatan: 'Data telah diverifikasi oleh Petugas Medik Veteriner Dinas Pertanian Banyumas.',
});

function startCreateNew() {
    isEditing.value = false;
    formKecamatanId.value = '';
    formData.value = {
        nama: '',
        kode_kec: '33.02.',
        zona: 'aman',
        kategori_utama: 'Sapi Potong',
        sapi_potong: 500,
        sapi_perah: 0,
        kambing: 300,
        domba: 100,
        ayam_broiler: 2000,
        susu_liter: 0,
        daging_sapi: 2500,
        pmk: 0,
        lsd: 0,
        poskeswan: 0,
        catatan: 'Pencatatan data survei lapangan periode Triwulan II 2026.',
    };
    currentTab.value = 'create';
}

function startEditKecamatan(kec: KecamatanData) {
    isEditing.value = true;
    formKecamatanId.value = kec.id;
    formData.value = {
        nama: kec.nama,
        kode_kec: kec.kode_kec,
        zona: kec.zona,
        kategori_utama: kec.sapi_perah > 100 ? 'Sapi Perah' : 'Sapi Potong',
        sapi_potong: kec.sapi_potong,
        sapi_perah: kec.sapi_perah,
        kambing: kec.kambing,
        domba: kec.domba,
        ayam_broiler: kec.ayam_broiler,
        susu_liter: kec.susu_liter,
        daging_sapi: kec.daging_sapi,
        pmk: kec.pmk,
        lsd: kec.lsd,
        poskeswan: kec.poskeswan,
        catatan: `Pemutakhiran berkala data statistik Kecamatan ${kec.nama}.`,
    };
    currentTab.value = 'create';
}

function handleSaveForm(createAnother: boolean = false) {
    if (!formData.value.nama) {
        showToast('Nama Kecamatan wajib diisi!');
        return;
    }

    if (isEditing.value && formKecamatanId.value) {
        const idx = kecamatanList.value.findIndex(k => k.id === formKecamatanId.value);
        if (idx !== -1) {
            kecamatanList.value[idx] = {
                ...kecamatanList.value[idx],
                nama: formData.value.nama,
                kode_kec: formData.value.kode_kec,
                zona: formData.value.zona,
                sapi_potong: Number(formData.value.sapi_potong),
                sapi_perah: Number(formData.value.sapi_perah),
                kambing: Number(formData.value.kambing),
                domba: Number(formData.value.domba),
                ayam_broiler: Number(formData.value.ayam_broiler),
                susu_liter: Number(formData.value.susu_liter),
                daging_sapi: Number(formData.value.daging_sapi),
                pmk: Number(formData.value.pmk),
                lsd: Number(formData.value.lsd),
                poskeswan: Number(formData.value.poskeswan),
                last_updated: 'Baru saja'
            };
            auditLogs.value.unshift({
                id: Date.now(),
                user: 'Satrio (Super Admin)',
                action: 'UPDATE',
                table: 'populasi_ternak',
                detail: `Pembaruan data Kecamatan ${formData.value.nama}`,
                time: 'Baru saja',
                status: 'SUCCESS'
            });
            showToast(`Data ${formData.value.nama} berhasil diperbarui.`);
        }
    } else {
        const newId = formData.value.nama.toLowerCase().replace(/\s+/g, '_');
        kecamatanList.value.push({
            id: newId,
            nama: formData.value.nama,
            kode_kec: formData.value.kode_kec,
            zona: formData.value.zona,
            sapi_potong: Number(formData.value.sapi_potong),
            sapi_perah: Number(formData.value.sapi_perah),
            kambing: Number(formData.value.kambing),
            domba: Number(formData.value.domba),
            ayam_broiler: Number(formData.value.ayam_broiler),
            susu_liter: Number(formData.value.susu_liter),
            daging_sapi: Number(formData.value.daging_sapi),
            pmk: Number(formData.value.pmk),
            lsd: Number(formData.value.lsd),
            poskeswan: Number(formData.value.poskeswan),
            lat: -7.5134,
            lng: 109.1762,
            last_updated: 'Baru saja'
        });
        auditLogs.value.unshift({
            id: Date.now(),
            user: 'Satrio (Super Admin)',
            action: 'INSERT',
            table: 'populasi_ternak',
            detail: `Penambahan kecamatan baru ${formData.value.nama}`,
            time: 'Baru saja',
            status: 'SUCCESS'
        });
        showToast(`Kecamatan ${formData.value.nama} berhasil ditambahkan.`);
    }

    if (createAnother) {
        startCreateNew();
    } else {
        currentTab.value = 'data';
    }
}

function deleteKecamatan(kec: KecamatanData) {
    if (confirm(`Hapus data Kecamatan ${kec.nama}? Aksi ini akan dicatat ke audit log.`)) {
        kecamatanList.value = kecamatanList.value.filter(k => k.id !== kec.id);
        auditLogs.value.unshift({
            id: Date.now(),
            user: 'Satrio (Super Admin)',
            action: 'DELETE',
            table: 'populasi_ternak',
            detail: `Menghapus data Kecamatan ${kec.nama}`,
            time: 'Baru saja',
            status: 'WARNING'
        });
        showToast(`Kecamatan ${kec.nama} telah dihapus.`);
    }
}

// ----------------------------------------------------
// DATA RUMAH POTONG HEWAN (PRD FR-MAP-05)
// ----------------------------------------------------
const rphList = ref([
    { id: 1, nama: 'RPH Kertanegara', kecamatan: 'Purwokerto Selatan', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 50, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.4352, lng: 109.2312 },
    { id: 2, nama: 'RPH Ajibarang', kecamatan: 'Ajibarang', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 35, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.4124, lng: 109.0721 },
    { id: 3, nama: 'RPH Sokaraja', kecamatan: 'Sokaraja', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 40, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.4589, lng: 109.2845 },
    { id: 4, nama: 'RPH Wangon', kecamatan: 'Wangon', pengelola: 'UPTD Diskanak', kapasitas: 30, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.5187, lng: 109.0531 },
    { id: 5, nama: 'RPH Baturraden', kecamatan: 'Baturraden', pengelola: 'Swasta Terlisensi', kapasitas: 25, jenis: 'Sapi Perah & Potong', status: 'Aktif Operasional', lat: -7.3120, lng: 109.2241 },
]);

// ----------------------------------------------------
// PARAMETER ZONASI EWS (PRD FR-SYS-02 & FR-ZON-01)
// ----------------------------------------------------
const ewsParams = ref({
    jendelaHari: 14,
    ambangKuningMaks: 5,
    ambangMerahMin: 6,
});

function recalculateZonasi() {
    kecamatanList.value = kecamatanList.value.map(k => {
        const totalWabah = k.pmk + k.lsd;
        let zona: 'bahaya' | 'waspada' | 'aman' = 'aman';
        if (totalWabah >= ewsParams.value.ambangMerahMin) {
            zona = 'bahaya';
        } else if (totalWabah >= 1) {
            zona = 'waspada';
        }
        return { ...k, zona };
    });
    showToast('Rekalkulasi zonasi EWS selesai diterapkan.');
}

// ----------------------------------------------------
// TOAST NOTIFIKASI
// ----------------------------------------------------
const toastMsg = ref<string | null>(null);
function showToast(msg: string) {
    toastMsg.value = msg;
    setTimeout(() => {
        toastMsg.value = null;
    }, 4000);
}

// ----------------------------------------------------
// LOGOUT & NAVIGASI KEMBALI
// ----------------------------------------------------
function handleLogout() {
    router.visit('/internal/login');
}
function goToPublicPortal() {
    router.visit('/');
}
function goToDinasMap() {
    router.visit('/internal/dinas');
}
</script>

<template>
    <Head title="Kelola data peternakan - TERNAKVERSE // SYS_ADMIN" />

    <!-- CONTAINER UTAMA VIEWPORT TINGGI PENUH (h-screen, overflow-hidden agar sidebar tidak ikut terscroll) -->
    <div class="h-screen w-screen overflow-hidden bg-[#0A0D0E] text-[#F4F7F5] flex flex-col font-ui antialiased selection:bg-[#39FF14] selection:text-[#04110A]">
        
        <!-- ==================================================== -->
        <!-- TOP NAVBAR (BBPTUHPT Style: Dark Ink, Blue Square Logo, Search & User Avatar) -->
        <!-- ==================================================== -->
        <header class="h-16 w-full flex-shrink-0 bg-[#0B0F17] border-b border-[#1E293B] px-4 md:px-8 flex items-center justify-between z-40 select-none">
            <!-- Brand Kiri (Logo Kotak Biru Cobalt + Judul + Subtitle) -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-base shadow-sm">
                    T
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-bold text-base tracking-tight text-white">
                            TERNAKVERSE
                        </span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-blue-950/80 text-blue-400 border border-blue-800/60 font-mono">
                            PANEL DISKANAK
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 hidden sm:block leading-none mt-0.5">
                        Pemerintah Kabupaten Banyumas • Ditjen PKH
                    </p>
                </div>
            </div>

            <!-- Bagian Kanan: Search Bar, Notifikasi, Quick Links, & User Badge -->
            <div class="flex items-center gap-3">
                <!-- Search Bar Global -->
                <div class="relative hidden lg:block w-64">
                    <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <input
                        type="text"
                        placeholder="Cari komoditas, wilayah..."
                        class="w-full h-8 bg-[#111827] border border-[#1E293B] rounded-lg pl-8 pr-3 text-xs text-slate-200 placeholder-slate-500 outline-none focus:border-blue-500"
                    />
                </div>

                <!-- Navigasi Cepat Kembali ke Portal Publik -->
                <button
                    @click="goToPublicPortal"
                    class="h-8 px-3 rounded-lg bg-[#111827] border border-[#1E293B] hover:border-blue-500/50 text-xs font-medium text-slate-300 hover:text-white flex items-center gap-1.5 transition-colors"
                    title="Buka Halaman WebGIS Publik"
                >
                    <ArrowLeft class="w-3.5 h-3.5 text-slate-400" />
                    <span class="hidden md:inline">Portal Publik</span>
                </button>

                <!-- Navigasi Cepat Kembali ke WebGIS Dinas -->
                <button
                    @click="goToDinasMap"
                    class="h-8 px-3 rounded-lg bg-[#111827] border border-[#1E293B] hover:border-blue-500/50 text-xs font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1.5 transition-colors"
                    title="Buka Peta WebGIS Kuantitatif Dinas"
                >
                    <Compass class="w-3.5 h-3.5 text-blue-400" />
                    <span>WebGIS Dinas</span>
                </button>

                <!-- Notification Bell -->
                <div class="relative w-8 h-8 rounded-lg bg-[#111827] border border-[#1E293B] flex items-center justify-center text-slate-400 hover:text-white cursor-pointer transition-colors">
                    <span class="w-2 h-2 rounded-full bg-rose-500 absolute top-1.5 right-1.5 ring-2 ring-[#0B0F17]"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>

                <!-- User Badge [S] Super Admin BBPTUHPT / Diskanak -->
                <div
                    @click="currentTab = 'profile'"
                    class="flex items-center gap-2 pl-2 pr-3 py-1 rounded-lg bg-[#111827] border border-[#1E293B] hover:border-blue-500/50 cursor-pointer transition-colors"
                >
                    <div class="w-6 h-6 rounded bg-blue-600 text-white font-bold text-xs flex items-center justify-center">
                        S
                    </div>
                    <span class="text-xs font-medium text-slate-200 hidden sm:inline">Super Admin Diskanak</span>
                    <span class="text-[10px] text-slate-400">▾</span>
                </div>
            </div>
        </header>

        <!-- ==================================================== -->
        <!-- ROW LAYOUT: SIDEBAR PINNED (BBPTUHPT Style Categorized) + MAIN -->
        <!-- ==================================================== -->
        <div class="flex-1 flex w-full overflow-hidden">
            
            <!-- SIDEBAR KIRI (Pinned / Fixed Height) -->
            <aside class="w-64 h-full flex-shrink-0 bg-[#0B0F17] border-r border-[#1E293B] flex flex-col justify-between p-3 select-none z-20">
                
                <!-- BAGIAN MENU ATAS -->
                <div class="space-y-4 overflow-y-auto pr-1">
                    
                    <!-- Kategori 1: MENU UTAMA -->
                    <div class="space-y-1">
                        <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            MENU UTAMA
                        </div>

                        <!-- 1. Dashboard -->
                        <button
                            @click="currentTab = 'dashboard'"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'dashboard'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <LayoutDashboard class="w-4 h-4 flex-shrink-0" :class="currentTab === 'dashboard' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Dashboard</span>
                        </button>

                        <!-- 2. Data Peternakan -->
                        <button
                            @click="currentTab = 'data'"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'data'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <FolderKanban class="w-4 h-4 flex-shrink-0" :class="currentTab === 'data' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Data Peternakan</span>
                        </button>

                        <!-- 3. Form Input Data -->
                        <button
                            @click="startCreateNew"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'create'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <PlusSquare class="w-4 h-4 flex-shrink-0" :class="currentTab === 'create' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Input Data Baru</span>
                        </button>
                    </div>

                    <!-- Kategori 2: MONITORING & FASILITAS -->
                    <div class="space-y-1">
                        <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            MONITORING & FASILITAS
                        </div>

                        <!-- 4. Monitoring EWS Penyakit -->
                        <button
                            @click="currentTab = 'ews'"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'ews'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <ShieldAlert class="w-4 h-4 flex-shrink-0" :class="currentTab === 'ews' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Monitoring EWS</span>
                        </button>

                        <!-- 5. Fasilitas RPH Spasial -->
                        <button
                            @click="currentTab = 'rph'"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'rph'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <Building2 class="w-4 h-4 flex-shrink-0" :class="currentTab === 'rph' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Fasilitas RPH</span>
                        </button>

                        <!-- 6. Ekspor Laporan -->
                        <button
                            @click="currentTab = 'laporan'"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'laporan'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <FileDown class="w-4 h-4 flex-shrink-0" :class="currentTab === 'laporan' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Ekspor Laporan</span>
                        </button>

                        <!-- 7. My Profile -->
                        <button
                            @click="currentTab = 'profile'"
                            class="w-full text-left px-3 h-9 rounded-lg flex items-center gap-3 text-xs font-medium transition-all"
                            :class="currentTab === 'profile'
                                ? 'bg-blue-600/15 text-blue-400 font-semibold border-l-2 border-blue-500'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
                        >
                            <User class="w-4 h-4 flex-shrink-0" :class="currentTab === 'profile' ? 'text-blue-400' : 'text-slate-400'" />
                            <span>Pengaturan Akun</span>
                        </button>
                    </div>
                </div>

                <!-- BAGIAN BAWAH: TAUTAN EKSTERNAL -->
                <div class="pt-3 border-t border-[#1E293B] space-y-1 flex-shrink-0">
                    <div class="px-2 text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-1">
                        TAUTAN EKSTERNAL
                    </div>

                    <!-- Tombol Kembali ke Portal Publik -->
                    <button
                        @click="goToPublicPortal"
                        class="w-full h-8 px-3 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 font-medium text-xs flex items-center gap-2 transition-all"
                    >
                        <ArrowLeft class="w-3.5 h-3.5 text-slate-400" />
                        <span>Lihat Portal Publik</span>
                    </button>

                    <!-- Tombol Kembali ke WebGIS Dinas -->
                    <button
                        @click="goToDinasMap"
                        class="w-full h-8 px-3 rounded-lg text-slate-400 hover:text-blue-400 hover:bg-slate-800/40 font-medium text-xs flex items-center gap-2 transition-all"
                    >
                        <Compass class="w-3.5 h-3.5 text-slate-400" />
                        <span>WebGIS Dinas</span>
                    </button>

                    <!-- Tombol Sign Out Sesi -->
                    <button
                        @click="handleLogout"
                        class="w-full h-8 px-3 rounded-lg text-rose-400 hover:text-rose-300 hover:bg-rose-950/20 font-medium text-xs flex items-center gap-2 transition-all mt-1"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span>Keluar Sesi</span>
                    </button>
                </div>
            </aside>

            <!-- ==================================================== -->
            <!-- MAIN CONTENT AREA: RATA KANAN KIRI PENUH (w-full) & SCROLLABLE -->
            <!-- ==================================================== -->
            <main class="flex-1 h-full overflow-y-auto p-6 md:p-8 bg-[#0B0F17] w-full">

                <!-- ---------------------------------------------------- -->
                <!-- 1. VIEW: DASHBOARD (BBPTUHPT Style) -->
                <!-- ---------------------------------------------------- -->
                <div v-if="currentTab === 'dashboard'" class="w-full space-y-6">
                    
                    <!-- HEADER DASHBOARD: Kelola data peternakan & Tombol Aksi -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
                        <div>
                            <h1 class="font-display font-bold text-2xl md:text-3xl tracking-tight text-white">
                                Panel Operasional Peternakan
                            </h1>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Monitoring populasi komoditas ternak, sebaran wilayah, dan kewaspadaan penyakit Kabupaten Banyumas
                            </p>
                        </div>

                        <!-- Bar Kanan: Cari Catatan + Impor CSV/Excel + Tambah Data -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Input Cari Catatan -->
                            <div class="relative min-w-[200px]">
                                <Search class="w-3.5 h-3.5 absolute left-3 top-3 text-slate-400" />
                                <input
                                    v-model="searchQueryDashboard"
                                    @keyup.enter="handleDashboardSearch"
                                    type="text"
                                    placeholder="Cari komoditas, catatan..."
                                    class="h-9 bg-[#111827] border border-[#1E293B] rounded-lg pl-8 pr-3 text-xs text-white placeholder-slate-500 focus:border-blue-500 outline-none"
                                />
                            </div>

                            <!-- Tombol Impor CSV/Excel -->
                            <button
                                @click="isImportModalOpen = true"
                                class="btn-secondary-tactile h-9 px-3.5 text-xs font-medium gap-2"
                            >
                                <Upload class="w-3.5 h-3.5 text-slate-300" />
                                <span>Impor Data</span>
                            </button>

                            <!-- Tombol + Tambah data (BBPTUHPT Blue Button) -->
                            <button
                                @click="startCreateNew"
                                class="btn-primary-tactile h-9 px-3.5 text-xs font-semibold gap-1.5"
                            >
                                <Plus class="w-4 h-4 stroke-[2.5]" />
                                <span>Tambah Komoditas</span>
                            </button>
                        </div>
                    </div>

                    <!-- BARIS 1: 4 KARTU STATISTIK KPI (BBPTUHPT Style) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                        
                        <!-- 1. Total Sapi -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 6 C4 4, 2 3, 2 3 M20 6 C20 4, 22 3, 22 3 M5 8 L19 8 C20 8, 21 9, 21 11 L21 15 C21 18, 18 20, 15 20 L9 20 C6 20, 3 18, 3 15 L3 11 C3 9, 4 8, 5 8 Z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border border-emerald-500/30 bg-emerald-950/40 text-emerald-400">
                                    +5.0% bulan ini
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-medium block">Total Populasi Sapi</span>
                                <div class="flex items-baseline gap-1.5 mt-0.5">
                                    <span class="font-display font-extrabold text-2xl text-white tracking-tight">34.895</span>
                                    <span class="text-xs text-slate-400 font-mono">ekor</span>
                                </div>
                            </div>
                            <!-- Sparkline Blue -->
                            <div class="w-full h-7 flex items-end mt-3">
                                <svg class="w-full h-6" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 20 L25 17 L50 15 L75 12 L100 6" fill="none" stroke="#3B82F6" stroke-width="2" />
                                    <path d="M0 20 L25 17 L50 15 L75 12 L100 6 L100 24 L0 24 Z" fill="#3B82F6" opacity="0.1" />
                                </svg>
                            </div>
                        </div>

                        <!-- 2. Total Kambing -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M7 3 C6 2, 4 3, 3 5 M17 3 C18 2, 20 3, 21 5 M6 7 L18 7 C19 7, 20 8, 20 10 L19 16 C18.5 18, 16 19.5, 12 19.5 C8 19.5, 5.5 18, 5 16 L4 10 C4 8, 5 7, 6 7 Z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border border-emerald-500/30 bg-emerald-950/40 text-emerald-400">
                                    +3.2% bulan ini
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-medium block">Total Kambing & Domba</span>
                                <div class="flex items-baseline gap-1.5 mt-0.5">
                                    <span class="font-display font-extrabold text-2xl text-white tracking-tight">23.310</span>
                                    <span class="text-xs text-slate-400 font-mono">ekor</span>
                                </div>
                            </div>
                            <!-- Sparkline Emerald -->
                            <div class="w-full h-7 flex items-end mt-3">
                                <svg class="w-full h-6" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 18 L25 15 L50 18 L75 14 L100 8" fill="none" stroke="#10B981" stroke-width="2" />
                                    <path d="M0 18 L25 15 L50 18 L75 14 L100 8 L100 24 L0 24 Z" fill="#10B981" opacity="0.1" />
                                </svg>
                            </div>
                        </div>

                        <!-- 3. Kasus aktif PMK -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <AlertTriangle class="w-4 h-4" />
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border border-emerald-500/30 bg-emerald-950/40 text-emerald-400">
                                    -12% terkendali
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-medium block">Kasus Aktif PMK</span>
                                <div class="flex items-baseline gap-1.5 mt-0.5">
                                    <span class="font-display font-extrabold text-2xl text-white tracking-tight">52</span>
                                    <span class="text-xs text-slate-400 font-mono">kasus</span>
                                </div>
                            </div>
                            <!-- Sparkline Green Trend -->
                            <div class="w-full h-7 flex items-end mt-3">
                                <svg class="w-full h-6" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 12 L20 10 L45 7 L70 14 L100 20" fill="none" stroke="#10B981" stroke-width="2" />
                                    <path d="M0 12 L20 10 L45 7 L70 14 L100 20 L100 24 L0 24 Z" fill="#10B981" opacity="0.1" />
                                </svg>
                            </div>
                        </div>

                        <!-- 4. Kasus aktif LSD -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-700 transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center">
                                    <ShieldAlert class="w-4 h-4" />
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border border-rose-500/30 bg-rose-950/40 text-rose-400">
                                    +9% waspada
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-medium block">Kasus Aktif LSD</span>
                                <div class="flex items-baseline gap-1.5 mt-0.5">
                                    <span class="font-display font-extrabold text-2xl text-white tracking-tight">32</span>
                                    <span class="text-xs text-slate-400 font-mono">kasus</span>
                                </div>
                            </div>
                            <!-- Sparkline Red Trend -->
                            <div class="w-full h-7 flex items-end mt-3">
                                <svg class="w-full h-6" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 18 L25 19 L50 14 L75 13 L100 6" fill="none" stroke="#EF4444" stroke-width="2" />
                                    <path d="M0 18 L25 19 L50 14 L75 13 L100 6 L100 24 L0 24 Z" fill="#EF4444" opacity="0.1" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- BARIS 2: BAR CHART (BBPTUHPT Blue) & LINE CHART -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                        
                        <!-- 1. Bar Chart: Populasi ternak per kecamatan (BBPTUHPT Style) -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 md:p-6 shadow-sm">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-display font-bold text-base text-white">
                                        Tren Distribusi Ternak per Kecamatan
                                    </h3>
                                    <p class="text-xs text-slate-400">5 sentra populasi teratas di Banyumas</p>
                                </div>
                                <!-- Timeframe Selector (BBPTUHPT Image 2) -->
                                <div class="flex items-center gap-1 bg-[#0B0F17] p-1 rounded-lg border border-[#1E293B] text-[10px]">
                                    <span class="px-2 py-0.5 text-slate-400 cursor-pointer hover:text-white">12 Bulan</span>
                                    <span class="px-2 py-0.5 bg-blue-600 text-white rounded font-semibold cursor-pointer">30 Hari</span>
                                    <span class="px-2 py-0.5 text-slate-400 cursor-pointer hover:text-white">7 Hari</span>
                                </div>
                            </div>

                            <!-- Visual SVG Bar Chart Sesuai BBPTUHPT -->
                            <div class="w-full h-[260px]">
                                <svg class="w-full h-full" viewBox="0 0 480 240" preserveAspectRatio="none">
                                    <!-- Garis Grid Putus-putus Horizontal -->
                                    <line x1="45" y1="30" x2="470" y2="30" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                    <text x="35" y="34" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">6.050</text>

                                    <line x1="45" y1="75" x2="470" y2="75" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                    <text x="35" y="79" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">4.538</text>

                                    <line x1="45" y1="120" x2="470" y2="120" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                    <text x="35" y="124" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">3.025</text>

                                    <line x1="45" y1="165" x2="470" y2="165" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                    <text x="35" y="169" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">1.513</text>

                                    <line x1="45" y1="210" x2="470" y2="210" stroke="#1E293B" stroke-width="1" />
                                    <text x="35" y="214" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">0</text>

                                    <!-- Bar 1: Wangon (6.050) - Vivid Royal Blue #2563EB -->
                                    <text x="80" y="22" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700" font-family="'Plus Jakarta Sans', sans-serif">6.050</text>
                                    <rect x="58" y="30" width="44" height="180" rx="4" fill="#2563EB" />
                                    <text x="80" y="228" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Wangon</text>

                                    <!-- Bar 2: Ajibarang (4.880) -->
                                    <text x="165" y="58" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700" font-family="'Plus Jakarta Sans', sans-serif">4.880</text>
                                    <rect x="143" y="65" width="44" height="145" rx="4" fill="#2563EB" />
                                    <text x="165" y="228" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Ajibarang</text>

                                    <!-- Bar 3: Sumbang (4.140) -->
                                    <text x="250" y="80" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700" font-family="'Plus Jakarta Sans', sans-serif">4.140</text>
                                    <rect x="228" y="87" width="44" height="123" rx="4" fill="#2563EB" />
                                    <text x="250" y="228" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Sumbang</text>

                                    <!-- Bar 4: Cikakak (3.240) -->
                                    <text x="335" y="106" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700" font-family="'Plus Jakarta Sans', sans-serif">3.240</text>
                                    <rect x="313" y="114" width="44" height="96" rx="4" fill="#2563EB" />
                                    <text x="335" y="228" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Cikakak</text>

                                    <!-- Bar 5: Gumelar (3.240) -->
                                    <text x="420" y="106" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700" font-family="'Plus Jakarta Sans', sans-serif">3.240</text>
                                    <rect x="398" y="114" width="44" height="96" rx="4" fill="#2563EB" />
                                    <text x="420" y="228" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Gumelar</text>
                                </svg>
                            </div>
                        </div>

                        <!-- 2. Donut Chart: Komposisi Komoditas (BBPTUHPT Style) -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 md:p-6 shadow-sm flex flex-col justify-between">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h3 class="font-display font-bold text-base text-white">
                                        Komposisi Komoditas
                                    </h3>
                                    <p class="text-xs text-slate-400">Porsi distribusi populasi ternak unggulan</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-center justify-center py-2">
                                <!-- Donut Chart SVG -->
                                <div class="relative w-44 h-44 flex items-center justify-center">
                                    <svg class="w-full h-full -rotate-90" viewBox="0 0 210 210">
                                        <!-- Background Ring -->
                                        <circle cx="105" cy="105" r="70" fill="none" stroke="#0B0F17" stroke-width="26" />

                                        <!-- Segment 1: Sapi potong 54% (Royal Blue #2563EB) -->
                                        <circle
                                            cx="105"
                                            cy="105"
                                            r="70"
                                            fill="none"
                                            stroke="#2563EB"
                                            stroke-width="26"
                                            stroke-dasharray="237.5 439.8"
                                            stroke-dashoffset="0"
                                        />

                                        <!-- Segment 2: Sapi perah 6% (Cyan #06B6D4) -->
                                        <circle
                                            cx="105"
                                            cy="105"
                                            r="70"
                                            fill="none"
                                            stroke="#06B6D4"
                                            stroke-width="26"
                                            stroke-dasharray="26.4 439.8"
                                            stroke-dashoffset="-237.5"
                                        />

                                        <!-- Segment 3: Kambing 40% (Emerald #10B981) -->
                                        <circle
                                            cx="105"
                                            cy="105"
                                            r="70"
                                            fill="none"
                                            stroke="#10B981"
                                            stroke-width="26"
                                            stroke-dasharray="175.9 439.8"
                                            stroke-dashoffset="-263.9"
                                        />
                                    </svg>

                                    <!-- Angka Besar di Tengah Lubang Donut (BBPTUHPT Style) -->
                                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Total Ternak</span>
                                        <span class="font-display font-black text-2xl text-white leading-none my-0.5">
                                            58.205
                                        </span>
                                        <span class="text-[10px] text-slate-400">Unit & Dosis</span>
                                    </div>
                                </div>

                                <!-- Horizontal Legend Below Donut (BBPTUHPT Image 2) -->
                                <div class="flex flex-wrap items-center justify-center gap-4 mt-4 text-xs font-medium">
                                    <span class="inline-flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2.5 h-2.5 rounded-sm bg-[#2563EB]"></span>
                                        Sapi Potong (54%)
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2.5 h-2.5 rounded-sm bg-[#06B6D4]"></span>
                                        Sapi Perah (6%)
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2.5 h-2.5 rounded-sm bg-[#10B981]"></span>
                                        Kambing (40%)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BARIS 3: SEBARAN WILAYAH & AKTIVITAS TERBARU (BBPTUHPT Style) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                        
                        <!-- 1. Sebaran Wilayah Distribusi (Horizontal Progress Bars - BBPTUHPT Image 2) -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 md:p-6 shadow-sm">
                            <div class="mb-5">
                                <h3 class="font-display font-bold text-base text-white">
                                    Sebaran Wilayah Distribusi
                                </h3>
                                <p class="text-xs text-slate-400">Sentra peternakan & dinas kecamatan prioritas</p>
                            </div>

                            <div class="space-y-4">
                                <!-- Wilayah 1 -->
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="font-semibold text-slate-200">Banyumas Barat (Wangon, Ajibarang)</span>
                                        <span class="font-bold text-blue-400">48%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-[#0B0F17] overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: 48%"></div>
                                    </div>
                                </div>

                                <!-- Wilayah 2 -->
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="font-semibold text-slate-200">Banyumas Timur (Sumbang, Kembaran)</span>
                                        <span class="font-bold text-blue-400">26%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-[#0B0F17] overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: 26%"></div>
                                    </div>
                                </div>

                                <!-- Wilayah 3 -->
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="font-semibold text-slate-200">Banyumas Selatan (Tambak, Sumpiuh)</span>
                                        <span class="font-bold text-blue-400">16%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-[#0B0F17] overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: 16%"></div>
                                    </div>
                                </div>

                                <!-- Wilayah 4 -->
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="font-semibold text-slate-200">Banyumas Utara (Baturraden, Cilongok)</span>
                                        <span class="font-bold text-blue-400">10%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-[#0B0F17] overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: 10%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Pesanan & Aktivitas Komoditas Terbaru (BBPTUHPT Image 2) -->
                        <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 md:p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-display font-bold text-base text-white">
                                        Aktivitas & Mutasi Ternak
                                    </h3>
                                    <p class="text-xs text-slate-400">Log operasional data dan verifikasi lapangan</p>
                                </div>
                                <a href="#" @click.prevent="currentTab = 'data'" class="text-xs font-semibold text-blue-400 hover:text-blue-300 transition-colors">
                                    Lihat Semua &gt;
                                </a>
                            </div>

                            <div class="divide-y divide-[#1E293B] text-xs">
                                <!-- Feed 1 -->
                                <div class="py-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-600/15 border border-blue-500/30 flex items-center justify-center text-blue-400 flex-shrink-0">
                                            <ShieldAlert class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <p class="text-slate-200 font-semibold leading-tight">Update Kasus PMK Ajibarang</p>
                                            <p class="text-[11px] text-slate-400">Budi Santoso • 12 Kasus aktif</p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                            Diproses Balai
                                        </span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5">26 Okt, 16:02</span>
                                    </div>
                                </div>

                                <!-- Feed 2 -->
                                <div class="py-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-600/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                                            <CheckCircle2 class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <p class="text-slate-200 font-semibold leading-tight">Verifikasi Laporan Tambak</p>
                                            <p class="text-[11px] text-slate-400">Siti Aminah • 5 Laporan selesai</p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            Disetujui
                                        </span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5">26 Okt, 14:18</span>
                                    </div>
                                </div>

                                <!-- Feed 3 -->
                                <div class="py-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-600/15 border border-blue-500/30 flex items-center justify-center text-blue-400 flex-shrink-0">
                                            <FileText class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <p class="text-slate-200 font-semibold leading-tight">Sinkronisasi Batas RBI</p>
                                            <p class="text-[11px] text-slate-400">Agus • 27 Kecamatan valid</p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                            Diproses Balai
                                        </span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5">25 Okt, 17:02</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BARIS 4: LINE CHART TREN KASUS PENYAKIT (PMK & LSD) -->
                    <div class="bg-[#111827] border border-[#1E293B] rounded-xl p-5 md:p-6 shadow-sm">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="font-display font-bold text-base text-white">
                                    Tren Kasus Penyakit Hewan (EWS)
                                </h3>
                                <p class="text-xs text-slate-400">Monitoring perkembangan kasus PMK & LSD 6 bulan terakhir</p>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-bold">
                                <span class="flex items-center gap-1.5 text-rose-400">
                                    <span class="w-2.5 h-2.5 rounded-sm bg-rose-500"></span>
                                    PMK
                                </span>
                                <span class="flex items-center gap-1.5 text-amber-400">
                                    <span class="w-2.5 h-2.5 rounded-sm bg-amber-500"></span>
                                    LSD
                                </span>
                            </div>
                        </div>

                        <!-- Visual SVG Line Chart -->
                        <div class="w-full h-[220px]">
                            <svg class="w-full h-full" viewBox="0 0 960 220" preserveAspectRatio="none">
                                <!-- Garis Grid Putus-putus Horizontal -->
                                <line x1="40" y1="20" x2="940" y2="20" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                <text x="30" y="24" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">60</text>

                                <line x1="40" y1="65" x2="940" y2="65" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                <text x="30" y="69" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">45</text>

                                <line x1="40" y1="110" x2="940" y2="110" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                <text x="30" y="114" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">30</text>

                                <line x1="40" y1="155" x2="940" y2="155" stroke="#1E293B" stroke-width="1" stroke-dasharray="3, 6" />
                                <text x="30" y="159" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">15</text>

                                <line x1="40" y1="195" x2="940" y2="195" stroke="#1E293B" stroke-width="1" />
                                <text x="30" y="199" text-anchor="end" fill="#64748B" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">0</text>

                                <!-- Label Sumbu X (Bulan) -->
                                <text x="100" y="212" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Mei</text>
                                <text x="260" y="212" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Jun</text>
                                <text x="420" y="212" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Jul</text>
                                <text x="580" y="212" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Agu</text>
                                <text x="740" y="212" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Sep</text>
                                <text x="900" y="212" text-anchor="middle" fill="#94A3B8" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Okt</text>

                                <!-- Garis PMK Merah (#F43F5E) -->
                                <polyline
                                    points="100,150 260,130 420,115 580,120 740,100 900,45"
                                    fill="none"
                                    stroke="#F43F5E"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle cx="100" cy="150" r="4" fill="#F43F5E" stroke="#111827" stroke-width="2" />
                                <circle cx="260" cy="130" r="4" fill="#F43F5E" stroke="#111827" stroke-width="2" />
                                <circle cx="420" cy="115" r="4" fill="#F43F5E" stroke="#111827" stroke-width="2" />
                                <circle cx="580" cy="120" r="4" fill="#F43F5E" stroke="#111827" stroke-width="2" />
                                <circle cx="740" cy="100" r="4" fill="#F43F5E" stroke="#111827" stroke-width="2" />
                                <circle cx="900" cy="45" r="4.5" fill="#F43F5E" stroke="#111827" stroke-width="2" />

                                <!-- Garis LSD Kuning (#F59E0B) -->
                                <polyline
                                    points="100,175 260,165 420,170 580,155 740,160 900,105"
                                    fill="none"
                                    stroke="#F59E0B"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle cx="100" cy="175" r="4" fill="#F59E0B" stroke="#111827" stroke-width="2" />
                                <circle cx="260" cy="165" r="4" fill="#F59E0B" stroke="#111827" stroke-width="2" />
                                <circle cx="420" cy="170" r="4" fill="#F59E0B" stroke="#111827" stroke-width="2" />
                                <circle cx="580" cy="155" r="4" fill="#F59E0B" stroke="#111827" stroke-width="2" />
                                <circle cx="740" cy="160" r="4" fill="#F59E0B" stroke="#111827" stroke-width="2" />
                                <circle cx="900" cy="105" r="4.5" fill="#F59E0B" stroke="#111827" stroke-width="2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 2. VIEW: DATA PETERNAKAN / TABLE LIST (BBPTUHPT Enterprise Dark) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'data'" class="w-full space-y-4">
                    
                    <!-- Breadcrumbs -->
                    <div class="text-xs font-semibold text-slate-400">
                        Beranda &gt; Katalog Komoditas &gt; Basis Data Wilayah
                    </div>

                    <!-- Header & Tombol Aksi + TAMBAH DATA BARU (BBPTUHPT Image 4) -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                        <div>
                            <h1 class="font-display font-extrabold text-2xl tracking-tight text-white">
                                Katalog Komoditas &amp; Bibit Ternak
                            </h1>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Kelola data rumpun ternak sapi perah FH, sapi potong, kambing, dan status zonasi EWS 27 kecamatan
                            </p>
                        </div>

                        <!-- Tombol Biru + TAMBAH DATA (BBPTUHPT Style) -->
                        <button
                            @click="startCreateNew"
                            class="bg-blue-600 hover:bg-blue-500 text-white font-medium px-4 py-2.5 rounded-lg text-xs flex items-center gap-2 shadow-sm transition-all self-start sm:self-auto cursor-pointer"
                        >
                            <Plus class="w-4 h-4 stroke-[2.5]" />
                            <span>+ Tambah Komoditas Baru</span>
                        </button>
                    </div>

                    <!-- CONTAINER TABEL (BBPTUHPT High-Contrast Solid Dark) -->
                    <div class="w-full bg-[#111827] border border-[#1E293B] rounded-xl overflow-hidden shadow-sm">
                        
                        <!-- Header Kontrol Tabel: Search, Filter Dropdowns, Reset Filter, & Export CSV/Excel -->
                        <div class="p-3.5 md:p-4 border-b border-[#1E293B] flex flex-wrap lg:flex-nowrap items-center justify-between gap-3 w-full bg-[#0B0F17]/60 select-none">
                            
                            <!-- SISI KIRI: Search + Dropdown Filter + Reset Filter -->
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 flex-1 min-w-0">
                                
                                <!-- 1. Search Bar (BBPTUHPT Image 4) -->
                                <div class="relative w-full sm:w-72 flex-shrink-0">
                                    <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari berdasarkan nama komoditas atau kode..."
                                        class="w-full h-9 bg-[#0B0F17] border border-[#1E293B] rounded-lg pl-9 pr-8 text-xs text-white placeholder-slate-500 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        @click="searchQuery = ''"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-400 transition-colors"
                                        title="Hapus kata kunci pencarian"
                                    >
                                        <X class="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <!-- 2. Dropdown Filter Kategori Komoditas Ternak -->
                                <div class="relative w-44 flex-shrink-0">
                                    <select
                                        v-model="selectedCategoryFilter"
                                        class="w-full h-9 bg-[#0B0F17] border border-[#1E293B] rounded-lg pl-3 pr-7 text-xs font-medium text-slate-300 outline-none cursor-pointer transition-all appearance-none truncate flex items-center"
                                        title="Filter Kategori Komoditas"
                                    >
                                        <option value="semua" class="bg-[#111827] text-slate-200">Semua Kategori</option>
                                        <option value="sapi_potong" class="bg-[#111827] text-slate-200">Sapi Potong (&gt;500)</option>
                                        <option value="sapi_perah" class="bg-[#111827] text-slate-200">Sapi Perah (&gt;50)</option>
                                        <option value="kambing_domba" class="bg-[#111827] text-slate-200">Kambing / Domba (&gt;1rb)</option>
                                        <option value="unggas" class="bg-[#111827] text-slate-200">Ayam Broiler (&gt;5rb)</option>
                                    </select>
                                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">
                                        ▼
                                    </div>
                                </div>

                                <!-- 3. Dropdown Filter Status Zonasi EWS -->
                                <div class="relative w-44 flex-shrink-0">
                                    <select
                                        v-model="selectedZonaFilter"
                                        class="w-full h-9 bg-[#0B0F17] border border-[#1E293B] rounded-lg pl-3 pr-7 text-xs font-medium text-slate-300 outline-none cursor-pointer transition-all appearance-none truncate flex items-center"
                                        title="Filter Status Zonasi EWS"
                                    >
                                        <option value="semua" class="bg-[#111827] text-slate-200">Semua Status Zonasi</option>
                                        <option value="bahaya" class="bg-[#111827] text-slate-200">🔴 Zona Merah (Bahaya)</option>
                                        <option value="waspada" class="bg-[#111827] text-slate-200">🟡 Zona Kuning (Waspada)</option>
                                        <option value="aman" class="bg-[#111827] text-slate-200">🟢 Zona Hijau (Aman)</option>
                                    </select>
                                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">
                                        ▼
                                    </div>
                                </div>

                                <!-- 4. Tombol Buka Modal Parameter Filter Lanjutan -->
                                <button
                                    @click="isFilterModalOpen = true"
                                    class="h-9 px-3 rounded-lg border border-[#1E293B] bg-[#0B0F17] text-xs font-medium text-slate-300 hover:border-blue-500/50 flex items-center gap-1.5 transition-all select-none flex-shrink-0 cursor-pointer"
                                    title="Buka panel parameter filter lanjutan"
                                >
                                    <SlidersHorizontal class="w-3.5 h-3.5 text-blue-400 flex-shrink-0" />
                                    <span>Filter Lanjutan</span>
                                </button>

                                <!-- 5. Tombol Reset Filter -->
                                <button
                                    v-if="hasActiveFilters"
                                    @click="resetFilters"
                                    class="h-9 px-3 rounded-lg border border-rose-500/40 bg-rose-500/10 text-xs font-medium text-rose-300 hover:bg-rose-500/20 flex items-center gap-1.5 transition-all select-none whitespace-nowrap flex-shrink-0 cursor-pointer"
                                    title="Reset semua filter"
                                >
                                    <RotateCcw class="w-3.5 h-3.5 flex-shrink-0" />
                                    <span>Reset ({{ activeFilterCount }})</span>
                                </button>
                            </div>

                            <!-- SISI KANAN: Fitur Export CSV / Excel -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    @click="isExportModalOpen = true"
                                    class="h-9 px-3 rounded-lg border border-[#1E293B] bg-[#0B0F17] text-xs font-semibold text-slate-300 hover:text-white hover:border-slate-600 flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                                    title="Ekspor data peternakan ke berkas CSV atau Excel"
                                >
                                    <Download class="w-3.5 h-3.5 text-blue-400" />
                                    <span>Ekspor</span>
                                    <span class="text-[10px] text-slate-500 font-mono">({{ selectedRowIds.length > 0 ? selectedRowIds.length : filteredKecamatan.length }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- TABEL DATA (BBPTUHPT High-Contrast Format) -->
                        <div class="w-full overflow-x-auto">
                            <table class="w-full text-left text-xs font-ui">
                                <thead>
                                    <tr class="border-b border-[#1E293B] text-slate-400 bg-[#0B0F17]/90 text-[11px] font-bold uppercase tracking-wider select-none">
                                        <th class="py-3 px-4 w-12">
                                            <input
                                                type="checkbox"
                                                :checked="selectedRowIds.length > 0 && selectedRowIds.length === paginatedKecamatan.length"
                                                @change="toggleSelectAll"
                                                class="w-4 h-4 rounded bg-[#0B0F17] border border-[#1E293B] text-blue-600 focus:ring-0 cursor-pointer"
                                            />
                                        </th>
                                        <th class="py-3 px-4 text-slate-300">KOMODITAS / KECAMATAN</th>
                                        <th class="py-3 px-4 text-slate-300">KATEGORI</th>
                                        <th class="py-3 px-4 text-slate-300">POPULASI (EKOR)</th>
                                        <th class="py-3 px-4 text-slate-300">TAHUN</th>
                                        <th class="py-3 px-4 text-slate-300">STATUS ZONA EWS</th>
                                        <th class="py-3 px-4 text-slate-300">TERAKHIR DIPERBARUI</th>
                                        <th class="py-3 px-4 text-right text-slate-300">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#1E293B]/60">
                                    <tr
                                        v-for="kec in paginatedKecamatan"
                                        :key="kec.id"
                                        class="hover:bg-[#1E293B]/20 transition-colors"
                                    >
                                        <!-- Checkbox -->
                                        <td class="py-3.5 px-4">
                                            <input
                                                type="checkbox"
                                                :checked="selectedRowIds.includes(kec.id)"
                                                @change="toggleSelectRow(kec.id)"
                                                class="w-4 h-4 rounded bg-[#0B0F17] border border-[#1E293B] text-blue-600 focus:ring-0 cursor-pointer"
                                            />
                                        </td>

                                        <!-- Title (Nama Kecamatan + Subtitle) -->
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-sm text-white">
                                                {{ kec.nama }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                                <span>KODE: {{ kec.kode_kec }}</span>
                                                <span>•</span>
                                                <span>{{ getDesaByKecamatan(kec.id).length }} Desa</span>
                                            </div>
                                        </td>

                                        <!-- Category -->
                                        <td class="py-3.5 px-4">
                                            <span class="text-slate-300 font-medium">
                                                {{ kec.sapi_perah > 50 ? 'Sapi Perah Friesian Holstein' : 'Sapi Potong Unggul' }}
                                            </span>
                                        </td>

                                        <!-- Populasi Ekor -->
                                        <td class="py-3.5 px-4 font-mono font-bold text-white text-xs">
                                            {{ formatAngka(kec.sapi_potong) }} ekor
                                        </td>

                                        <!-- Year -->
                                        <td class="py-3.5 px-4 text-slate-400">
                                            2026
                                        </td>

                                        <!-- Status Zona EWS (BBPTUHPT Badge Style) -->
                                        <td class="py-3.5 px-4">
                                            <span
                                                v-if="kec.zona === 'aman'"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-emerald-500/30 bg-emerald-500/10 text-emerald-400"
                                            >
                                                AKTIF / AMAN
                                            </span>
                                            <span
                                                v-else-if="kec.zona === 'waspada'"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-amber-500/30 bg-amber-500/10 text-amber-400"
                                            >
                                                WASPADA
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-rose-500/30 bg-rose-500/10 text-rose-400"
                                            >
                                                BAHAYA
                                            </span>
                                        </td>

                                        <!-- Updated At -->
                                        <td class="py-3.5 px-4 text-slate-400 text-xs">
                                            {{ kec.last_updated }}
                                        </td>

                                        <!-- Actions (BBPTUHPT Image 4: Edit Hapus) -->
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-3 font-semibold text-xs">
                                                <button
                                                    @click="startEditKecamatan(kec)"
                                                    class="text-blue-400 hover:text-blue-300 transition-colors cursor-pointer"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    @click="deleteKecamatan(kec)"
                                                    class="text-rose-400 hover:text-rose-300 transition-colors cursor-pointer"
                                                    title="Hapus Data"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="paginatedKecamatan.length === 0">
                                        <td colspan="8" class="text-center py-10 text-slate-400">
                                            Tidak ada data komoditas yang sesuai pencarian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- FOOTER TABEL (BBPTUHPT Style) -->
                        <div class="p-3.5 md:p-4 border-t border-[#1E293B] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 w-full bg-[#0B0F17]/40">
                            <div class="font-medium">
                                Showing {{ (currentPage - 1) * rowsPerPage + 1 }} to {{ Math.min(currentPage * rowsPerPage, filteredKecamatan.length) }} of {{ filteredKecamatan.length }} results
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 bg-[#0B0F17] border border-[#1E293B] px-3 py-1 rounded-lg">
                                    <span>Per page</span>
                                    <select
                                        v-model="rowsPerPage"
                                        class="bg-transparent text-white font-semibold outline-none cursor-pointer"
                                    >
                                        <option :value="5" class="bg-[#111827]">5</option>
                                        <option :value="10" class="bg-[#111827]">10</option>
                                        <option :value="20" class="bg-[#111827]">20</option>
                                        <option :value="50" class="bg-[#111827]">50</option>
                                    </select>
                                </div>

                                <!-- Pagination Controls -->
                                <div class="flex items-center gap-1.5">
                                    <button
                                        @click="currentPage = Math.max(1, currentPage - 1)"
                                        :disabled="currentPage === 1"
                                        class="h-8 px-2.5 rounded-lg border border-[#1E293B] bg-[#0B0F17] text-slate-300 hover:text-white disabled:opacity-40 cursor-pointer"
                                    >
                                        &lt;
                                    </button>
                                    <span class="px-2 text-white font-semibold">{{ currentPage }} / {{ totalPages }}</span>
                                    <button
                                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                        :disabled="currentPage === totalPages"
                                        class="h-8 px-2.5 rounded-lg border border-[#1E293B] bg-[#0B0F17] text-slate-300 hover:text-white disabled:opacity-40 cursor-pointer"
                                    >
                                        &gt;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 3. VIEW: FORM CREATE / EDIT (Full Width Rata Kanan Kiri) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'create'" class="w-full space-y-4">
                    
                    <!-- Breadcrumbs -->
                    <div class="text-xs font-semibold text-[#8FA1A0]">
                        Contact Messages &gt; Create
                    </div>

                    <!-- Header -->
                    <div>
                        <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                            {{ isEditing ? 'Edit Contact Message / Data' : 'Create Contact Message' }}
                        </h1>
                    </div>

                    <!-- FORM GRID FULL WIDTH -->
                    <div class="w-full glass-card p-6 md:p-8 space-y-6 shadow-2xl">
                        
                        <!-- Row 1: 2 Kolom Seimbang Rata Kanan Kiri (Name* & Email*) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                            <!-- Field 1: Name* -->
                            <div class="w-full">
                                <label class="block text-xs font-semibold text-slate-300 mb-2">
                                    Name / Nama Wilayah<span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="formData.nama"
                                    type="text"
                                    placeholder="Contoh: Kecamatan Sumbang"
                                    class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500/30 transition-all"
                                />
                            </div>

                            <!-- Field 2: Email* (Kode Kemendagri Wilayah) -->
                            <div class="w-full">
                                <label class="block text-xs font-semibold text-slate-300 mb-2">
                                    Kode Kemendagri Wilayah<span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="formData.kode_kec"
                                    type="text"
                                    placeholder="Contoh: 33.02.21"
                                    class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500/30 transition-all font-mono"
                                />
                            </div>
                        </div>

                        <!-- Row 2: 2 Kolom Seimbang (Project Type + Angka di Kiri, Message/Catatan di Kanan) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                            <!-- Left Column: Project Type Dropdown & Angka Populasi -->
                            <div class="space-y-4 w-full">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-2">
                                        Komoditas Unggulan
                                    </label>
                                    <select
                                        v-model="formData.kategori_utama"
                                        class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60 cursor-pointer"
                                    >
                                        <option value="Sapi Potong" class="bg-[#0F171B]">Sapi Potong Pedaging</option>
                                        <option value="Sapi Perah" class="bg-[#0F171B]">Sapi Perah Penghasil Susu</option>
                                        <option value="Kambing / Domba" class="bg-[#0F171B]">Kambing & Domba</option>
                                        <option value="Ayam Broiler" class="bg-[#0F171B]">Unggas / Ayam Broiler</option>
                                    </select>
                                </div>

                                <!-- Grid Angka Populasi & Kasus -->
                                <div class="grid grid-cols-2 gap-3 w-full">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Populasi Sapi Potong</label>
                                        <input
                                            v-model.number="formData.sapi_potong"
                                            type="number"
                                            class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60 font-mono"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Populasi Sapi Perah</label>
                                        <input
                                            v-model.number="formData.sapi_perah"
                                            type="number"
                                            class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60 font-mono"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-rose-400 mb-1">Kasus Aktif PMK</label>
                                        <input
                                            v-model.number="formData.pmk"
                                            type="number"
                                            class="w-full h-10 bg-[#080C0E]/70 border border-rose-500/30 rounded-10 px-3 text-sm text-rose-400 font-bold outline-none focus:border-rose-400 font-mono"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-amber-400 mb-1">Kasus Aktif LSD</label>
                                        <input
                                            v-model.number="formData.lsd"
                                            type="number"
                                            class="w-full h-10 bg-[#080C0E]/70 border border-amber-500/30 rounded-10 px-3 text-sm text-amber-400 font-bold outline-none focus:border-amber-400 font-mono"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Status Zonasi EWS</label>
                                    <select
                                        v-model="formData.zona"
                                        class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60"
                                    >
                                        <option value="aman" class="bg-[#0F171B]">Zona Hijau (Aman / Bebas Wabah)</option>
                                        <option value="waspada" class="bg-[#0F171B]">Zona Kuning (Waspada)</option>
                                        <option value="bahaya" class="bg-[#0F171B]">Zona Merah (Tertular / Bahaya)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Right Column: Message* (Catatan Lapangan Textarea) -->
                            <div class="flex flex-col w-full">
                                <label class="block text-xs font-semibold text-slate-300 mb-2">
                                    Catatan Verifikasi Lapangan<span class="text-rose-400">*</span>
                                </label>
                                <textarea
                                    v-model="formData.catatan"
                                    rows="9"
                                    placeholder="Tuliskan catatan verifikasi lapangan, riwayat vaksinasi, atau anjuran biosekuriti dinas..."
                                    class="w-full flex-1 min-h-[200px] bg-[#080C0E]/70 border border-white/10 rounded-10 p-3.5 text-sm text-[#F4F7F5] outline-none focus:border-emerald-500/60 focus:ring-1 focus:ring-emerald-500/30 resize-y transition-all"
                                ></textarea>
                            </div>
                        </div>

                        <!-- TOMBOL AKSI: CREATE, Create & create another, Cancel -->
                        <div class="flex flex-wrap items-center gap-3 pt-5 border-t border-white/10 w-full">
                            <!-- CREATE Button -->
                            <button
                                @click="handleSaveForm(false)"
                                class="btn-primary-tactile h-10 px-6 text-xs font-bold tracking-wider"
                            >
                                SIMPAN PERUBAHAN
                            </button>

                            <!-- Create & create another -->
                            <button
                                @click="handleSaveForm(true)"
                                class="btn-secondary-tactile h-10 px-4 text-xs font-semibold"
                            >
                                Simpan & Tambah Lagi
                            </button>

                            <!-- Cancel -->
                            <button
                                @click="currentTab = 'data'"
                                class="h-10 px-4 rounded-10 border border-white/10 text-slate-400 hover:text-slate-200 hover:border-white/20 text-xs font-semibold transition-all"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 4. VIEW: MONITORING EWS & PARAMETER ZONASI (PRD FR-ZON) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'ews'" class="w-full space-y-6">
                    <div>
                        <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                            Monitoring Kewaspadaan Dini Wabah (EWS)
                        </h1>
                        <p class="text-xs text-[#8FA1A0] mt-1">
                            Sistem Peringatan Dini Penyakit Mulut dan Kuku (PMK) & Lumpy Skin Disease (LSD)
                        </p>
                    </div>

                    <!-- Parameter Konfigurasi Ambang Batas (PRD FR-SYS-02) -->
                    <div class="w-full glass-card p-6 shadow-xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-white/10">
                            <h3 class="font-display font-bold text-lg text-[#F4F7F5] flex items-center gap-2">
                                <ShieldAlert class="w-5 h-5 text-emerald-400" />
                                <span>Konfigurasi Ambang Batas Zonasi Risiko (FR-SYS-02)</span>
                            </h3>
                            <button
                                @click="recalculateZonasi"
                                class="btn-primary-tactile h-9 px-4 text-xs font-semibold gap-1.5"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span>Terapkan & Rekalkulasi Batch</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs w-full">
                            <div class="p-4 bg-[#080C0E]/60 border border-white/10 rounded-12">
                                <span class="text-slate-400 font-semibold block mb-1">Jendela Hari Analisis</span>
                                <input
                                    v-model.number="ewsParams.jendelaHari"
                                    type="number"
                                    class="w-full bg-transparent text-[#F4F7F5] font-display font-extrabold text-2xl outline-none"
                                />
                                <span class="text-[10px] text-slate-500">Default: 14 hari terakhir</span>
                            </div>

                            <div class="p-4 bg-amber-500/5 border border-amber-500/30 rounded-12">
                                <span class="text-amber-400 font-semibold block mb-1">Ambang Kuning (Waspada)</span>
                                <input
                                    v-model.number="ewsParams.ambangKuningMaks"
                                    type="number"
                                    class="w-full bg-transparent text-amber-400 font-display font-extrabold text-2xl outline-none"
                                />
                                <span class="text-[10px] text-slate-500">1 s.d. 5 kasus aktif</span>
                            </div>

                            <div class="p-4 bg-rose-500/5 border border-rose-500/30 rounded-12">
                                <span class="text-rose-400 font-semibold block mb-1">Ambang Merah (Tertular)</span>
                                <input
                                    v-model.number="ewsParams.ambangMerahMin"
                                    type="number"
                                    class="w-full bg-transparent text-rose-400 font-display font-extrabold text-2xl outline-none"
                                />
                                <span class="text-[10px] text-slate-500">&gt; 5 kasus aktif</span>
                            </div>
                        </div>
                    </div>

                    <!-- Snapshot 27 Kecamatan Zonasi -->
                    <div class="w-full glass-card p-6 shadow-xl">
                        <h3 class="font-display font-bold text-lg text-[#F4F7F5] mb-4">
                            Snapshot Zonasi 27 Kecamatan Banyumas (Tahun 2026)
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5 text-xs w-full">
                            <div
                                v-for="kec in kecamatanList"
                                :key="kec.id"
                                class="p-2.5 rounded-10 border flex items-center justify-between transition-colors"
                                :class="kec.zona === 'bahaya' ? 'bg-rose-500/10 border-rose-500/30 text-rose-300' : (kec.zona === 'waspada' ? 'bg-amber-500/10 border-amber-500/30 text-amber-300' : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300')"
                            >
                                <span class="font-semibold truncate">{{ kec.nama }}</span>
                                <span class="text-[9px] uppercase font-bold tracking-wider opacity-80">{{ kec.zona }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 5. VIEW: FASILITAS RPH SPASIAL (PRD FR-MAP-05) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'rph'" class="w-full space-y-6">
                    <div>
                        <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                            Sebaran Rumah Potong Hewan (RPH) Banyumas
                        </h1>
                        <p class="text-xs text-slate-400 mt-1">
                            Mandat KAK Geospasial Peternakan 2026: Fasilitas Pemotongan Resmi & RPH Swasta
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 w-full">
                        <div
                            v-for="rph in rphList"
                            :key="rph.id"
                            class="glass-card p-5 space-y-3 hover:border-emerald-500/40 hover:-translate-y-0.5 transition-all shadow-lg"
                        >
                            <div class="flex items-start justify-between">
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">{{ rph.nama }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold text-[10px]">
                                    {{ rph.status }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 space-y-1.5">
                                <div><b class="text-slate-200">Kecamatan:</b> {{ rph.kecamatan }}</div>
                                <div><b class="text-slate-200">Pengelola:</b> {{ rph.pengelola }}</div>
                                <div><b class="text-slate-200">Kapasitas:</b> {{ rph.kapasitas }} ekor/hari ({{ rph.jenis }})</div>
                                <div class="text-[11px] text-teal-400 font-mono font-medium">GPS: {{ rph.lat }}, {{ rph.lng }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 6. VIEW: EKSPOR LAPORAN (PRD FR-RPT & FR-META) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'laporan'" class="w-full space-y-6">
                    <div>
                        <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                            Generator Laporan & Interoperabilitas Metadata
                        </h1>
                        <p class="text-xs text-slate-400 mt-1">
                            Ekspor berkas terstandarisasi untuk Geoportal Banyumas dan Laporan Triwulanan
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                        <!-- Kartu 1: Laporan Excel Multi-Sheet -->
                        <div class="glass-card p-6 space-y-3 hover:border-emerald-500/40 hover:-translate-y-0.5 transition-all shadow-xl">
                            <div class="flex items-center gap-2.5 text-emerald-400">
                                <FileDown class="w-5 h-5" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Buku Kerja Excel Multi-Sheet (.xlsx)</h3>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Berkas Excel memuat 5 lembar terpisah: Ringkasan, Populasi Ternak, Produksi, Kasus Penyakit, dan Sebaran RPH 27 kecamatan.
                            </p>
                            <button
                                @click="showToast('Mengunduh Laporan_Peternakan_Banyumas_2026_TW2.xlsx')"
                                class="btn-primary-tactile w-full h-10 text-xs font-bold gap-2"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Excel Multi-Sheet</span>
                            </button>
                        </div>

                        <!-- Kartu 2: Dokumen PDF Resmi -->
                        <div class="glass-card p-6 space-y-3 hover:border-teal-500/40 hover:-translate-y-0.5 transition-all shadow-xl">
                            <div class="flex items-center gap-2.5 text-teal-400">
                                <FileText class="w-5 h-5" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Laporan Ringkasan Eksekutif (PDF)</h3>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Dokumen PDF resmi berstempel dinas memuat ringkasan eksekutif, tabel data, peta zonasi, dan lembar pengesahan Kepala Bidang.
                            </p>
                            <button
                                @click="showToast('Mengunduh Laporan_Ringkasan_Eksekutif_Banyumas_2026.pdf')"
                                class="btn-secondary-tactile w-full h-10 text-xs font-semibold gap-2 text-teal-400 hover:text-teal-300"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Dokumen PDF</span>
                            </button>
                        </div>

                        <!-- Kartu 3: Metadata ISO 19115 XML -->
                        <div class="glass-card p-6 space-y-3 hover:border-amber-500/40 hover:-translate-y-0.5 transition-all shadow-xl">
                            <div class="flex items-center gap-2.5 text-amber-400">
                                <Compass class="w-5 h-5" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Metadata Geospasial ISO 19115 (XML)</h3>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Format XML resmi KAK Bagian 4.d untuk interoperabilitas panen katalog (harvesting) Geoportal Kabupaten Banyumas.
                            </p>
                            <button
                                @click="showToast('Mengunduh ID-DINPERTAN-BMS-TERNAKVERSE-2026.xml')"
                                class="btn-secondary-tactile w-full h-10 text-xs font-semibold gap-2 text-amber-400 hover:text-amber-300"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Metadata XML</span>
                            </button>
                        </div>

                        <!-- Kartu 4: Shapefile KUGI (.zip) -->
                        <div class="glass-card p-6 space-y-3 hover:border-emerald-500/40 hover:-translate-y-0.5 transition-all shadow-xl">
                            <div class="flex items-center gap-2.5 text-emerald-400">
                                <Layers class="w-5 h-5" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Shapefile KUGI Geoportal (.zip)</h3>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Unsur ADMINISTRASI_AR_PETERNAKAN lengkap dengan file .shp, .shx, .dbf, .prj proyeksi SRGI2013-WGS84.
                            </p>
                            <button
                                @click="showToast('Mengunduh BANYUMAS_PETERNAKAN_KUGI_SHP.zip')"
                                class="btn-secondary-tactile w-full h-10 text-xs font-semibold gap-2 text-emerald-400 hover:text-emerald-300"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Paket SHP KUGI</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 7. VIEW: MY PROFILE -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'profile'" class="w-full space-y-6">
                    <div>
                        <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                            My Profile
                        </h1>
                        <p class="text-xs text-slate-400 mt-1">
                            Akun Super Administrator Sistem TERNAKVERSE
                        </p>
                    </div>

                    <div class="w-full glass-card p-6 md:p-8 space-y-6 shadow-2xl">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-16 bg-gradient-to-br from-emerald-500/20 to-teal-500/20 border border-emerald-500/40 flex items-center justify-center font-display font-black text-2xl text-emerald-400 shadow-lg shadow-emerald-950/40">
                                S
                            </div>
                            <div>
                                <h3 class="font-display font-extrabold text-2xl text-[#F4F7F5]">Satrio</h3>
                                <p class="text-xs text-slate-400 mt-0.5">NIP: 19850412 201001 1 008</p>
                                <span class="inline-block mt-1 px-3 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold border border-emerald-500/30">
                                    ROLE: SUPER_ADMIN
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-white/10 text-xs">
                            <div class="py-3.5 flex justify-between">
                                <span class="text-slate-400 font-semibold">Email Dinas:</span>
                                <span class="text-[#F4F7F5] font-semibold">admin.diskanak@banyumaskab.go.id</span>
                            </div>
                            <div class="py-3.5 flex justify-between">
                                <span class="text-slate-400 font-semibold">Unit Kerja:</span>
                                <span class="text-[#F4F7F5]">Bidang Peternakan & Keswan</span>
                            </div>
                            <div class="py-3.5 flex justify-between">
                                <span class="text-slate-400 font-semibold">Instansi:</span>
                                <span class="text-[#F4F7F5]">Dinas Pertanian dan Ketahanan Pangan</span>
                            </div>
                            <div class="py-3.5 flex justify-between">
                                <span class="text-slate-400 font-semibold">Status Akun:</span>
                                <span class="text-emerald-400 font-bold">Aktif (2FA Terlindungi)</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                @click="handleLogout"
                                class="h-10 px-6 rounded-10 bg-rose-500/15 border border-rose-500/40 text-rose-300 hover:bg-rose-500/25 font-semibold text-xs flex items-center justify-center gap-2 transition-all"
                            >
                                <LogOut class="w-4 h-4" />
                                <span>Sign Out Dari Sesi Ini</span>
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <!-- ==================================================== -->
        <!-- MODAL DIALOG IMPOR CSV/EXCEL -->
        <!-- ==================================================== -->
        <div
            v-if="isImportModalOpen"
            class="fixed inset-0 z-50 bg-[#080C0E]/80 backdrop-blur-md flex items-center justify-center p-4 select-none"
            @click.self="isImportModalOpen = false"
        >
            <div class="glass-card border border-emerald-500/30 rounded-20 p-6 w-full max-w-lg shadow-2xl animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                    <h3 class="font-display font-extrabold text-xl text-[#F4F7F5] flex items-center gap-2">
                        <Upload class="w-5 h-5 text-emerald-400" />
                        <span>Impor Data CSV / Excel</span>
                    </h3>
                    <button
                        @click="isImportModalOpen = false"
                        class="w-8 h-8 rounded-full border border-white/10 text-slate-400 hover:text-[#F4F7F5] hover:border-emerald-500/40 flex items-center justify-center transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <p class="text-xs text-slate-400">
                        Unggah berkas lembar kerja rekapitulasi data peternakan (.csv atau .xlsx). Sistem akan memvalidasi kode kecamatan dan menghitung total agregat secara otomatis.
                    </p>

                    <!-- Dropzone -->
                    <div class="border border-dashed border-white/20 hover:border-emerald-500/50 rounded-16 p-6 text-center cursor-pointer transition-colors bg-[#080C0E]/60">
                        <Upload class="w-8 h-8 text-emerald-400 mx-auto mb-2" />
                        <span class="text-xs font-bold text-[#F4F7F5] block">Tarik berkas ke sini atau klik untuk memilih</span>
                        <span class="text-[10px] text-slate-500 block mt-1">Mendukung format .CSV, .XLS, .XLSX (Maks 10 MB)</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-white/10">
                        <button
                            @click="isImportModalOpen = false"
                            class="h-10 px-4 rounded-10 border border-white/10 text-xs font-semibold text-slate-400 hover:text-slate-200"
                        >
                            Batal
                        </button>
                        <button
                            @click="handleImportSuccess"
                            class="btn-primary-tactile h-10 px-5 text-xs font-bold gap-2"
                        >
                            <Upload class="w-4 h-4" />
                            <span>Unggah & Sinkronkan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- MODAL DIALOG EKSPOR CSV/EXCEL -->
        <!-- ==================================================== -->
        <div
            v-if="isExportModalOpen"
            class="fixed inset-0 z-50 bg-[#080C0E]/80 backdrop-blur-md flex items-center justify-center p-4 select-none animate-in fade-in duration-150"
            @click.self="isExportModalOpen = false"
        >
            <div class="glass-card border border-emerald-500/30 rounded-20 p-6 w-full max-w-md shadow-2xl space-y-4 font-ui">
                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-10 bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                            <Download class="w-4 h-4 text-emerald-400" />
                        </div>
                        <div>
                            <h3 class="font-display font-extrabold text-base text-[#F4F7F5] leading-none">
                                Ekspor Data Peternakan
                            </h3>
                            <span class="text-[11px] text-slate-400 mt-0.5 block">
                                Unduh rekapitulasi data kecamatan ke spreadsheet
                            </span>
                        </div>
                    </div>
                    <button
                        @click="isExportModalOpen = false"
                        class="w-8 h-8 rounded-full border border-white/10 text-slate-400 hover:text-[#F4F7F5] hover:border-rose-400 flex items-center justify-center transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Scope Data Info -->
                <div class="bg-[#080C0E]/60 border border-white/10 rounded-10 p-3 text-xs text-slate-400 flex items-center justify-between">
                    <span>Cakupan Data:</span>
                    <span class="font-semibold text-emerald-400">
                        {{ selectedRowIds.length > 0 ? selectedRowIds.length + ' baris dipilih' : filteredKecamatan.length + ' kecamatan' }}
                    </span>
                </div>

                <!-- Pilihan Format Ekspor (2 Kartu Tactile) -->
                <div class="space-y-2.5 pt-1">
                    <!-- Format Excel -->
                    <button
                        @click="handleExport('excel')"
                        class="w-full p-3.5 rounded-12 border border-white/10 hover:border-emerald-500/40 bg-[#080C0E]/50 hover:bg-[#080C0E]/90 flex items-center justify-between transition-all group text-left shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-10 bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 flex-shrink-0">
                                <FileText class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#F4F7F5] group-hover:text-emerald-400 flex items-center gap-2">
                                    <span>Microsoft Excel (.xls)</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/15 text-emerald-400">XLS</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Format spreadsheet langsung rapi di Microsoft Excel &amp; Google Sheets.
                                </p>
                            </div>
                        </div>
                        <Download class="w-4 h-4 text-slate-400 group-hover:text-emerald-400 transition-colors" />
                    </button>

                    <!-- Format CSV -->
                    <button
                        @click="handleExport('csv')"
                        class="w-full p-3.5 rounded-12 border border-white/10 hover:border-teal-500/40 bg-[#080C0E]/50 hover:bg-[#080C0E]/90 flex items-center justify-between transition-all group text-left shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-10 bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400 flex-shrink-0">
                                <FileDown class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#F4F7F5] group-hover:text-teal-400 flex items-center gap-2">
                                    <span>Comma-Separated Values (.csv)</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-teal-500/15 text-teal-400">CSV</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Format teks standar dengan encoding UTF-8 BOM dan pemisah koma.
                                </p>
                            </div>
                        </div>
                        <Download class="w-4 h-4 text-slate-400 group-hover:text-teal-400 transition-colors" />
                    </button>
                </div>

                <!-- Tombol Batal -->
                <div class="pt-2 flex justify-end">
                    <button
                        @click="isExportModalOpen = false"
                        class="h-9 px-4 rounded-10 border border-white/10 text-xs font-semibold text-slate-400 hover:text-[#F4F7F5] hover:border-white/20 transition-all"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- MODAL DIALOG PARAMETER FILTER LANJUTAN -->
        <!-- ==================================================== -->
        <div
            v-if="isFilterModalOpen"
            class="fixed inset-0 z-50 bg-[#080C0E]/80 backdrop-blur-md flex items-center justify-center p-4 select-none animate-in fade-in duration-150"
            @click.self="isFilterModalOpen = false"
        >
            <div class="glass-card border border-emerald-500/30 rounded-20 p-6 w-full max-w-md shadow-2xl space-y-4 font-ui">
                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-10 bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                            <SlidersHorizontal class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="font-display font-extrabold text-base text-[#F4F7F5] leading-none">
                                Parameter Filter Lanjutan
                            </h3>
                            <span class="text-[11px] text-slate-400 mt-0.5 block">
                                Sesuaikan kriteria populasi, kasus wabah, &amp; fasilitas
                            </span>
                        </div>
                    </div>
                    <button
                        @click="isFilterModalOpen = false"
                        class="w-8 h-8 rounded-full border border-white/10 text-slate-400 hover:text-[#F4F7F5] hover:border-rose-400 flex items-center justify-center transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5">
                    <!-- Filter Kasus Wabah -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Status Kasus Wabah (PMK / LSD)
                        </label>
                        <select
                            v-model="selectedWabahFilter"
                            class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3 text-xs text-[#F4F7F5] outline-none focus:border-emerald-500/60"
                        >
                            <option value="semua" class="bg-[#0F171B]">Semua Kondisi Kasus</option>
                            <option value="ada_kasus" class="bg-[#0F171B]">⚠️ Hanya Kecamatan Berkasus (&gt;0 Kasus)</option>
                            <option value="bebas_kasus" class="bg-[#0F171B]">✅ Bebas Kasus Wabah (0 Kasus)</option>
                        </select>
                    </div>

                    <!-- Filter Fasilitas Poskeswan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Fasilitas Poskeswan
                        </label>
                        <select
                            v-model="selectedPoskeswanFilter"
                            class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3 text-xs text-[#F4F7F5] outline-none focus:border-emerald-500/60"
                        >
                            <option value="semua" class="bg-[#0F171B]">Semua Kecamatan</option>
                            <option value="ada" class="bg-[#0F171B]">Tersedia Poskeswan (&ge; 1 Unit)</option>
                            <option value="tidak_ada" class="bg-[#0F171B]">Belum Ada Poskeswan (0 Unit)</option>
                        </select>
                    </div>

                    <!-- Filter Ambang Populasi Minimum -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Ambang Populasi Ternak Minimum
                        </label>
                        <select
                            v-model.number="minPopulasiFilter"
                            class="w-full h-10 bg-[#080C0E]/70 border border-white/10 rounded-10 px-3 text-xs text-[#F4F7F5] outline-none focus:border-emerald-500/60"
                        >
                            <option :value="0" class="bg-[#0F171B]">Tanpa Batas Minimum</option>
                            <option :value="1000" class="bg-[#0F171B]">&ge; 1.000 Ekor</option>
                            <option :value="3000" class="bg-[#0F171B]">&ge; 3.000 Ekor (Sentra Utama)</option>
                            <option :value="5000" class="bg-[#0F171B]">&ge; 5.000 Ekor (Populasi Sangat Tinggi)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-white/10 flex items-center justify-between">
                    <button
                        @click="resetFilters"
                        class="text-xs text-rose-400 hover:underline font-semibold"
                    >
                        Reset Parameter
                    </button>
                    <button
                        @click="isFilterModalOpen = false"
                        class="btn-primary-tactile h-9 px-4 text-xs font-semibold"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- TOAST NOTIFIKASI DI SUDUT BAWAH -->
        <!-- ==================================================== -->
        <div
            v-if="toastMsg"
            class="fixed bottom-6 right-6 z-50 bg-[#0F171B]/95 border border-emerald-500/40 text-[#F4F7F5] px-5 py-3 rounded-full shadow-2xl shadow-emerald-950/50 backdrop-blur-md text-xs font-bold flex items-center gap-2.5 animate-in fade-in slide-in-from-bottom-2 duration-150"
        >
            <Check class="w-4 h-4 text-emerald-400 stroke-[3]" />
            <span>{{ toastMsg }}</span>
        </div>

        <!-- DIAGNOSTIC BOX (Bab 13 Design Rule - Diposisikan di Kanan Bawah) -->
        <DiagnosticBox />
    </div>
</template>

/* BERKAS-UTUH: AdminPortal.vue */
