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
        <!-- TOP NAVBAR (64px, Sesuai Design Rule) -->
        <!-- ==================================================== -->
        <header class="h-16 w-full flex-shrink-0 bg-[#10171A] border-b-2 border-[#243034] px-4 md:px-8 flex items-center justify-between z-40 select-none">
            <!-- Brand Kiri (Logo Kotak Cyan + Judul + Role Badge) -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-12 bg-[#0A0D0E] border-2 border-[#19F5D4] flex items-center justify-center text-[#19F5D4] shadow-[3px_3px_0_#19F5D4]">
                    <Compass class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-extrabold text-xl tracking-tight text-[#F4F7F5]">
                            TERNAKVERSE
                        </span>
                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-[#39FF14] text-[#04110A] border border-[#F4F7F5] shadow-[2px_2px_0_#19F5D4] font-ui">
                            SYS_ADMIN
                        </span>
                    </div>
                    <p class="text-[12px] text-[#8FA1A0] hidden sm:block leading-none mt-0.5">
                        Pemerintah Kabupaten Banyumas • Panel Kendali Peternakan & Analitik
                    </p>
                </div>
            </div>

            <!-- Bagian Kanan: Navigasi Cepat Sesuai Design Rule & Avatar User S -->
            <div class="flex items-center gap-3">
                <!-- Navigasi Cepat Kembali ke Portal Publik -->
                <button
                    @click="goToPublicPortal"
                    class="btn-secondary-tactile h-9 px-3.5 text-xs gap-1.5 text-[#8FA1A0] hover:text-[#F4F7F5]"
                    title="Buka Halaman WebGIS Publik"
                >
                    <ArrowLeft class="w-3.5 h-3.5" />
                    <span class="hidden md:inline">Portal Publik</span>
                </button>

                <!-- Navigasi Cepat Kembali ke WebGIS Dinas -->
                <button
                    @click="goToDinasMap"
                    class="btn-secondary-tactile h-9 px-3.5 text-xs gap-1.5 text-[#19F5D4]"
                    title="Buka Peta WebGIS Kuantitatif Dinas"
                >
                    <MapPin class="w-3.5 h-3.5" />
                    <span>WebGIS Dinas</span>
                </button>

                <!-- Avatar User Initial [ S ] (Sesuai Screenshot Pojok Kanan Atas) -->
                <div
                    @click="currentTab = 'profile'"
                    class="w-9 h-9 rounded-12 bg-[#0A0D0E] border-2 border-[#19F5D4] hover:border-[#39FF14] flex items-center justify-center text-sm font-display font-extrabold text-[#19F5D4] hover:text-[#39FF14] cursor-pointer shadow-[2px_2px_0_#243034] transition-all"
                    title="Satrio (Super Admin)"
                >
                    S
                </div>
            </div>
        </header>

        <!-- ==================================================== -->
        <!-- ROW LAYOUT: SIDEBAR PINNED (TIDAK TERSCROLL) + MAIN SCROLLABLE -->
        <!-- ==================================================== -->
        <div class="flex-1 flex w-full overflow-hidden">
            
            <!-- SIDEBAR KIRI (Pinned / Fixed Height, Tidak Ikut Terscroll) -->
            <aside class="w-64 h-full flex-shrink-0 bg-[#10171A] border-r-2 border-[#243034] flex flex-col justify-between p-3 select-none z-20">
                
                <!-- BAGIAN MENU ATAS (Scrollable Mandiri Jika Layar Sangat Pendek) -->
                <div class="space-y-1.5 overflow-y-auto pr-1">
                    <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#8FA1A0]">
                        Navigasi Sistem
                    </div>

                    <!-- 1. Dashboard -->
                    <button
                        @click="currentTab = 'dashboard'"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'dashboard'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <LayoutDashboard class="w-4 h-4 flex-shrink-0" />
                        <span>Dashboard</span>
                    </button>

                    <!-- 2. Data Peternakan -->
                    <button
                        @click="currentTab = 'data'"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'data'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <FolderKanban class="w-4 h-4 flex-shrink-0" />
                        <span>Data Peternakan</span>
                    </button>

                    <!-- 3. Form Input Data -->
                    <button
                        @click="startCreateNew"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'create'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <PlusSquare class="w-4 h-4 flex-shrink-0" />
                        <span>Input Data Baru</span>
                    </button>

                    <!-- 4. Monitoring EWS Penyakit -->
                    <button
                        @click="currentTab = 'ews'"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'ews'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <ShieldAlert class="w-4 h-4 flex-shrink-0" />
                        <span>Monitoring EWS</span>
                    </button>

                    <!-- 5. Fasilitas RPH Spasial -->
                    <button
                        @click="currentTab = 'rph'"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'rph'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <Building2 class="w-4 h-4 flex-shrink-0" />
                        <span>Fasilitas RPH</span>
                    </button>

                    <!-- 6. Ekspor Laporan -->
                    <button
                        @click="currentTab = 'laporan'"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'laporan'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <FileDown class="w-4 h-4 flex-shrink-0" />
                        <span>Ekspor Laporan</span>
                    </button>

                    <!-- 7. My Profile -->
                    <button
                        @click="currentTab = 'profile'"
                        class="w-full text-left px-3.5 h-10 rounded-12 flex items-center gap-3 text-xs font-bold transition-all"
                        :class="currentTab === 'profile'
                            ? 'bg-[#243034] text-[#39FF14] border-2 border-[#39FF14] shadow-[3px_3px_0_#19F5D4]'
                            : 'text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034]/60 border-2 border-transparent'"
                    >
                        <User class="w-4 h-4 flex-shrink-0" />
                        <span>My Profile</span>
                    </button>
                </div>

                <!-- BAGIAN BAWAH: NAVIGASI KELUAR (SELALU TERLIHAT, PINNED TANPA SCROLL) -->
                <div class="pt-3 border-t-2 border-[#243034] space-y-1.5 flex-shrink-0">
                    <div class="px-2 text-[10px] uppercase font-bold tracking-wider text-[#8FA1A0] mb-1">
                        Navigasi Keluar
                    </div>

                    <!-- Tombol Kembali ke Portal Publik -->
                    <button
                        @click="goToPublicPortal"
                        class="w-full h-9 px-3 rounded-12 bg-[#0A0D0E] border-2 border-[#243034] hover:border-[#19F5D4] text-[#19F5D4] font-bold text-xs flex items-center gap-2 transition-all shadow-[2px_2px_0_#0A0D0E]"
                    >
                        <ArrowLeft class="w-3.5 h-3.5 text-[#19F5D4]" />
                        <span>← Portal Publik</span>
                    </button>

                    <!-- Tombol Kembali ke WebGIS Dinas -->
                    <button
                        @click="goToDinasMap"
                        class="w-full h-9 px-3 rounded-12 bg-[#0A0D0E] border-2 border-[#243034] hover:border-[#39FF14] text-[#39FF14] font-bold text-xs flex items-center gap-2 transition-all shadow-[2px_2px_0_#0A0D0E]"
                    >
                        <Compass class="w-3.5 h-3.5 text-[#39FF14]" />
                        <span>← WebGIS Dinas</span>
                    </button>

                    <!-- Tombol Sign Out Sesi -->
                    <button
                        @click="handleLogout"
                        class="w-full h-9 px-3 rounded-12 bg-[#FF5C5C]/15 border-2 border-[#FF5C5C] hover:bg-[#FF5C5C] hover:text-[#04110A] text-[#FF5C5C] font-bold text-xs flex items-center gap-2 transition-all"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span>Sign Out</span>
                    </button>
                </div>
            </aside>

            <!-- ==================================================== -->
            <!-- MAIN CONTENT AREA: RATA KANAN KIRI PENUH (w-full) & SCROLLABLE -->
            <!-- ==================================================== -->
            <main class="flex-1 h-full overflow-y-auto p-6 md:p-8 bg-[#0A0D0E] w-full">

                <!-- ---------------------------------------------------- -->
                <!-- 1. VIEW: DASHBOARD (PERSIS SEPERTI SCREENSHOT USER) -->
                <!-- ---------------------------------------------------- -->
                <div v-if="currentTab === 'dashboard'" class="w-full space-y-6">
                    
                    <!-- HEADER DASHBOARD: Kelola data peternakan & Tombol Aksi -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
                        <div>
                            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#F4F7F5]">
                                Kelola data peternakan
                            </h1>
                            <p class="text-xs text-[#8FA1A0] mt-0.5">
                                Setiap perubahan langsung muncul di peta.
                            </p>
                        </div>

                        <!-- Bar Kanan: Cari Catatan + Impor CSV/Excel + Tambah Data -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Input Cari Catatan -->
                            <div class="relative min-w-[210px]">
                                <Search class="w-4 h-4 absolute left-3 top-3 text-[#8FA1A0]" />
                                <input
                                    v-model="searchQueryDashboard"
                                    @keyup.enter="handleDashboardSearch"
                                    type="text"
                                    placeholder="Cari catatan"
                                    class="h-10 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 pl-9 pr-3 text-xs text-[#F4F7F5] placeholder-[#8FA1A0] focus:border-[#19F5D4] outline-none"
                                />
                            </div>

                            <!-- Tombol Impor CSV/Excel -->
                            <button
                                @click="isImportModalOpen = true"
                                class="btn-secondary-tactile h-10 px-4 text-xs font-bold gap-2"
                            >
                                <Upload class="w-4 h-4 text-[#F4F7F5]" />
                                <span>Impor CSV/Excel</span>
                            </button>

                            <!-- Tombol + Tambah data (btn-primary-tactile) -->
                            <button
                                @click="startCreateNew"
                                class="btn-primary-tactile h-10 px-4 text-xs font-bold gap-1.5"
                            >
                                <Plus class="w-4 h-4 stroke-[3]" />
                                <span>Tambah data</span>
                            </button>
                        </div>
                    </div>

                    <!-- BARIS 1: 4 KARTU STATISTIK KPI (Persis Screenshot Row 1) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                        
                        <!-- 1. Total Sapi -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-[3px_3px_0_#243034] flex flex-col justify-between">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-[#8FA1A0]">Total sapi</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-[#39FF14] bg-[#39FF14]/10 text-[#39FF14]">
                                    +5% bulan ini
                                </span>
                            </div>
                            <div class="flex items-center gap-2.5 my-1">
                                <!-- Ikon Sapi (Kepala bertanduk, Bab 8) -->
                                <svg class="w-6 h-6 text-[#F4F7F5] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 6 C4 4, 2 3, 2 3" />
                                    <path d="M20 6 C20 4, 22 3, 22 3" />
                                    <path d="M5 8 L19 8 C20 8, 21 9, 21 11 L21 15 C21 18, 18 20, 15 20 L9 20 C6 20, 3 18, 3 15 L3 11 C3 9, 4 8, 5 8 Z" />
                                    <circle cx="8.5" cy="13" r="1.5" fill="currentColor" />
                                    <circle cx="15.5" cy="13" r="1.5" fill="currentColor" />
                                    <path d="M9 17 C10.5 18, 13.5 18, 15 17" />
                                </svg>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-display font-extrabold text-2xl text-[#F4F7F5] tracking-tight">34.895</span>
                                    <span class="text-xs text-[#8FA1A0]">ekor</span>
                                </div>
                            </div>
                            <!-- Sparkline Hijau Neon -->
                            <div class="w-full h-8 flex items-end mt-2">
                                <svg class="w-full h-7" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 20 L25 17 L50 15 L75 12 L100 6" fill="none" stroke="#39FF14" stroke-width="2.5" />
                                    <path d="M0 20 L25 17 L50 15 L75 12 L100 6 L100 24 L0 24 Z" fill="#39FF14" opacity="0.12" />
                                </svg>
                            </div>
                        </div>

                        <!-- 2. Total Kambing -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-[3px_3px_0_#243034] flex flex-col justify-between">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-[#8FA1A0]">Total kambing</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-[#39FF14] bg-[#39FF14]/10 text-[#39FF14]">
                                    +3% bulan ini
                                </span>
                            </div>
                            <div class="flex items-center gap-2.5 my-1">
                                <!-- Ikon Kambing (Kepala berjanggut, Bab 8) -->
                                <svg class="w-6 h-6 text-[#F4F7F5] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 3 C6 2, 4 3, 3 5" />
                                    <path d="M17 3 C18 2, 20 3, 21 5" />
                                    <path d="M6 7 L18 7 C19 7, 20 8, 20 10 L19 16 C18.5 18, 16 19.5, 12 19.5 C8 19.5, 5.5 18, 5 16 L4 10 C4 8, 5 7, 6 7 Z" />
                                    <path d="M12 19.5 L12 22" />
                                    <circle cx="9" cy="11.5" r="1.5" fill="currentColor" />
                                    <circle cx="15" cy="11.5" r="1.5" fill="currentColor" />
                                </svg>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-display font-extrabold text-2xl text-[#F4F7F5] tracking-tight">23.310</span>
                                    <span class="text-xs text-[#8FA1A0]">ekor</span>
                                </div>
                            </div>
                            <!-- Sparkline Hijau Neon -->
                            <div class="w-full h-8 flex items-end mt-2">
                                <svg class="w-full h-7" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 18 L25 15 L50 18 L75 14 L100 8" fill="none" stroke="#39FF14" stroke-width="2.5" />
                                    <path d="M0 18 L25 15 L50 18 L75 14 L100 8 L100 24 L0 24 Z" fill="#39FF14" opacity="0.12" />
                                </svg>
                            </div>
                        </div>

                        <!-- 3. Kasus aktif PMK -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-[3px_3px_0_#243034] flex flex-col justify-between">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-[#8FA1A0]">Kasus aktif PMK</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-[#39FF14] bg-[#39FF14]/10 text-[#39FF14]">
                                    -12% bulan ini
                                </span>
                            </div>
                            <div class="flex items-center gap-2.5 my-1">
                                <!-- Ikon Virus Berduri PMK -->
                                <svg class="w-6 h-6 text-[#F4F7F5] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="5" />
                                    <line x1="12" y1="2" x2="12" y2="6" />
                                    <line x1="12" y1="18" x2="12" y2="22" />
                                    <line x1="2" y1="12" x2="6" y2="12" />
                                    <line x1="18" y1="12" x2="22" y2="12" />
                                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76" />
                                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07" />
                                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24" />
                                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93" />
                                </svg>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-display font-extrabold text-2xl text-[#F4F7F5] tracking-tight">52</span>
                                    <span class="text-xs text-[#8FA1A0]">kasus</span>
                                </div>
                            </div>
                            <!-- Sparkline Penurunan Kasus -->
                            <div class="w-full h-8 flex items-end mt-2">
                                <svg class="w-full h-7" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 12 L20 10 L45 7 L70 14 L100 20" fill="none" stroke="#39FF14" stroke-width="2.5" />
                                    <path d="M0 12 L20 10 L45 7 L70 14 L100 20 L100 24 L0 24 Z" fill="#39FF14" opacity="0.12" />
                                </svg>
                            </div>
                        </div>

                        <!-- 4. Kasus aktif LSD -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-[3px_3px_0_#243034] flex flex-col justify-between">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-[#8FA1A0]">Kasus aktif LSD</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-[#FF5C5C] bg-[#FF5C5C]/10 text-[#FF5C5C]">
                                    +9% bulan ini
                                </span>
                            </div>
                            <div class="flex items-center gap-2.5 my-1">
                                <!-- Ikon Virus Bernodul LSD -->
                                <svg class="w-6 h-6 text-[#F4F7F5] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="6" />
                                    <circle cx="12" cy="3" r="1.5" />
                                    <circle cx="12" cy="21" r="1.5" />
                                    <circle cx="3" cy="12" r="1.5" />
                                    <circle cx="21" cy="12" r="1.5" />
                                    <circle cx="6" cy="6" r="1.5" />
                                    <circle cx="18" cy="18" r="1.5" />
                                    <circle cx="6" cy="18" r="1.5" />
                                    <circle cx="18" cy="6" r="1.5" />
                                </svg>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-display font-extrabold text-2xl text-[#F4F7F5] tracking-tight">32</span>
                                    <span class="text-xs text-[#8FA1A0]">kasus</span>
                                </div>
                            </div>
                            <!-- Sparkline Kenaikan Kasus Merah -->
                            <div class="w-full h-8 flex items-end mt-2">
                                <svg class="w-full h-7" viewBox="0 0 100 24" preserveAspectRatio="none">
                                    <path d="M0 18 L25 19 L50 14 L75 13 L100 6" fill="none" stroke="#FF5C5C" stroke-width="2.5" />
                                    <path d="M0 18 L25 19 L50 14 L75 13 L100 6 L100 24 L0 24 Z" fill="#FF5C5C" opacity="0.12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- BARIS 2: BAR CHART & LINE CHART SEJAJAR (Persis Screenshot Row 2) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                        
                        <!-- 1. Bar Chart: Populasi ternak per kecamatan -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5 md:p-6 shadow-[3px_3px_0_#243034]">
                            <div class="mb-4">
                                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">
                                    Populasi ternak per kecamatan
                                </h3>
                                <p class="text-xs text-[#8FA1A0]">5 kecamatan teratas, jumlah ekor</p>
                            </div>

                            <!-- Visual SVG Bar Chart Sesuai Screenshot -->
                            <div class="w-full h-[260px]">
                                <svg class="w-full h-full" viewBox="0 0 480 240" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="barGradTernak" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#39FF14" />
                                            <stop offset="100%" stop-color="#19F5D4" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Garis Grid Putus-putus Horizontal -->
                                    <line x1="45" y1="30" x2="470" y2="30" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="35" y="34" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">6.050</text>

                                    <line x1="45" y1="75" x2="470" y2="75" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="35" y="79" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">4.538</text>

                                    <line x1="45" y1="120" x2="470" y2="120" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="35" y="124" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">3.025</text>

                                    <line x1="45" y1="165" x2="470" y2="165" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="35" y="169" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">1.513</text>

                                    <line x1="45" y1="210" x2="470" y2="210" stroke="#243034" stroke-width="1.5" />
                                    <text x="35" y="214" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">0</text>

                                    <!-- Bar 1: Wangon (6.050) -->
                                    <text x="80" y="22" text-anchor="middle" fill="#F4F7F5" font-size="11" font-weight="700" font-family="'Bricolage Grotesque', sans-serif">6.050</text>
                                    <rect x="58" y="30" width="44" height="180" rx="6" fill="url(#barGradTernak)" />
                                    <text x="80" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Wangon</text>

                                    <!-- Bar 2: Ajibarang (4.880) -->
                                    <text x="165" y="58" text-anchor="middle" fill="#F4F7F5" font-size="11" font-weight="700" font-family="'Bricolage Grotesque', sans-serif">4.880</text>
                                    <rect x="143" y="65" width="44" height="145" rx="6" fill="url(#barGradTernak)" />
                                    <text x="165" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Ajibarang</text>

                                    <!-- Bar 3: Sumbang (4.140) -->
                                    <text x="250" y="80" text-anchor="middle" fill="#F4F7F5" font-size="11" font-weight="700" font-family="'Bricolage Grotesque', sans-serif">4.140</text>
                                    <rect x="228" y="87" width="44" height="123" rx="6" fill="url(#barGradTernak)" />
                                    <text x="250" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Sumbang</text>

                                    <!-- Bar 4: Cikakak (3.240) -->
                                    <text x="335" y="106" text-anchor="middle" fill="#F4F7F5" font-size="11" font-weight="700" font-family="'Bricolage Grotesque', sans-serif">3.240</text>
                                    <rect x="313" y="114" width="44" height="96" rx="6" fill="url(#barGradTernak)" />
                                    <text x="335" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Cikakak</text>

                                    <!-- Bar 5: Gumelar (3.240) -->
                                    <text x="420" y="106" text-anchor="middle" fill="#F4F7F5" font-size="11" font-weight="700" font-family="'Bricolage Grotesque', sans-serif">3.240</text>
                                    <rect x="398" y="114" width="44" height="96" rx="6" fill="url(#barGradTernak)" />
                                    <text x="420" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Gumelar</text>
                                </svg>
                            </div>
                        </div>

                        <!-- 2. Line Chart: Tren kasus penyakit -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5 md:p-6 shadow-[3px_3px_0_#243034]">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-display font-bold text-lg text-[#F4F7F5]">
                                        Tren kasus penyakit
                                    </h3>
                                    <p class="text-xs text-[#8FA1A0]">6 bulan terakhir</p>
                                </div>
                                <div class="flex items-center gap-3 text-xs font-bold">
                                    <span class="flex items-center gap-1.5 text-[#FF5C5C]">
                                        <span class="w-2.5 h-2.5 rounded-sm bg-[#FF5C5C]"></span>
                                        PMK
                                    </span>
                                    <span class="flex items-center gap-1.5 text-[#FFC53D]">
                                        <span class="w-2.5 h-2.5 rounded-sm bg-[#FFC53D]"></span>
                                        LSD
                                    </span>
                                </div>
                            </div>

                            <!-- Visual SVG Line Chart Sesuai Screenshot -->
                            <div class="w-full h-[260px]">
                                <svg class="w-full h-full" viewBox="0 0 480 240" preserveAspectRatio="none">
                                    <!-- Garis Grid Putus-putus Horizontal -->
                                    <line x1="30" y1="30" x2="470" y2="30" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="22" y="34" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">60</text>

                                    <line x1="30" y1="75" x2="470" y2="75" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="22" y="79" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">45</text>

                                    <line x1="30" y1="120" x2="470" y2="120" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="22" y="124" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">30</text>

                                    <line x1="30" y1="165" x2="470" y2="165" stroke="#243034" stroke-width="1.5" stroke-dasharray="3, 6" />
                                    <text x="22" y="169" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">15</text>

                                    <line x1="30" y1="210" x2="470" y2="210" stroke="#243034" stroke-width="1.5" />
                                    <text x="22" y="214" text-anchor="end" fill="#8FA1A0" font-size="10" font-family="'Plus Jakarta Sans', sans-serif">0</text>

                                    <!-- Label Sumbu X (Bulan) -->
                                    <text x="50" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Mei</text>
                                    <text x="130" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Jun</text>
                                    <text x="210" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Jul</text>
                                    <text x="290" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Agu</text>
                                    <text x="370" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Sep</text>
                                    <text x="450" y="228" text-anchor="middle" fill="#8FA1A0" font-size="11" font-family="'Plus Jakarta Sans', sans-serif">Okt</text>

                                    <!-- Garis PMK Merah (#FF5C5C) -->
                                    <polyline
                                        points="50,154 130,136 210,118 290,126 370,108 450,54"
                                        fill="none"
                                        stroke="#FF5C5C"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <circle cx="50" cy="154" r="4" fill="#FF5C5C" stroke="#10171A" stroke-width="2" />
                                    <circle cx="130" cy="136" r="4" fill="#FF5C5C" stroke="#10171A" stroke-width="2" />
                                    <circle cx="210" cy="118" r="4" fill="#FF5C5C" stroke="#10171A" stroke-width="2" />
                                    <circle cx="290" cy="126" r="4" fill="#FF5C5C" stroke="#10171A" stroke-width="2" />
                                    <circle cx="370" cy="108" r="4" fill="#FF5C5C" stroke="#10171A" stroke-width="2" />
                                    <circle cx="450" cy="54" r="4.5" fill="#FF5C5C" stroke="#10171A" stroke-width="2" />

                                    <!-- Garis LSD Kuning (#FFC53D) -->
                                    <polyline
                                        points="50,186 130,172 210,180 290,165 370,175 450,114"
                                        fill="none"
                                        stroke="#FFC53D"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <circle cx="50" cy="186" r="4" fill="#FFC53D" stroke="#10171A" stroke-width="2" />
                                    <circle cx="130" cy="172" r="4" fill="#FFC53D" stroke="#10171A" stroke-width="2" />
                                    <circle cx="210" cy="180" r="4" fill="#FFC53D" stroke="#10171A" stroke-width="2" />
                                    <circle cx="290" cy="165" r="4" fill="#FFC53D" stroke="#10171A" stroke-width="2" />
                                    <circle cx="370" cy="175" r="4" fill="#FFC53D" stroke="#10171A" stroke-width="2" />
                                    <circle cx="450" cy="114" r="4.5" fill="#FFC53D" stroke="#10171A" stroke-width="2" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- BARIS 3: DONUT CHART KOMPOSISI & AKTIVITAS TERBARU (Persis Screenshot Row 3) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                        
                        <!-- 1. Donut Chart: Komposisi jenis ternak -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5 md:p-6 shadow-[3px_3px_0_#243034]">
                            <div class="mb-4">
                                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">
                                    Komposisi jenis ternak
                                </h3>
                                <p class="text-xs text-[#8FA1A0]">Total 58.205 ekor di 23 kecamatan</p>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center justify-around gap-6 pt-2">
                                <!-- Donut Chart SVG -->
                                <div class="relative w-48 h-48 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-full h-full -rotate-90" viewBox="0 0 210 210">
                                        <!-- Background Ring -->
                                        <circle cx="105" cy="105" r="70" fill="none" stroke="#0A0D0E" stroke-width="28" />

                                        <!-- Segment 1: Sapi potong 54% (Neon #39FF14) -->
                                        <circle
                                            cx="105"
                                            cy="105"
                                            r="70"
                                            fill="none"
                                            stroke="#39FF14"
                                            stroke-width="28"
                                            stroke-dasharray="237.5 439.8"
                                            stroke-dashoffset="0"
                                        />

                                        <!-- Segment 2: Sapi perah 6% (Cyan #19F5D4) -->
                                        <circle
                                            cx="105"
                                            cy="105"
                                            r="70"
                                            fill="none"
                                            stroke="#19F5D4"
                                            stroke-width="28"
                                            stroke-dasharray="26.4 439.8"
                                            stroke-dashoffset="-237.5"
                                        />

                                        <!-- Segment 3: Kambing 40% (Teal #16BDB8) -->
                                        <circle
                                            cx="105"
                                            cy="105"
                                            r="70"
                                            fill="none"
                                            stroke="#16BDB8"
                                            stroke-width="28"
                                            stroke-dasharray="175.9 439.8"
                                            stroke-dashoffset="-263.9"
                                        />
                                    </svg>

                                    <!-- Angka Besar di Tengah Lubang Donut (58.205) -->
                                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="font-display font-extrabold text-2xl text-[#F4F7F5] leading-none">
                                            58.205
                                        </span>
                                        <span class="text-[11px] text-[#8FA1A0] mt-1">ekor ternak</span>
                                    </div>
                                </div>

                                <!-- Rincian Legenda Sebelah Kanan Donut -->
                                <div class="space-y-3.5 w-full sm:w-auto text-xs font-ui">
                                    <!-- Sapi Potong -->
                                    <div class="flex items-center justify-between sm:justify-start gap-4">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3.5 h-3.5 rounded-sm bg-[#39FF14]"></span>
                                            <span class="font-bold text-[#F4F7F5]">Sapi potong</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-[#39FF14]">54%</span>
                                            <span class="text-[#8FA1A0] font-mono">31.160 ekor</span>
                                        </div>
                                    </div>

                                    <!-- Sapi Perah -->
                                    <div class="flex items-center justify-between sm:justify-start gap-4">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3.5 h-3.5 rounded-sm bg-[#19F5D4]"></span>
                                            <span class="font-bold text-[#F4F7F5]">Sapi perah</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-[#19F5D4]">6%</span>
                                            <span class="text-[#8FA1A0] font-mono">3.735 ekor</span>
                                        </div>
                                    </div>

                                    <!-- Kambing -->
                                    <div class="flex items-center justify-between sm:justify-start gap-4">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3.5 h-3.5 rounded-sm bg-[#16BDB8]"></span>
                                            <span class="font-bold text-[#F4F7F5]">Kambing</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-[#16BDB8]">40%</span>
                                            <span class="text-[#8FA1A0] font-mono">23.310 ekor</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Aktivitas Terbaru (Activity Feed Sesuai Screenshot) -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-5 md:p-6 shadow-[3px_3px_0_#243034]">
                            <div class="mb-4">
                                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">
                                    Aktivitas terbaru
                                </h3>
                                <p class="text-xs text-[#8FA1A0]">Perubahan data terakhir</p>
                            </div>

                            <div class="divide-y-2 divide-[#243034] text-xs">
                                <!-- Feed 1: Budi -->
                                <div class="py-3">
                                    <p class="text-[#F4F7F5] leading-snug">
                                        <b class="text-[#F4F7F5] font-bold">Budi</b> memperbarui kasus PMK Ajibarang jadi 12 kasus.
                                    </p>
                                    <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">2 menit lalu</span>
                                </div>

                                <!-- Feed 2: Siti -->
                                <div class="py-3">
                                    <p class="text-[#F4F7F5] leading-snug">
                                        <b class="text-[#F4F7F5] font-bold">Siti</b> memperbarui kasus PMK Tambak jadi 5 kasus.
                                    </p>
                                    <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">19 menit lalu</span>
                                </div>

                                <!-- Feed 3: Budi -->
                                <div class="py-3">
                                    <p class="text-[#F4F7F5] leading-snug">
                                        <b class="text-[#F4F7F5] font-bold">Budi</b> menandai Ajibarang, Wangon, dan Tambak sebagai zona merah PMK.
                                    </p>
                                    <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">34 menit lalu</span>
                                </div>

                                <!-- Feed 4: Siti -->
                                <div class="py-3">
                                    <p class="text-[#F4F7F5] leading-snug">
                                        <b class="text-[#F4F7F5] font-bold">Siti</b> memverifikasi 5 laporan penyuluh.
                                    </p>
                                    <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">1 jam lalu</span>
                                </div>

                                <!-- Feed 5: Agus -->
                                <div class="py-3">
                                    <p class="text-[#F4F7F5] leading-snug">
                                        <b class="text-[#F4F7F5] font-bold">Agus</b> memperbarui peta wilayah dari berkas RBI.
                                    </p>
                                    <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">1 jam lalu</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 2. VIEW: DATA PETERNAKAN / TABLE LIST (Full Width Rata Kanan Kiri) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'data'" class="w-full space-y-4">
                    
                    <!-- Breadcrumbs -->
                    <div class="text-xs font-semibold text-[#8FA1A0]">
                        Dashboard &gt; Data Peternakan &gt; Basis Data Wilayah
                    </div>

                    <!-- Header & Tombol Aksi + TAMBAH DATA BARU -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                        <div>
                            <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                                Data Peternakan
                            </h1>
                            <p class="text-xs text-[#8FA1A0] mt-0.5">
                                Manajemen populasi ternak, komoditas unggulan, dan status zonasi EWS 27 kecamatan Kabupaten Banyumas
                            </p>
                        </div>

                        <!-- Tombol Hijau Neon + TAMBAH DATA (btn-primary-tactile) -->
                        <button
                            @click="startCreateNew"
                            class="btn-primary-tactile h-11 px-5 gap-2 text-xs font-bold self-start sm:self-auto"
                        >
                            <Plus class="w-4 h-4 stroke-[3]" />
                            <span>TAMBAH DATA BARU</span>
                        </button>
                    </div>

                    <!-- CONTAINER TABEL (Full Width Rata Kanan Kiri) -->
                    <div class="w-full bg-[#10171A] border-2 border-[#243034] rounded-20 overflow-hidden shadow-[4px_4px_0_#243034]">
                        
                        <!-- Header Kontrol Tabel: Search, Filter Dropdowns, Reset Filter, & Export CSV/Excel (Tanpa Scrollbar Terpotong) -->
                        <div class="p-3.5 md:p-4 border-b-2 border-[#243034] flex flex-wrap lg:flex-nowrap items-center justify-between gap-3 w-full bg-[#10171A] select-none">
                            
                            <!-- SISI KIRI: Search + Dropdown Filter + Tambah Filter + Reset Filter -->
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 flex-1 min-w-0">
                                
                                <!-- 1. Search Bar Nama Kecamatan atau Kode Wilayah -->
                                <div class="relative w-full sm:w-60 md:w-64 flex-shrink-0">
                                    <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#8FA1A0] pointer-events-none" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search nama kecamatan atau kode wilayah..."
                                        class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 pl-10 pr-8 text-xs text-[#F4F7F5] placeholder-[#8FA1A0] outline-none focus:border-[#19F5D4] transition-all"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        @click="searchQuery = ''"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8FA1A0] hover:text-[#FF5C5C] transition-colors"
                                        title="Hapus kata kunci pencarian"
                                    >
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- 2. Dropdown Filter Kategori Komoditas Ternak (Presisi w-40 sm:w-44 h-11) -->
                                <div class="relative w-40 sm:w-44 flex-shrink-0">
                                    <select
                                        v-model="selectedCategoryFilter"
                                        class="w-full h-11 bg-[#0A0D0E] border-2 rounded-12 pl-3 pr-7 text-xs font-bold outline-none cursor-pointer transition-all appearance-none truncate flex items-center"
                                        :class="selectedCategoryFilter !== 'semua'
                                            ? 'border-[#19F5D4] text-[#19F5D4] bg-[#19F5D4]/10 shadow-[2px_2px_0_#19F5D4]'
                                            : 'border-[#243034] text-[#F4F7F5] hover:border-[#19F5D4]'"
                                        title="Filter Kategori Komoditas"
                                    >
                                        <option value="semua">Semua Kategori</option>
                                        <option value="sapi_potong">Sapi Potong (&gt;500)</option>
                                        <option value="sapi_perah">Sapi Perah (&gt;50)</option>
                                        <option value="kambing_domba">Kambing / Domba (&gt;1rb)</option>
                                        <option value="unggas">Ayam Broiler (&gt;5rb)</option>
                                    </select>
                                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#8FA1A0] text-[10px]">
                                        ▼
                                    </div>
                                </div>

                                <!-- 3. Dropdown Filter Status Zonasi EWS (Presisi w-40 sm:w-44 h-11) -->
                                <div class="relative w-40 sm:w-44 flex-shrink-0">
                                    <select
                                        v-model="selectedZonaFilter"
                                        class="w-full h-11 bg-[#0A0D0E] border-2 rounded-12 pl-3 pr-7 text-xs font-bold outline-none cursor-pointer transition-all appearance-none truncate flex items-center"
                                        :class="selectedZonaFilter !== 'semua'
                                            ? 'border-[#39FF14] text-[#39FF14] bg-[#39FF14]/10 shadow-[2px_2px_0_#39FF14]'
                                            : 'border-[#243034] text-[#F4F7F5] hover:border-[#39FF14]'"
                                        title="Filter Status Zonasi EWS"
                                    >
                                        <option value="semua">Semua Zona EWS</option>
                                        <option value="bahaya">🔴 Zona Merah (Bahaya)</option>
                                        <option value="waspada">🟡 Zona Kuning (Waspada)</option>
                                        <option value="aman">🟢 Zona Hijau (Aman)</option>
                                    </select>
                                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#8FA1A0] text-[10px]">
                                        ▼
                                    </div>
                                </div>

                                <!-- 4. Tombol Buka Modal Parameter Filter Lanjutan (Presisi w-40 sm:w-44 h-11) -->
                                <div class="relative w-40 sm:w-44 flex-shrink-0">
                                    <button
                                        @click="isFilterModalOpen = true"
                                        class="w-full h-11 px-3 rounded-12 border-2 text-xs font-bold flex items-center justify-between transition-all select-none"
                                        :class="selectedWabahFilter !== 'semua' || selectedPoskeswanFilter !== 'semua' || minPopulasiFilter > 0
                                            ? 'bg-[#243034] border-[#19F5D4] text-[#19F5D4] shadow-[2px_2px_0_#19F5D4]'
                                            : 'bg-[#0A0D0E] border-[#243034] text-[#F4F7F5] hover:border-[#19F5D4]'"
                                        title="Buka panel parameter filter lanjutan"
                                    >
                                        <span class="flex items-center gap-1.5 truncate">
                                            <SlidersHorizontal class="w-3.5 h-3.5 text-[#19F5D4] flex-shrink-0" />
                                            <span class="truncate">+ Tambah Filter</span>
                                        </span>
                                        <span class="text-[10px] text-[#8FA1A0] ml-1 flex-shrink-0">⚙️</span>
                                    </button>
                                </div>

                                <!-- 5. Tombol Reset Filter (Presisi h-11 px-3.5) -->
                                <button
                                    @click="resetFilters"
                                    class="h-11 px-3.5 rounded-12 border-2 text-xs font-bold flex items-center gap-1.5 transition-all select-none whitespace-nowrap flex-shrink-0"
                                    :class="hasActiveFilters
                                        ? 'bg-[#FF5C5C]/15 border-[#FF5C5C] text-[#FF5C5C] hover:bg-[#FF5C5C] hover:text-[#04110A] shadow-[2px_2px_0_#FF5C5C]'
                                        : 'bg-[#0A0D0E] border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#8FA1A0]'"
                                    title="Reset semua filter dan kata kunci pencarian"
                                >
                                    <RotateCcw class="w-3.5 h-3.5 flex-shrink-0" />
                                    <span>Reset Filter</span>
                                    <span
                                        v-if="hasActiveFilters"
                                        class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-[#FF5C5C] text-[#04110A] font-extrabold"
                                    >
                                        {{ activeFilterCount }}
                                    </span>
                                </button>
                            </div>

                            <!-- SISI KANAN: Fitur Export CSV / Excel (Membuka Modal Ekspor Bebas Clipping) -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    @click="isExportModalOpen = true"
                                    class="btn-secondary-tactile h-11 px-4 text-xs font-bold gap-2 text-[#19F5D4] hover:text-[#39FF14] shadow-[3px_3px_0_#243034] whitespace-nowrap flex-shrink-0"
                                    title="Ekspor data peternakan ke berkas CSV atau Excel"
                                >
                                    <Download class="w-4 h-4 text-[#19F5D4]" />
                                    <span>Export CSV / Excel</span>
                                    <span class="text-[10px] text-[#8FA1A0] font-mono">({{ selectedRowIds.length > 0 ? selectedRowIds.length + ' dipilih' : filteredKecamatan.length }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- TABEL DATA (Persis Kolom Screenshot 3, Full Width) -->
                        <div class="w-full overflow-x-auto">
                            <table class="w-full text-left text-xs font-ui">
                                <thead>
                                    <tr class="border-b-2 border-[#243034] text-[#8FA1A0] bg-[#0A0D0E]/60 select-none">
                                        <th class="py-4 px-5 w-12">
                                            <input
                                                type="checkbox"
                                                :checked="selectedRowIds.length > 0 && selectedRowIds.length === paginatedKecamatan.length"
                                                @change="toggleSelectAll"
                                                class="w-4 h-4 rounded bg-[#0A0D0E] border-2 border-[#243034] text-[#39FF14] focus:ring-0 cursor-pointer"
                                            />
                                        </th>
                                        <th class="py-4 px-5 font-bold">Title ⌄</th>
                                        <th class="py-4 px-5 font-bold">Category ⌄</th>
                                        <th class="py-4 px-5 font-bold">Client</th>
                                        <th class="py-4 px-5 font-bold">Year</th>
                                        <th class="py-4 px-5 font-bold text-center">Featured</th>
                                        <th class="py-4 px-5 font-bold">Updated at ⌄</th>
                                        <th class="py-4 px-5 font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-[#243034]">
                                    <tr
                                        v-for="kec in paginatedKecamatan"
                                        :key="kec.id"
                                        class="hover:bg-[#243034]/50 transition-colors"
                                    >
                                        <!-- Checkbox -->
                                        <td class="py-4 px-5">
                                            <input
                                                type="checkbox"
                                                :checked="selectedRowIds.includes(kec.id)"
                                                @change="toggleSelectRow(kec.id)"
                                                class="w-4 h-4 rounded bg-[#0A0D0E] border-2 border-[#243034] text-[#39FF14] focus:ring-0 cursor-pointer"
                                            />
                                        </td>

                                        <!-- Title (Nama Kecamatan) -->
                                        <td class="py-4 px-5 font-bold text-sm text-[#F4F7F5]">
                                            <div class="flex items-center gap-2">
                                                <span>{{ kec.nama }}</span>
                                                <span class="text-xs text-[#8FA1A0] font-normal">({{ kec.kode_kec }})</span>
                                                <span class="text-[10px] text-[#39FF14] font-mono px-1.5 py-0.5 rounded bg-[#0A0D0E] border border-[#243034]">
                                                    {{ getDesaByKecamatan(kec.id).length }} Desa
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Category (Badge Hijau Neon Sesuai Screenshot 3) -->
                                        <td class="py-4 px-5">
                                            <span class="px-3 py-1 rounded-full border-2 border-[#39FF14]/40 bg-[#39FF14]/10 text-[#39FF14] font-extrabold text-[11px] uppercase tracking-wider">
                                                {{ kec.sapi_perah > 50 ? 'SAPI PERAH' : 'SAPI POTONG' }}
                                            </span>
                                        </td>

                                        <!-- Client (Populasi Ekor) -->
                                        <td class="py-4 px-5 text-[#8FA1A0] font-bold">
                                            {{ formatAngka(kec.sapi_potong) }} ekor
                                        </td>

                                        <!-- Year -->
                                        <td class="py-4 px-5 text-[#8FA1A0]">
                                            2026
                                        </td>

                                        <!-- Featured (Ikon Centang Bulat Hijau Sesuai Screenshot 3) -->
                                        <td class="py-4 px-5 text-center">
                                            <div class="inline-flex items-center justify-center">
                                                <CheckCircle2
                                                    v-if="kec.zona === 'aman'"
                                                    class="w-5 h-5 text-[#39FF14]"
                                                />
                                                <AlertTriangle
                                                    v-else-if="kec.zona === 'waspada'"
                                                    class="w-5 h-5 text-[#FFC53D]"
                                                />
                                                <ShieldAlert
                                                    v-else
                                                    class="w-5 h-5 text-[#FF5C5C]"
                                                />
                                            </div>
                                        </td>

                                        <!-- Updated At -->
                                        <td class="py-4 px-5 text-[#8FA1A0] text-xs">
                                            {{ kec.last_updated }}
                                        </td>

                                        <!-- Actions (Edit Link Sesuai Screenshot 3) -->
                                        <td class="py-4 px-5 text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <button
                                                    @click="startEditKecamatan(kec)"
                                                    class="text-[#19F5D4] hover:text-[#39FF14] font-bold flex items-center gap-1"
                                                >
                                                    <Edit2 class="w-3.5 h-3.5" />
                                                    <span>Edit</span>
                                                </button>
                                                <button
                                                    @click="deleteKecamatan(kec)"
                                                    class="text-[#FF5C5C] hover:text-[#FF8080]"
                                                    title="Hapus Data"
                                                >
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="paginatedKecamatan.length === 0">
                                        <td colspan="8" class="text-center py-10 text-[#8FA1A0]">
                                            Tidak ada data kecamatan yang sesuai pencarian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- FOOTER TABEL: Showing results & Per Page (Full Width) -->
                        <div class="p-4 border-t-2 border-[#243034] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#8FA1A0] w-full">
                            <div class="font-medium">
                                Showing {{ (currentPage - 1) * rowsPerPage + 1 }} to {{ Math.min(currentPage * rowsPerPage, filteredKecamatan.length) }} of {{ filteredKecamatan.length }} results
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 bg-[#0A0D0E] border-2 border-[#243034] px-3 py-1.5 rounded-12">
                                    <span>Per page</span>
                                    <select
                                        v-model="rowsPerPage"
                                        class="bg-transparent text-[#F4F7F5] font-bold outline-none cursor-pointer"
                                    >
                                        <option :value="5" class="bg-[#10171A]">5</option>
                                        <option :value="10" class="bg-[#10171A]">10</option>
                                        <option :value="20" class="bg-[#10171A]">20</option>
                                        <option :value="50" class="bg-[#10171A]">50</option>
                                    </select>
                                </div>

                                <!-- Pagination Controls -->
                                <div class="flex items-center gap-1.5">
                                    <button
                                        @click="currentPage = Math.max(1, currentPage - 1)"
                                        :disabled="currentPage === 1"
                                        class="btn-secondary-tactile h-8 px-2.5 text-xs disabled:opacity-40"
                                    >
                                        &lt;
                                    </button>
                                    <span class="px-2 text-[#F4F7F5] font-bold">{{ currentPage }} / {{ totalPages }}</span>
                                    <button
                                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                        :disabled="currentPage === totalPages"
                                        class="btn-secondary-tactile h-8 px-2.5 text-xs disabled:opacity-40"
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

                    <!-- FORM GRID FULL WIDTH RATA KANAN KIRI (Persis Screenshot 1 + Full Width) -->
                    <div class="w-full bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 md:p-8 shadow-[4px_4px_0_#243034] space-y-6">
                        
                        <!-- Row 1: 2 Kolom Seimbang Rata Kanan Kiri (Name* & Email*) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full">
                            <!-- Field 1: Name* -->
                            <div class="w-full">
                                <label class="block text-xs font-bold text-[#F4F7F5] mb-2">
                                    Name<span class="text-[#FF5C5C]">*</span>
                                </label>
                                <input
                                    v-model="formData.nama"
                                    type="text"
                                    placeholder="Contoh: Kecamatan Sumbang"
                                    class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4] focus:shadow-[2px_2px_0_#19F5D4] transition-all"
                                />
                            </div>

                            <!-- Field 2: Email* (Kode Kemendagri Wilayah) -->
                            <div class="w-full">
                                <label class="block text-xs font-bold text-[#F4F7F5] mb-2">
                                    Email / Kode Wilayah<span class="text-[#FF5C5C]">*</span>
                                </label>
                                <input
                                    v-model="formData.kode_kec"
                                    type="text"
                                    placeholder="Contoh: 33.02.21"
                                    class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4] focus:shadow-[2px_2px_0_#19F5D4] transition-all"
                                />
                            </div>
                        </div>

                        <!-- Row 2: 2 Kolom Seimbang (Project Type + Angka di Kiri, Message/Catatan di Kanan) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full">
                            <!-- Left Column: Project Type Dropdown & Angka Populasi -->
                            <div class="space-y-4 w-full">
                                <div>
                                    <label class="block text-xs font-bold text-[#F4F7F5] mb-2">
                                        Project Type (Komoditas Utama)
                                    </label>
                                    <select
                                        v-model="formData.kategori_utama"
                                        class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4] cursor-pointer"
                                    >
                                        <option value="Sapi Potong">Sapi Potong Pedaging</option>
                                        <option value="Sapi Perah">Sapi Perah Penghasil Susu</option>
                                        <option value="Kambing / Domba">Kambing & Domba</option>
                                        <option value="Ayam Broiler">Unggas / Ayam Broiler</option>
                                    </select>
                                </div>

                                <!-- Grid Angka Populasi & Kasus -->
                                <div class="grid grid-cols-2 gap-3.5 w-full">
                                    <div>
                                        <label class="block text-[11px] font-bold text-[#8FA1A0] mb-1">Populasi Sapi Potong</label>
                                        <input
                                            v-model.number="formData.sapi_potong"
                                            type="number"
                                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4]"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-[#8FA1A0] mb-1">Populasi Sapi Perah</label>
                                        <input
                                            v-model.number="formData.sapi_perah"
                                            type="number"
                                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4]"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-[#FF5C5C] mb-1">Kasus Aktif PMK</label>
                                        <input
                                            v-model.number="formData.pmk"
                                            type="number"
                                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#FF5C5C]/50 rounded-12 px-3.5 text-sm text-[#FF5C5C] font-bold outline-none focus:border-[#FF5C5C]"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-[#FFC53D] mb-1">Kasus Aktif LSD</label>
                                        <input
                                            v-model.number="formData.lsd"
                                            type="number"
                                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#FFC53D]/50 rounded-12 px-3.5 text-sm text-[#FFC53D] font-bold outline-none focus:border-[#FFC53D]"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-[#8FA1A0] mb-1">Status Zonasi EWS</label>
                                    <select
                                        v-model="formData.zona"
                                        class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3.5 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4]"
                                    >
                                        <option value="aman">Zona Hijau (Aman / Bebas Wabah)</option>
                                        <option value="waspada">Zona Kuning (Waspada)</option>
                                        <option value="bahaya">Zona Merah (Tertular / Bahaya)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Right Column: Message* (Catatan Lapangan Textarea, Spans Vertically) -->
                            <div class="flex flex-col w-full">
                                <label class="block text-xs font-bold text-[#F4F7F5] mb-2">
                                    Message / Catatan Lapangan<span class="text-[#FF5C5C]">*</span>
                                </label>
                                <textarea
                                    v-model="formData.catatan"
                                    rows="9"
                                    placeholder="Tuliskan catatan verifikasi lapangan, riwayat vaksinasi, atau anjuran biosekuriti dinas..."
                                    class="w-full flex-1 min-h-[220px] bg-[#0A0D0E] border-2 border-[#243034] rounded-12 p-4 text-sm text-[#F4F7F5] outline-none focus:border-[#19F5D4] focus:shadow-[2px_2px_0_#19F5D4] resize-y transition-all"
                                ></textarea>
                            </div>
                        </div>

                        <!-- TOMBOL AKSI: CREATE, Create & create another, Cancel (Persis Screenshot 1) -->
                        <div class="flex flex-wrap items-center gap-3 pt-5 border-t-2 border-[#243034] w-full">
                            <!-- CREATE Button (Neon Hijau Tactile) -->
                            <button
                                @click="handleSaveForm(false)"
                                class="btn-primary-tactile h-11 px-7 text-xs font-bold tracking-wider"
                            >
                                CREATE
                            </button>

                            <!-- Create & create another -->
                            <button
                                @click="handleSaveForm(true)"
                                class="btn-secondary-tactile h-11 px-5 text-xs font-bold"
                            >
                                Create & create another
                            </button>

                            <!-- Cancel -->
                            <button
                                @click="currentTab = 'data'"
                                class="h-11 px-5 rounded-12 border-2 border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#F4F7F5] text-xs font-bold transition-all"
                            >
                                Cancel
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
                    <div class="w-full bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 shadow-[3px_3px_0_#243034] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b-2 border-[#243034]">
                            <h3 class="font-display font-bold text-lg text-[#F4F7F5] flex items-center gap-2">
                                <ShieldAlert class="w-5 h-5 text-[#39FF14]" />
                                <span>Konfigurasi Ambang Batas Zonasi Risiko (FR-SYS-02)</span>
                            </h3>
                            <button
                                @click="recalculateZonasi"
                                class="btn-primary-tactile h-9 px-4 text-xs font-bold gap-1.5"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span>Terapkan & Rekalkulasi Batch</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-xs w-full">
                            <div class="p-4 bg-[#0A0D0E] border-2 border-[#243034] rounded-12">
                                <span class="text-[#8FA1A0] font-bold block mb-1">Jendela Hari Analisis</span>
                                <input
                                    v-model.number="ewsParams.jendelaHari"
                                    type="number"
                                    class="w-full bg-transparent text-[#F4F7F5] font-display font-extrabold text-2xl outline-none"
                                />
                                <span class="text-[10px] text-[#8FA1A0]">Default: 14 hari terakhir</span>
                            </div>

                            <div class="p-4 bg-[#0A0D0E] border-2 border-[#FFC53D] rounded-12">
                                <span class="text-[#FFC53D] font-bold block mb-1">Ambang Kuning (Waspada)</span>
                                <input
                                    v-model.number="ewsParams.ambangKuningMaks"
                                    type="number"
                                    class="w-full bg-transparent text-[#FFC53D] font-display font-extrabold text-2xl outline-none"
                                />
                                <span class="text-[10px] text-[#8FA1A0]">1 s.d. 5 kasus aktif</span>
                            </div>

                            <div class="p-4 bg-[#0A0D0E] border-2 border-[#FF5C5C] rounded-12">
                                <span class="text-[#FF5C5C] font-bold block mb-1">Ambang Merah (Tertular)</span>
                                <input
                                    v-model.number="ewsParams.ambangMerahMin"
                                    type="number"
                                    class="w-full bg-transparent text-[#FF5C5C] font-display font-extrabold text-2xl outline-none"
                                />
                                <span class="text-[10px] text-[#8FA1A0]">&gt; 5 kasus aktif</span>
                            </div>
                        </div>
                    </div>

                    <!-- Snapshot 27 Kecamatan Zonasi -->
                    <div class="w-full bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 shadow-[3px_3px_0_#243034]">
                        <h3 class="font-display font-bold text-lg text-[#F4F7F5] mb-4">
                            Snapshot Zonasi 27 Kecamatan Banyumas (Tahun 2026)
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 text-xs w-full">
                            <div
                                v-for="kec in kecamatanList"
                                :key="kec.id"
                                class="p-3 rounded-12 border-2 flex items-center justify-between"
                                :class="kec.zona === 'bahaya' ? 'bg-[#FF5C5C]/10 border-[#FF5C5C] text-[#FF5C5C]' : (kec.zona === 'waspada' ? 'bg-[#FFC53D]/10 border-[#FFC53D] text-[#FFC53D]' : 'bg-[#39FF14]/10 border-[#39FF14] text-[#39FF14]')"
                            >
                                <span class="font-bold truncate">{{ kec.nama }}</span>
                                <span class="text-[10px] uppercase font-extrabold">{{ kec.zona }}</span>
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
                        <p class="text-xs text-[#8FA1A0] mt-1">
                            Mandat KAK Geospasial Peternakan 2026: Fasilitas Pemotongan Resmi & RPH Swasta
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 w-full">
                        <div
                            v-for="rph in rphList"
                            :key="rph.id"
                            class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-2.5 hover:border-[#19F5D4] shadow-[3px_3px_0_#243034] transition-all"
                        >
                            <div class="flex items-start justify-between">
                                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">{{ rph.nama }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#39FF14]/10 text-[#39FF14] border-2 border-[#39FF14] font-bold text-[10px]">
                                    {{ rph.status }}
                                </span>
                            </div>
                            <div class="text-xs text-[#8FA1A0] space-y-1.5">
                                <div><b class="text-[#F4F7F5]">Kecamatan:</b> {{ rph.kecamatan }}</div>
                                <div><b class="text-[#F4F7F5]">Pengelola:</b> {{ rph.pengelola }}</div>
                                <div><b class="text-[#F4F7F5]">Kapasitas:</b> {{ rph.kapasitas }} ekor/hari ({{ rph.jenis }})</div>
                                <div class="text-[11px] text-[#19F5D4] font-bold">GPS: {{ rph.lat }}, {{ rph.lng }}</div>
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
                        <p class="text-xs text-[#8FA1A0] mt-1">
                            Ekspor berkas terstandarisasi untuk Geoportal Banyumas dan Laporan Triwulanan
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                        <!-- Kartu 1: Laporan Excel Multi-Sheet -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3.5 shadow-[3px_3px_0_#243034]">
                            <div class="flex items-center gap-2.5 text-[#39FF14]">
                                <FileDown class="w-6 h-6" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Buku Kerja Excel Multi-Sheet (.xlsx)</h3>
                            </div>
                            <p class="text-xs text-[#8FA1A0] leading-relaxed">
                                Berkas Excel memuat 5 lembar terpisah: Ringkasan, Populasi Ternak, Produksi, Kasus Penyakit, dan Sebaran RPH 27 kecamatan.
                            </p>
                            <button
                                @click="showToast('Mengunduh Laporan_Peternakan_Banyumas_2026_TW2.xlsx')"
                                class="btn-primary-tactile w-full h-11 text-xs font-bold gap-2"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Excel Multi-Sheet</span>
                            </button>
                        </div>

                        <!-- Kartu 2: Dokumen PDF Resmi -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3.5 shadow-[3px_3px_0_#243034]">
                            <div class="flex items-center gap-2.5 text-[#19F5D4]">
                                <FileText class="w-6 h-6" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Laporan Ringkasan Eksekutif (PDF)</h3>
                            </div>
                            <p class="text-xs text-[#8FA1A0] leading-relaxed">
                                Dokumen PDF resmi berstempel dinas memuat ringkasan eksekutif, tabel data, peta zonasi, dan lembar pengesahan Kepala Bidang.
                            </p>
                            <button
                                @click="showToast('Mengunduh Laporan_Ringkasan_Eksekutif_Banyumas_2026.pdf')"
                                class="btn-secondary-tactile w-full h-11 text-xs font-bold gap-2 text-[#19F5D4] hover:text-[#39FF14]"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Dokumen PDF</span>
                            </button>
                        </div>

                        <!-- Kartu 3: Metadata ISO 19115 XML -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3.5 shadow-[3px_3px_0_#243034]">
                            <div class="flex items-center gap-2.5 text-[#FFC53D]">
                                <Compass class="w-6 h-6" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Metadata Geospasial ISO 19115 (XML)</h3>
                            </div>
                            <p class="text-xs text-[#8FA1A0] leading-relaxed">
                                Format XML resmi KAK Bagian 4.d untuk interoperabilitas panen katalog (harvesting) Geoportal Kabupaten Banyumas.
                            </p>
                            <button
                                @click="showToast('Mengunduh ID-DINPERTAN-BMS-TERNAKVERSE-2026.xml')"
                                class="btn-secondary-tactile w-full h-11 text-xs font-bold gap-2 text-[#FFC53D]"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Metadata XML</span>
                            </button>
                        </div>

                        <!-- Kartu 4: Shapefile KUGI (.zip) -->
                        <div class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3.5 shadow-[3px_3px_0_#243034]">
                            <div class="flex items-center gap-2.5 text-[#39FF14]">
                                <Layers class="w-6 h-6" />
                                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Shapefile KUGI Geoportal (.zip)</h3>
                            </div>
                            <p class="text-xs text-[#8FA1A0] leading-relaxed">
                                Unsur ADMINISTRASI_AR_PETERNAKAN lengkap dengan file .shp, .shx, .dbf, .prj proyeksi SRGI2013-WGS84.
                            </p>
                            <button
                                @click="showToast('Mengunduh BANYUMAS_PETERNAKAN_KUGI_SHP.zip')"
                                class="btn-secondary-tactile w-full h-11 text-xs font-bold gap-2 text-[#39FF14]"
                            >
                                <Download class="w-4 h-4" />
                                <span>Unduh Paket SHP KUGI</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------------- -->
                <!-- 7. VIEW: MY PROFILE (Sesuai Sidebar Screenshot) -->
                <!-- ---------------------------------------------------- -->
                <div v-else-if="currentTab === 'profile'" class="w-full space-y-6">
                    <div>
                        <h1 class="font-display font-extrabold text-3xl tracking-tight text-[#F4F7F5]">
                            My Profile
                        </h1>
                        <p class="text-xs text-[#8FA1A0] mt-1">
                            Akun Super Administrator Sistem TERNAKVERSE
                        </p>
                    </div>

                    <div class="w-full bg-[#10171A] border-2 border-[#19F5D4] rounded-20 p-6 md:p-8 shadow-[4px_4px_0_#19F5D4] space-y-6">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-16 bg-[#0A0D0E] border-2 border-[#39FF14] flex items-center justify-center font-display font-black text-2xl text-[#39FF14] shadow-[3px_3px_0_#243034]">
                                S
                            </div>
                            <div>
                                <h3 class="font-display font-extrabold text-2xl text-[#F4F7F5]">Satrio</h3>
                                <p class="text-xs text-[#8FA1A0] mt-0.5">NIP: 19850412 201001 1 008</p>
                                <span class="inline-block mt-1 px-3 py-0.5 rounded-full bg-[#39FF14] text-[#04110A] text-xs font-extrabold border border-[#F4F7F5]">
                                    ROLE: SUPER_ADMIN
                                </span>
                            </div>
                        </div>

                        <div class="divide-y-2 divide-[#243034] text-xs">
                            <div class="py-3.5 flex justify-between">
                                <span class="text-[#8FA1A0] font-bold">Email Dinas:</span>
                                <span class="text-[#F4F7F5] font-bold">admin.diskanak@banyumaskab.go.id</span>
                            </div>
                            <div class="py-3.5 flex justify-between">
                                <span class="text-[#8FA1A0] font-bold">Unit Kerja:</span>
                                <span class="text-[#F4F7F5]">Bidang Peternakan & Keswan</span>
                            </div>
                            <div class="py-3.5 flex justify-between">
                                <span class="text-[#8FA1A0] font-bold">Instansi:</span>
                                <span class="text-[#F4F7F5]">Dinas Pertanian dan Ketahanan Pangan</span>
                            </div>
                            <div class="py-3.5 flex justify-between">
                                <span class="text-[#8FA1A0] font-bold">Status Akun:</span>
                                <span class="text-[#39FF14] font-extrabold">Aktif (2FA Terlindungi)</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                @click="handleLogout"
                                class="h-11 px-6 rounded-12 bg-[#FF5C5C]/15 border-2 border-[#FF5C5C] text-[#FF5C5C] hover:bg-[#FF5C5C] hover:text-[#04110A] font-bold text-xs flex items-center justify-center gap-2 transition-all duration-100"
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
        <!-- MODAL DIALOG IMPOR CSV/EXCEL (Bab 6: Radius 24px, Border 2px Cyan) -->
        <!-- ==================================================== -->
        <div
            v-if="isImportModalOpen"
            class="fixed inset-0 z-50 bg-[#0A0D0E]/80 flex items-center justify-center p-4 select-none"
            @click.self="isImportModalOpen = false"
        >
            <div class="bg-[#10171A] border-2 border-[#19F5D4] rounded-24 p-6 w-full max-w-lg shadow-[4px_4px_0_#19F5D4] animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b-2 border-[#243034] mb-4">
                    <h3 class="font-display font-extrabold text-xl text-[#F4F7F5] flex items-center gap-2">
                        <Upload class="w-5 h-5 text-[#39FF14]" />
                        <span>Impor Data CSV / Excel</span>
                    </h3>
                    <button
                        @click="isImportModalOpen = false"
                        class="w-8 h-8 rounded-full border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#19F5D4] flex items-center justify-center transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <p class="text-xs text-[#8FA1A0]">
                        Unggah berkas lembar kerja rekapitulasi data peternakan (.csv atau .xlsx). Sistem akan memvalidasi kode kecamatan dan menghitung total agregat secara otomatis.
                    </p>

                    <!-- Dropzone -->
                    <div class="border-2 border-dashed border-[#243034] hover:border-[#39FF14] rounded-16 p-6 text-center cursor-pointer transition-colors bg-[#0A0D0E]">
                        <Upload class="w-8 h-8 text-[#39FF14] mx-auto mb-2" />
                        <span class="text-xs font-bold text-[#F4F7F5] block">Tarik berkas ke sini atau klik untuk memilih</span>
                        <span class="text-[10px] text-[#8FA1A0] block mt-1">Mendukung format .CSV, .XLS, .XLSX (Maks 10 MB)</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t-2 border-[#243034]">
                        <button
                            @click="isImportModalOpen = false"
                            class="h-10 px-4 rounded-12 border-2 border-[#243034] text-xs font-bold text-[#8FA1A0] hover:text-[#F4F7F5]"
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
        <!-- MODAL DIALOG EKSPOR CSV/EXCEL (100% Terlihat, Bebas Clipping) -->
        <!-- ==================================================== -->
        <div
            v-if="isExportModalOpen"
            class="fixed inset-0 z-50 bg-[#0A0D0E]/85 flex items-center justify-center p-4 select-none animate-in fade-in duration-150"
            @click.self="isExportModalOpen = false"
        >
            <div class="bg-[#10171A] border-2 border-[#19F5D4] rounded-24 p-6 w-full max-w-md shadow-[4px_4px_0_#19F5D4] space-y-4 font-ui">
                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b-2 border-[#243034]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-10 bg-[#0A0D0E] border border-[#19F5D4] flex items-center justify-center text-[#19F5D4] shadow-[2px_2px_0_#243034]">
                            <Download class="w-4 h-4 text-[#19F5D4]" />
                        </div>
                        <div>
                            <h3 class="font-display font-extrabold text-lg text-[#F4F7F5] leading-none">
                                Ekspor Data Peternakan
                            </h3>
                            <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">
                                Unduh rekapitulasi data kecamatan ke spreadsheet
                            </span>
                        </div>
                    </div>
                    <button
                        @click="isExportModalOpen = false"
                        class="w-8 h-8 rounded-full border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#FF5C5C] flex items-center justify-center transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Scope Data Info -->
                <div class="bg-[#0A0D0E] border border-[#243034] rounded-12 p-3 text-xs text-[#8FA1A0] flex items-center justify-between">
                    <span>Cakupan Data:</span>
                    <span class="font-bold text-[#39FF14]">
                        {{ selectedRowIds.length > 0 ? selectedRowIds.length + ' baris dipilih' : filteredKecamatan.length + ' kecamatan (semua hasil filter)' }}
                    </span>
                </div>

                <!-- Pilihan Format Ekspor (2 Kartu Tactile) -->
                <div class="space-y-2.5 pt-1">
                    <!-- Format Excel -->
                    <button
                        @click="handleExport('excel')"
                        class="w-full p-3.5 rounded-14 border-2 border-[#243034] hover:border-[#39FF14] bg-[#0A0D0E] hover:bg-[#141C1F] flex items-center justify-between transition-all group text-left shadow-[2px_2px_0_#243034]"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-10 bg-[#39FF14]/10 border border-[#39FF14] flex items-center justify-center text-[#39FF14] flex-shrink-0">
                                <FileText class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#F4F7F5] group-hover:text-[#39FF14] flex items-center gap-2">
                                    <span>Microsoft Excel (.xls)</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-[#39FF14]/20 text-[#39FF14]">XLS</span>
                                </div>
                                <p class="text-[11px] text-[#8FA1A0] mt-0.5">
                                    Format spreadsheet langsung rapi di Microsoft Excel &amp; Google Sheets.
                                </p>
                            </div>
                        </div>
                        <Download class="w-4 h-4 text-[#8FA1A0] group-hover:text-[#39FF14] transition-colors" />
                    </button>

                    <!-- Format CSV -->
                    <button
                        @click="handleExport('csv')"
                        class="w-full p-3.5 rounded-14 border-2 border-[#243034] hover:border-[#19F5D4] bg-[#0A0D0E] hover:bg-[#141C1F] flex items-center justify-between transition-all group text-left shadow-[2px_2px_0_#243034]"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-10 bg-[#19F5D4]/10 border border-[#19F5D4] flex items-center justify-center text-[#19F5D4] flex-shrink-0">
                                <FileDown class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#F4F7F5] group-hover:text-[#19F5D4] flex items-center gap-2">
                                    <span>Comma-Separated Values (.csv)</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-[#19F5D4]/20 text-[#19F5D4]">CSV</span>
                                </div>
                                <p class="text-[11px] text-[#8FA1A0] mt-0.5">
                                    Format teks standar dengan encoding UTF-8 BOM dan pemisah koma.
                                </p>
                            </div>
                        </div>
                        <Download class="w-4 h-4 text-[#8FA1A0] group-hover:text-[#19F5D4] transition-colors" />
                    </button>
                </div>

                <!-- Tombol Batal -->
                <div class="pt-2 flex justify-end">
                    <button
                        @click="isExportModalOpen = false"
                        class="h-9 px-4 rounded-10 border border-[#243034] text-xs font-bold text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#F4F7F5] transition-all"
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
            class="fixed inset-0 z-50 bg-[#0A0D0E]/85 flex items-center justify-center p-4 select-none animate-in fade-in duration-150"
            @click.self="isFilterModalOpen = false"
        >
            <div class="bg-[#10171A] border-2 border-[#19F5D4] rounded-24 p-6 w-full max-w-md shadow-[4px_4px_0_#19F5D4] space-y-4 font-ui">
                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b-2 border-[#243034]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-10 bg-[#0A0D0E] border border-[#19F5D4] flex items-center justify-center text-[#19F5D4]">
                            <SlidersHorizontal class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="font-display font-extrabold text-lg text-[#F4F7F5] leading-none">
                                Parameter Filter Lanjutan
                            </h3>
                            <span class="text-[11px] text-[#8FA1A0] mt-0.5 block">
                                Sesuaikan kriteria populasi, kasus wabah, &amp; fasilitas
                            </span>
                        </div>
                    </div>
                    <button
                        @click="isFilterModalOpen = false"
                        class="w-8 h-8 rounded-full border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#FF5C5C] flex items-center justify-center transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5">
                    <!-- Filter Kasus Wabah -->
                    <div>
                        <label class="block text-xs font-bold text-[#F4F7F5] mb-1.5">
                            Status Kasus Wabah (PMK / LSD)
                        </label>
                        <select
                            v-model="selectedWabahFilter"
                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3 text-xs text-[#F4F7F5] outline-none focus:border-[#19F5D4]"
                        >
                            <option value="semua">Semua Kondisi Kasus</option>
                            <option value="ada_kasus">⚠️ Hanya Kecamatan Berkasus (&gt;0 Kasus)</option>
                            <option value="bebas_kasus">✅ Bebas Kasus Wabah (0 Kasus)</option>
                        </select>
                    </div>

                    <!-- Filter Fasilitas Poskeswan -->
                    <div>
                        <label class="block text-xs font-bold text-[#F4F7F5] mb-1.5">
                            Fasilitas Poskeswan
                        </label>
                        <select
                            v-model="selectedPoskeswanFilter"
                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3 text-xs text-[#F4F7F5] outline-none focus:border-[#19F5D4]"
                        >
                            <option value="semua">Semua Kecamatan</option>
                            <option value="ada">Tersedia Poskeswan (&ge; 1 Unit)</option>
                            <option value="tidak_ada">Belum Ada Poskeswan (0 Unit)</option>
                        </select>
                    </div>

                    <!-- Filter Ambang Populasi Minimum -->
                    <div>
                        <label class="block text-xs font-bold text-[#F4F7F5] mb-1.5">
                            Ambang Populasi Ternak Minimum
                        </label>
                        <select
                            v-model.number="minPopulasiFilter"
                            class="w-full h-11 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 px-3 text-xs text-[#F4F7F5] outline-none focus:border-[#19F5D4]"
                        >
                            <option :value="0">Tanpa Batas Minimum</option>
                            <option :value="1000">&ge; 1.000 Ekor</option>
                            <option :value="3000">&ge; 3.000 Ekor (Sentra Utama)</option>
                            <option :value="5000">&ge; 5.000 Ekor (Populasi Sangat Tinggi)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t-2 border-[#243034] flex items-center justify-between">
                    <button
                        @click="resetFilters"
                        class="text-xs text-[#FF5C5C] hover:underline font-bold"
                    >
                        Reset Parameter
                    </button>
                    <button
                        @click="isFilterModalOpen = false"
                        class="btn-primary-tactile h-10 px-5 text-xs font-bold"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================================================== -->
        <!-- TOAST NOTIFIKASI DI SUDUT BAWAH (Bab 6: Pil + Bayangan Cyan) -->
        <!-- ==================================================== -->
        <div
            v-if="toastMsg"
            class="fixed bottom-6 right-6 z-50 bg-[#10171A] border-2 border-[#19F5D4] text-[#F4F7F5] px-5 py-3 rounded-full shadow-[3px_3px_0_#19F5D4] text-xs font-bold flex items-center gap-2.5 animate-in fade-in slide-in-from-bottom-2 duration-150"
        >
            <Check class="w-4 h-4 text-[#39FF14] stroke-[3]" />
            <span>{{ toastMsg }}</span>
        </div>

        <!-- DIAGNOSTIC BOX (Bab 13 Design Rule - Diposisikan di Kanan Bawah) -->
        <DiagnosticBox />
    </div>
</template>

/* BERKAS-UTUH: AdminPortal.vue */
