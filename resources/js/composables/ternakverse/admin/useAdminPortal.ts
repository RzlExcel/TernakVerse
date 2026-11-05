import { ref, computed, watch, type Ref, type ComputedRef } from 'vue';
import { router } from '@inertiajs/vue3';
import { KECAMATAN_LIST, type KecamatanData } from '../../../data/banyumasData';

export type KecamatanDataSource = Ref<KecamatanData[]> | ComputedRef<KecamatanData[]> | (() => KecamatanData[]);

export type AdminTab = 'dashboard' | 'webgis' | 'data' | 'create' | 'ews' | 'rph' | 'laporan' | 'profile' | 'rbac' | 'master_data';

export interface AuditLogItem {
    id: number;
    user: string;
    action: 'UPDATE' | 'INSERT' | 'DELETE' | 'CALCULATE' | 'EXPORT' | 'BROADCAST';
    table: string;
    detail: string;
    time: string;
    status: 'SUCCESS' | 'WARNING';
}

export interface RphFacility {
    id: number;
    nama: string;
    kecamatan: string;
    pengelola: string;
    kapasitas: number;
    jenis: string;
    status: 'Aktif Operasional' | 'Pemeliharaan Terjadwal';
    lat: number;
    lng: number;
    tipe?: 'RPH' | 'Puskeswan' | 'Pasar Hewan';
    hari_pasaran?: string;
    wilayah_binaan?: string;
}

export interface AdminFormData {
    nama: string;
    kode_kec: string;
    zona: 'bahaya' | 'waspada' | 'aman';
    kategori_utama: string;
    sapi_potong: number;
    sapi_perah: number;
    kambing: number;
    kambing_perah?: number;
    domba: number;
    ayam_broiler: number;
    ayam_petelur?: number;
    susu_liter: number;
    daging_sapi: number;
    pmk: number;
    lsd: number;
    poskeswan: number;
    luas_hmt_ha?: number;
    catatan: string;
}

export interface EwsParameters {
    jendelaHari: number;
    ambangKuningMaks: number;
    ambangMerahMin: number;
}

export interface ActiveFilters {
    category: string;
    zona: string;
    wabah: string;
    poskeswan: string;
    minPopulasi: number;
}

export interface AdminProfileSettings {
    nama: string;
    nip: string;
    role: string;
    email: string;
    unitKerja: string;
    instansi: string;
    statusAkun: string;
    notifEwsEmail: boolean;
    notifWhatsappDarurat: boolean;
    autoSyncGeoportal: boolean;
}

function escapeSpreadsheetCell(val: string | number | null | undefined, isExcel: boolean): string {
    if (val === null || val === undefined) return '';
    let s = String(val);
    if (/^[=+\-@\t\r]/.test(s)) {
        s = `'${s}`;
    }
    if (isExcel) return s;
    if (s.includes(',') || s.includes('"') || s.includes('\n') || s.includes('\r')) {
        return `"${s.replace(/"/g, '""')}"`;
    }
    return s;
}

export function useAdminPortal(customKecamatanSource?: KecamatanDataSource) {
    // ----------------------------------------------------
    // NAVIGASI TAB & RESPONSIVE DRAWER / SIDEBAR STATE
    // ----------------------------------------------------
    const currentTab = ref<AdminTab>('dashboard');
    const isMobileMenuOpen = ref(false);
    const isSidebarCollapsed = ref(false);

    // State Analitik & Periode (Sinkron dengan konteks portal)
    const selectedTahun = ref<number>(2026);
    const selectedTriwulan = ref<string>('TW2');
    const selectedMetric = ref<string>('sapi_potong');
    const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
    const selectedKecamatanId = ref<string | null>(null);
    const mapEngine = ref<'leaflet' | 'svg'>('leaflet');

    function selectTab(tab: AdminTab): void {
        currentTab.value = tab;
        isMobileMenuOpen.value = false;
    }

    // ----------------------------------------------------
    // MASTER DATA 27 KECAMATAN BANYUMAS
    // ----------------------------------------------------
    const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
    const dynamicSource = ref<KecamatanDataSource | null>(
        customKecamatanSource ?? null
    ) as Ref<KecamatanDataSource | null>;

    function setKecamatanSource(source: KecamatanDataSource): void {
        dynamicSource.value = source;
    }

    function resolveSourceList(): KecamatanData[] {
        if (!dynamicSource.value) {
            return kecamatanList.value;
        }
        const src = dynamicSource.value;
        if (typeof src === 'function') {
            return src();
        }
        return src.value;
    }

    // Search & Modal di Dashboard
    const searchQueryDashboard = ref('');
    const isImportModalOpen = ref(false);

    // ----------------------------------------------------
    // AUDIT TRAIL LOG FEED (PRD FR-SYS-01)
    // ----------------------------------------------------
    const auditLogs = ref<AuditLogItem[]>([
        { id: 1, user: 'Satrio (Super Admin)', action: 'UPDATE', table: 'populasi_ternak', detail: 'Sinkronisasi populasi Sapi Potong Sumbang (+120 ekor)', time: '2 menit lalu', status: 'SUCCESS' },
        { id: 2, user: 'Dr. Hendra (Medik Vet)', action: 'INSERT', table: 'kasus_penyakit', detail: 'Pencatatan vaksinasi PMK Baturraden 850 dosis', time: '18 menit lalu', status: 'SUCCESS' },
        { id: 3, user: 'Sistem Sinkron', action: 'CALCULATE', table: 'zona_snapshot_kecamatan', detail: 'Kalkulasi zonasi EWS 27 kecamatan: 14 Merah, 7 Kuning, 6 Aman', time: '1 jam lalu', status: 'SUCCESS' },
        { id: 4, user: 'Satrio (Super Admin)', action: 'EXPORT', table: 'laporan_periodik', detail: 'Generate Laporan Excel Rekapitulasi Triwulan II 2026', time: '3 jam lalu', status: 'SUCCESS' },
    ]);

    // ----------------------------------------------------
    // TOAST NOTIFIKASI
    // ----------------------------------------------------
    const toastMsg = ref<string | null>(null);
    function showToast(msg: string): void {
        toastMsg.value = msg;
        setTimeout(() => {
            toastMsg.value = null;
        }, 4000);
    }

    // ----------------------------------------------------
    // TABLE LIST VIEW & MULTI-CRITERIA FILTER STATE
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

    const activeFilters = computed<ActiveFilters>(() => ({
        category: selectedCategoryFilter.value,
        zona: selectedZonaFilter.value,
        wabah: selectedWabahFilter.value,
        poskeswan: selectedPoskeswanFilter.value,
        minPopulasi: minPopulasiFilter.value,
    }));

    function handleDashboardSearch(): void {
        if (searchQueryDashboard.value.trim()) {
            searchQuery.value = searchQueryDashboard.value.trim();
            selectTab('data');
        }
    }

    function handleImportSuccess(importedRows?: Record<string, unknown>[]): void {
        isImportModalOpen.value = false;
        if (importedRows && importedRows.length > 0) {
            let updatedCount = 0;

            kecamatanList.value = kecamatanList.value.map(k => {
                const match = importedRows.find(row => {
                    const rKode = row.kode_kec !== undefined && row.kode_kec !== null ? String(row.kode_kec).trim().toLowerCase() : '';
                    const rNama = row.nama !== undefined && row.nama !== null ? String(row.nama).trim().toLowerCase() : '';
                    const kNama = k.nama.toLowerCase().trim();
                    const kKode = k.kode_kec.toLowerCase().trim();
                    return (
                        (rKode !== '' && kKode === rKode) ||
                        (rNama !== '' && (kNama === rNama || kNama.replace(/\s+/g, '') === rNama.replace(/\s+/g, '')))
                    );
                });

                if (!match) return k;

                updatedCount++;
                const updated: KecamatanData = { ...k };

                if (typeof match.sapi_potong === 'number' && !Number.isNaN(match.sapi_potong)) {
                    updated.sapi_potong = match.sapi_potong;
                }
                if (typeof match.sapi_perah === 'number' && !Number.isNaN(match.sapi_perah)) {
                    updated.sapi_perah = match.sapi_perah;
                }
                if (typeof match.kambing === 'number' && !Number.isNaN(match.kambing)) {
                    updated.kambing = match.kambing;
                }
                if (typeof match.domba === 'number' && !Number.isNaN(match.domba)) {
                    updated.domba = match.domba;
                }
                if (typeof match.ayam_potong === 'number' && !Number.isNaN(match.ayam_potong)) {
                    updated.ayam_potong = match.ayam_potong;
                    updated.ayam_broiler = match.ayam_potong;
                }
                if (typeof match.ayam_layer === 'number' && !Number.isNaN(match.ayam_layer)) {
                    updated.ayam_petelur = match.ayam_layer;
                }
                if (typeof match.daging_sapi === 'number' && !Number.isNaN(match.daging_sapi)) {
                    updated.daging_sapi = match.daging_sapi;
                }
                if (typeof match.susu_liter === 'number' && !Number.isNaN(match.susu_liter)) {
                    updated.susu_liter = match.susu_liter;
                    updated.susu_sapi = match.susu_liter;
                }
                if (typeof match.pmk === 'number' && !Number.isNaN(match.pmk)) {
                    updated.pmk = match.pmk;
                }
                if (typeof match.lsd === 'number' && !Number.isNaN(match.lsd)) {
                    updated.lsd = match.lsd;
                }
                if (typeof match.poskeswan === 'number' && !Number.isNaN(match.poskeswan)) {
                    updated.poskeswan = match.poskeswan;
                }

                // Kalkulasi zonasi dinamis EWS berdasarkan kasus aktif PMK dan LSD
                const totalKasus = (updated.pmk || 0) + (updated.lsd || 0);
                if (totalKasus > 10) {
                    updated.zona = 'bahaya';
                } else if (totalKasus > 0) {
                    updated.zona = 'waspada';
                } else {
                    updated.zona = 'aman';
                }

                updated.last_updated = new Date().toISOString().slice(0, 10);
                return updated;
            });

            auditLogs.value.unshift({
                id: Date.now(),
                user: 'Satrio (Super Admin)',
                action: 'UPDATE',
                table: 'populasi_ternak',
                detail: `Sinkronisasi batch impor: ${updatedCount} kecamatan berhasil diperbarui melalui smart alias mapping`,
                time: 'Baru saja',
                status: 'SUCCESS',
            });
            showToast(`Berhasil mengimpor & memperbarui ${updatedCount} kecamatan dengan smart alias mapping.`);
        } else {
            auditLogs.value.unshift({
                id: Date.now(),
                user: 'Satrio (Super Admin)',
                action: 'INSERT',
                table: 'populasi_ternak',
                detail: 'Impor batch berkas CSV/Excel 27 kecamatan',
                time: 'Baru saja',
                status: 'SUCCESS',
            });
            showToast('Data CSV/Excel berhasil diimpor & tersinkronisasi ke peta.');
        }
    }

    const filteredKecamatan = computed<KecamatanData[]>(() => {
        return resolveSourceList().filter(k => {
            const q = searchQuery.value.toLowerCase().trim();
            const matchesQuery =
                !q ||
                k.nama.toLowerCase().includes(q) ||
                k.kode_kec.toLowerCase().includes(q);

            let matchesCat = true;
            if (selectedCategoryFilter.value === 'sapi_potong') {
                matchesCat = (k.sapi_potong || 0) > 500;
            } else if (selectedCategoryFilter.value === 'sapi_perah') {
                matchesCat = (k.sapi_perah || 0) > 50;
            } else if (selectedCategoryFilter.value === 'kambing_domba') {
                matchesCat = (k.kambing || 0) + (k.domba || 0) > 1000;
            } else if (selectedCategoryFilter.value === 'unggas') {
                matchesCat = (k.ayam_broiler ?? k.ayam_potong ?? 0) > 5000;
            }

            let matchesZona = true;
            if (selectedZonaFilter.value !== 'semua') {
                matchesZona = k.zona === selectedZonaFilter.value;
            }

            let matchesWabah = true;
            if (selectedWabahFilter.value === 'ada_kasus') {
                matchesWabah = (k.pmk || 0) + (k.lsd || 0) > 0;
            } else if (selectedWabahFilter.value === 'bebas_kasus') {
                matchesWabah = (k.pmk || 0) + (k.lsd || 0) === 0;
            }

            let matchesPoskeswan = true;
            if (selectedPoskeswanFilter.value === 'ada') {
                matchesPoskeswan = (k.poskeswan || 0) > 0;
            } else if (selectedPoskeswanFilter.value === 'tidak_ada') {
                matchesPoskeswan = (k.poskeswan || 0) === 0;
            }

            let matchesPopulasi = true;
            if (minPopulasiFilter.value > 0) {
                const totalTernak =
                    k.populasi_total ??
                    ((k.sapi_potong || 0) +
                    (k.sapi_perah || 0) +
                    (k.kambing || 0) +
                    (k.domba || 0) +
                    (k.ayam_broiler ?? k.ayam_potong ?? 0));
                matchesPopulasi = totalTernak >= minPopulasiFilter.value;
            }

            return (
                matchesQuery &&
                matchesCat &&
                matchesZona &&
                matchesWabah &&
                matchesPoskeswan &&
                matchesPopulasi
            );
        });
    });

    const paginatedKecamatan = computed<KecamatanData[]>(() => {
        const start = (currentPage.value - 1) * rowsPerPage.value;
        return filteredKecamatan.value.slice(start, start + rowsPerPage.value);
    });

    const totalPages = computed<number>(() => {
        return Math.ceil(filteredKecamatan.value.length / rowsPerPage.value) || 1;
    });

    const hasActiveFilters = computed<boolean>(() => {
        return (
            searchQuery.value.trim() !== '' ||
            selectedCategoryFilter.value !== 'semua' ||
            selectedZonaFilter.value !== 'semua' ||
            selectedWabahFilter.value !== 'semua' ||
            selectedPoskeswanFilter.value !== 'semua' ||
            minPopulasiFilter.value > 0
        );
    });

    const activeFilterCount = computed<number>(() => {
        let count = 0;
        if (searchQuery.value.trim() !== '') count++;
        if (selectedCategoryFilter.value !== 'semua') count++;
        if (selectedZonaFilter.value !== 'semua') count++;
        if (selectedWabahFilter.value !== 'semua') count++;
        if (selectedPoskeswanFilter.value !== 'semua') count++;
        if (minPopulasiFilter.value > 0) count++;
        return count;
    });

    watch(
        [
            searchQuery,
            selectedCategoryFilter,
            selectedZonaFilter,
            selectedWabahFilter,
            selectedPoskeswanFilter,
            minPopulasiFilter,
        ],
        () => {
            currentPage.value = 1;
        }
    );

    function toggleSelectAll(): void {
        if (selectedRowIds.value.length === paginatedKecamatan.value.length) {
            selectedRowIds.value = [];
        } else {
            selectedRowIds.value = paginatedKecamatan.value.map(k => k.id);
        }
    }

    function toggleSelectRow(id: string): void {
        const idx = selectedRowIds.value.indexOf(id);
        if (idx > -1) {
            selectedRowIds.value.splice(idx, 1);
        } else {
            selectedRowIds.value.push(id);
        }
    }

    function resetFilters(): void {
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

    function handleExport(format: 'excel' | 'csv'): void {
        isExportDropdownOpen.value = false;
        isExportModalOpen.value = false;
        const currentSource = resolveSourceList();
        const dataToExport =
            selectedRowIds.value.length > 0
                ? currentSource.filter(k => selectedRowIds.value.includes(k.id))
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
            'Terakhir Diperbarui',
        ];

        const isExcel = format === 'excel';
        const delimiter = isExcel ? '\t' : ',';

        const rows = dataToExport.map((k, index) => {
            const ayamBroiler = k.ayam_broiler ?? k.ayam_potong ?? 0;
            const susuLiter = k.susu_liter ?? k.susu_sapi ?? 0;
            const total =
                (k.sapi_potong || 0) +
                (k.sapi_perah || 0) +
                (k.kambing || 0) +
                (k.domba || 0) +
                ayamBroiler;

            return [
                index + 1,
                escapeSpreadsheetCell(k.kode_kec, isExcel),
                escapeSpreadsheetCell(k.nama, isExcel),
                escapeSpreadsheetCell(k.zona.toUpperCase(), isExcel),
                k.sapi_potong || 0,
                k.sapi_perah || 0,
                k.kambing || 0,
                k.domba || 0,
                ayamBroiler,
                total,
                susuLiter,
                k.daging_sapi || 0,
                k.pmk || 0,
                k.lsd || 0,
                k.poskeswan || 0,
                k.lat || 0,
                k.lng || 0,
                escapeSpreadsheetCell(k.last_updated || '2026-10-15', isExcel),
            ].join(delimiter);
        });

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
            status: 'SUCCESS',
        });

        showToast(`Berhasil mengekspor ${dataToExport.length} baris data ke berkas ${filename}`);
    }

    // ----------------------------------------------------
    // FORM CREATE / EDIT KECAMATAN STATE
    // ----------------------------------------------------
    const isEditing = ref(false);
    const formKecamatanId = ref('');
    const formData = ref<AdminFormData>({
        nama: '',
        kode_kec: '',
        zona: 'aman',
        kategori_utama: 'Sapi Potong',
        sapi_potong: 1000,
        sapi_perah: 150,
        kambing: 1200,
        kambing_perah: 0,
        domba: 300,
        ayam_broiler: 5000,
        ayam_petelur: 0,
        susu_liter: 450,
        daging_sapi: 6000,
        pmk: 0,
        lsd: 0,
        poskeswan: 1,
        luas_hmt_ha: 0,
        catatan: 'Data telah diverifikasi oleh Petugas Medik Veteriner Dinas Pertanian Banyumas.',
    });

    function startCreateNew(): void {
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
            kambing_perah: 0,
            domba: 100,
            ayam_broiler: 2000,
            ayam_petelur: 0,
            susu_liter: 0,
            daging_sapi: 2500,
            pmk: 0,
            lsd: 0,
            poskeswan: 0,
            luas_hmt_ha: 0,
            catatan: 'Pencatatan data survei lapangan periode Triwulan II 2026.',
        };
        selectTab('create');
    }

    function startEditKecamatan(kec: KecamatanData): void {
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
            kambing_perah: kec.kambing_perah ?? 0,
            domba: kec.domba,
            ayam_broiler: kec.ayam_broiler ?? kec.ayam_potong ?? 0,
            ayam_petelur: kec.ayam_petelur ?? 0,
            susu_liter: kec.susu_liter ?? kec.susu_sapi ?? 0,
            daging_sapi: kec.daging_sapi,
            pmk: kec.pmk,
            lsd: kec.lsd,
            poskeswan: kec.poskeswan,
            luas_hmt_ha: kec.luas_hmt_ha ?? 0,
            catatan: `Pemutakhiran berkala data statistik Kecamatan ${kec.nama}.`,
        };
        selectTab('create');
    }

    function handleSaveForm(createAnother: boolean = false): void {
        if (!formData.value.nama.trim()) {
            showToast('Nama Kecamatan wajib diisi!');
            return;
        }

        const existingIndex = kecamatanList.value.findIndex(k => k.id === formKecamatanId.value);
        const existingRecord = existingIndex !== -1 ? kecamatanList.value[existingIndex] : undefined;

        if (isEditing.value && existingRecord && existingIndex !== -1) {
            kecamatanList.value[existingIndex] = {
                ...existingRecord,
                nama: formData.value.nama,
                kode_kec: formData.value.kode_kec,
                zona: formData.value.zona,
                sapi_potong: Number(formData.value.sapi_potong),
                sapi_perah: Number(formData.value.sapi_perah),
                kambing: Number(formData.value.kambing),
                kambing_perah: Number(formData.value.kambing_perah),
                domba: Number(formData.value.domba),
                ayam_potong: Number(formData.value.ayam_broiler),
                ayam_broiler: Number(formData.value.ayam_broiler),
                ayam_petelur: Number(formData.value.ayam_petelur),
                susu_sapi: Number(formData.value.susu_liter),
                susu_liter: Number(formData.value.susu_liter),
                daging_sapi: Number(formData.value.daging_sapi),
                pmk: Number(formData.value.pmk),
                lsd: Number(formData.value.lsd),
                poskeswan: Number(formData.value.poskeswan),
                luas_hmt_ha: Number(formData.value.luas_hmt_ha),
                last_updated: 'Baru saja',
            };
            auditLogs.value.unshift({
                id: Date.now(),
                user: 'Satrio (Super Admin)',
                action: 'UPDATE',
                table: 'populasi_ternak',
                detail: `Pembaruan data Kecamatan ${formData.value.nama}`,
                time: 'Baru saja',
                status: 'SUCCESS',
            });
            showToast(`Data ${formData.value.nama} berhasil diperbarui.`);
        } else {
            const newId = formData.value.nama.toLowerCase().trim().replace(/\s+/g, '_');
            const broilerCount = Number(formData.value.ayam_broiler);
            const susuCount = Number(formData.value.susu_liter);
            kecamatanList.value.push({
                id: newId,
                nama: formData.value.nama,
                kode_kec: formData.value.kode_kec,
                zona: formData.value.zona,
                ayam_potong: broilerCount,
                ayam_petelur: Number(formData.value.ayam_petelur),
                kambing: Number(formData.value.kambing),
                kambing_perah: Number(formData.value.kambing_perah),
                domba: Number(formData.value.domba),
                sapi_perah: Number(formData.value.sapi_perah),
                sapi_potong: Number(formData.value.sapi_potong),
                ayam_broiler: broilerCount,
                telur_ayam: 0,
                daging_ayam: 0,
                daging_sapi: Number(formData.value.daging_sapi),
                daging_kambing: 0,
                daging_domba: 0,
                susu_sapi: susuCount,
                susu_kambing: 0,
                susu_domba: 0,
                susu_liter: susuCount,
                pmk: Number(formData.value.pmk),
                lsd: Number(formData.value.lsd),
                poskeswan: Number(formData.value.poskeswan),
                luas_hmt_ha: Number(formData.value.luas_hmt_ha),
                lat: -7.5134,
                lng: 109.1762,
                last_updated: 'Baru saja',
            });
            auditLogs.value.unshift({
                id: Date.now(),
                user: 'Satrio (Super Admin)',
                action: 'INSERT',
                table: 'populasi_ternak',
                detail: `Penambahan kecamatan baru ${formData.value.nama}`,
                time: 'Baru saja',
                status: 'SUCCESS',
            });
            showToast(`Kecamatan ${formData.value.nama} berhasil ditambahkan.`);
        }

        if (createAnother) {
            startCreateNew();
        } else {
            selectTab('data');
        }
    }

    function deleteKecamatan(kec: KecamatanData): void {
        if (confirm(`Hapus data Kecamatan ${kec.nama}? Aksi ini akan dicatat ke audit log.`)) {
            kecamatanList.value = kecamatanList.value.filter(k => k.id !== kec.id);
            selectedRowIds.value = selectedRowIds.value.filter(id => id !== kec.id);
            auditLogs.value.unshift({
                id: Date.now(),
                user: 'Satrio (Super Admin)',
                action: 'DELETE',
                table: 'populasi_ternak',
                detail: `Menghapus data Kecamatan ${kec.nama}`,
                time: 'Baru saja',
                status: 'WARNING',
            });
            showToast(`Kecamatan ${kec.nama} telah dihapus.`);
        }
    }

    // ----------------------------------------------------
    // DATA FASILITAS PETERNAKAN RESMI BANYUMAS (PRD FR-MAP-05)
    // 6 RPH, 3 Pasar Hewan, 2 UPT Puskeswan
    // ----------------------------------------------------
    const rphList = ref<RphFacility[]>([
        { id: 1, nama: 'RPH Sokaraja', kecamatan: 'Sokaraja Kidul', pengelola: 'Pemkab Banyumas', kapasitas: 20, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.4562, lng: 109.2645, tipe: 'RPH' },
        { id: 2, nama: 'RPH Ajibarang', kecamatan: 'Pancasan', pengelola: 'Pemkab Banyumas', kapasitas: 15, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.4125, lng: 109.0732, tipe: 'RPH' },
        { id: 3, nama: 'RPH Wangon', kecamatan: 'Jl. Raya Wangon-Ajibarang', pengelola: 'UPTD Diskanak', kapasitas: 15, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.5187, lng: 109.0521, tipe: 'RPH' },
        { id: 4, nama: 'RPH Cilongok', kecamatan: 'Pernasidi', pengelola: 'Pemkab Banyumas', kapasitas: 20, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.4150, lng: 109.1350, tipe: 'RPH' },
        { id: 5, nama: 'RPH Sumpiuh', kecamatan: 'Jl. Stasiun Kradenan', pengelola: 'Pemkab Banyumas', kapasitas: 15, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.6124, lng: 109.3582, tipe: 'RPH' },
        { id: 6, nama: 'RPH Purwokerto', kecamatan: 'Purwokerto Barat', pengelola: 'Pemkab Banyumas', kapasitas: 30, jenis: 'Sapi & Kerbau', status: 'Aktif Operasional', lat: -7.4225, lng: 109.2215, tipe: 'RPH' },
        { id: 7, nama: 'Pasar Hewan Ajibarang', kecamatan: 'Ajibarang Kulon', pengelola: 'Pemkab Banyumas', kapasitas: 1500, jenis: 'Sapi, Kambing, Domba', status: 'Aktif Operasional', lat: -7.4112, lng: 109.0768, tipe: 'Pasar Hewan', hari_pasaran: 'Pon, Wage' },
        { id: 8, nama: 'Pasar Hewan Sokaraja', kecamatan: 'Sokaraja Kidul', pengelola: 'Pemkab Banyumas', kapasitas: 1000, jenis: 'Sapi, Kambing, Unggas', status: 'Aktif Operasional', lat: -7.4589, lng: 109.2612, tipe: 'Pasar Hewan', hari_pasaran: 'Pon, Kliwon' },
        { id: 9, nama: 'Pasar Hewan Cilongok', kecamatan: 'Pernasidi', pengelola: 'Pemkab Banyumas', kapasitas: 800, jenis: 'Kambing & Domba', status: 'Aktif Operasional', lat: -7.4089, lng: 109.1362, tipe: 'Pasar Hewan', hari_pasaran: 'Legi / Manis' },
        { id: 10, nama: 'UPT Puskeswan Banyumas', kecamatan: 'Kejawar', pengelola: 'Dinas Perikanan dan Peternakan', kapasitas: 50, jenis: 'Pelayanan Kesehatan Hewan', status: 'Aktif Operasional', lat: -7.5142, lng: 109.2985, tipe: 'Puskeswan', wilayah_binaan: 'Banyumas, Somagede, Kalibagor, Patikraja, Tambak, Sumpiuh, Kemranjen' },
        { id: 11, nama: 'UPT Puskeswan Cilongok', kecamatan: 'Pernasidi', pengelola: 'Dinas Perikanan dan Peternakan', kapasitas: 50, jenis: 'Pelayanan Kesehatan Hewan', status: 'Aktif Operasional', lat: -7.4110, lng: 109.1330, tipe: 'Puskeswan', wilayah_binaan: 'Cilongok, Ajibarang, Gumelar, Pekuncen, Wangon, Lumbir, Jatilawang' },
    ]);

    function toggleRphStatus(id: number): void {
        rphList.value = rphList.value.map(rph => {
            if (rph.id !== id) return rph;
            const nextStatus: RphFacility['status'] =
                rph.status === 'Aktif Operasional'
                    ? 'Pemeliharaan Terjadwal'
                    : 'Aktif Operasional';
            showToast(`Status ${rph.nama} diubah menjadi ${nextStatus}.`);
            return { ...rph, status: nextStatus };
        });
    }

    // ----------------------------------------------------
    // PARAMETER ZONASI EWS (PRD FR-SYS-02 & FR-ZON-01)
    // ----------------------------------------------------
    const ewsParams = ref<EwsParameters>({
        jendelaHari: 14,
        ambangKuningMaks: 5,
        ambangMerahMin: 6,
    });

    function recalculateZonasi(): void {
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
        auditLogs.value.unshift({
            id: Date.now(),
            user: 'Satrio (Super Admin)',
            action: 'CALCULATE',
            table: 'zona_snapshot_kecamatan',
            detail: `Rekalkulasi EWS (Ambang Merah >= ${ewsParams.value.ambangMerahMin} kasus)`,
            time: 'Baru saja',
            status: 'SUCCESS',
        });
        showToast('Rekalkulasi zonasi EWS selesai diterapkan.');
    }

    function broadcastEwsAlert(): void {
        const bahayaCount = kecamatanList.value.filter(k => k.zona === 'bahaya').length;
        auditLogs.value.unshift({
            id: Date.now(),
            user: 'Satrio (Super Admin)',
            action: 'BROADCAST',
            table: 'notifikasi_ews',
            detail: `Siaran peringatan dini biosekuriti ke ${bahayaCount} kecamatan Zona Merah`,
            time: 'Baru saja',
            status: 'SUCCESS',
        });
        showToast(`Peringatan dini EWS dikirim ke petugas Puskeswan di ${bahayaCount} kecamatan Zona Merah.`);
    }

    // ----------------------------------------------------
    // PROFIL & PENGATURAN AKUN
    // ----------------------------------------------------
    const profileSettings = ref<AdminProfileSettings>({
        nama: 'Satrio',
        nip: '19850412 201001 1 008',
        role: 'ROLE: SUPER_ADMIN',
        email: 'admin.diskanak@banyumaskab.go.id',
        unitKerja: 'Bidang Peternakan & Keswan',
        instansi: 'Dinas Pertanian dan Ketahanan Pangan',
        statusAkun: 'Aktif (2FA Terlindungi)',
        notifEwsEmail: true,
        notifWhatsappDarurat: true,
        autoSyncGeoportal: true,
    });

    function saveProfileSettings(): void {
        showToast('Pengaturan profil dan preferensi notifikasi EWS berhasil disimpan.');
    }

    // ----------------------------------------------------
    // LOGOUT & NAVIGASI EKSTERNAL
    // ----------------------------------------------------
    function handleLogout(): void {
        if (typeof window !== 'undefined') {
            localStorage.removeItem('ternakverse_role');
            localStorage.removeItem('ternakverse_name');
        }
        router.post('/internal/logout');
    }

    function goToPublicPortal(): void {
        if (typeof window !== 'undefined') {
            localStorage.setItem('ternakverse_role', 'admin');
            localStorage.setItem('ternakverse_name', 'Satrio Wibowo');
        }
        router.visit('/?preview=1');
    }

    function goToDinasMap(): void {
        if (typeof window !== 'undefined') {
            localStorage.setItem('ternakverse_role', 'admin');
            localStorage.setItem('ternakverse_name', 'Satrio Wibowo');
        }
        router.visit('/internal/dinas');
    }

    return {
        // Navigation & Shell
        currentTab,
        activeTab: currentTab,
        isMobileMenuOpen,
        isSidebarCollapsed,
        selectTab,

        // Analytical Context
        selectedTahun,
        selectedTriwulan,
        selectedMetric,
        activeKategori,
        selectedKecamatanId,
        mapEngine,

        // Data & Logs
        kecamatanList,
        setKecamatanSource,
        auditLogs,
        toastMsg,
        showToast,

        // Dashboard Search & Import
        searchQueryDashboard,
        isImportModalOpen,
        handleDashboardSearch,
        handleImportSuccess,

        // Data Table & Filters
        searchQuery,
        tableSearch: searchQuery,
        selectedCategoryFilter,
        selectedZonaFilter,
        selectedWabahFilter,
        selectedPoskeswanFilter,
        minPopulasiFilter,
        activeFilters,
        isFilterDropdownOpen,
        isExportDropdownOpen,
        isFilterModalOpen,
        isExportModalOpen,
        selectedRowIds,
        rowsPerPage,
        currentPage,
        filteredKecamatan,
        paginatedKecamatan,
        totalPages,
        hasActiveFilters,
        activeFilterCount,
        toggleSelectAll,
        toggleSelectRow,
        resetFilters,
        handleExport,

        // Create / Edit Form
        isEditing,
        formKecamatanId,
        formData,
        startCreateNew,
        startEditKecamatan,
        handleSaveForm,
        deleteKecamatan,

        // RPH & EWS
        rphList,
        toggleRphStatus,
        ewsParams,
        recalculateZonasi,
        broadcastEwsAlert,

        // Profile & Navigation
        profileSettings,
        saveProfileSettings,
        handleLogout,
        goToPublicPortal,
        goToDinasMap,
    };
}
