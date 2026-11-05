<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
    Database,
    Layers,
    Building2,
    Sprout,
    Plus,
    Search,
    Edit2,
    Trash2,
    Check,
    X,
    RefreshCw,
    AlertTriangle,
    MapPin,
    Phone,
    User,
    Calendar,
    Filter,
    Shield,
    TrendingUp,
    Activity,
    Scale,
    ShieldAlert,
    Package,
    Milk,
    Info,
} from 'lucide-vue-next';

// -----------------------------------------------------------------------------
// TIPE DATA & INTERFACES
// -----------------------------------------------------------------------------
export interface KomoditasItem {
    id: number;
    kode: string;
    nama: string;
    kelompok: 'Ternak Besar' | 'Ternak Kecil' | 'Unggas' | string;
    aktif: boolean;
}

export interface FasilitasItem {
    id: number;
    kecamatan_id: number;
    nama_rph: string;
    alamat_lengkap: string;
    tipe_fasilitas: 'RPH' | 'Puskeswan' | 'Pasar Hewan' | string;
    status_operasional: string;
    lat: number;
    lng: number;
    latitude?: number;
    longitude?: number;
    hari_pasaran?: string | null;
    wilayah_binaan?: string | null;
    penanggung_jawab?: string | null;
    nomor_kontak?: string | null;
    jenis_pengelola?: string | null;
    jenis_hewan_potong?: string | null;
    kapasitas_potong_ekor_hari?: number | null;
    kecamatan?: {
        id: number;
        nama_kecamatan: string;
        kode_kecamatan?: string;
    } | null;
}

export interface KecamatanHmtItem {
    id: number;
    kode_kecamatan: string;
    nama_kecamatan: string;
    luas_hmt_ha: number | string;
}

export interface DiseaseParameterZonasi {
    id?: number;
    penyakit_id?: number;
    jendela_hari_aktif?: number;
    ambang_kuning_maks?: number;
}

export interface DiseaseItem {
    id: number;
    kode: string;
    nama: string;
    nama_latin?: string | null;
    tingkat_bahaya: 'Rendah' | 'Sedang' | 'Tinggi' | 'Sangat Tinggi' | string;
    fcode?: string | null;
    hewan_rentan?: string;
    parameter_zonasi?: DiseaseParameterZonasi | null;
    parameterZonasi?: DiseaseParameterZonasi | null;
}

export interface ProductionCategoryItem {
    id: number;
    kode?: string;
    nama: string;
    satuan: string;
    deskripsi?: string;
}

export interface MasterDataResponse {
    status: string;
    komoditas: KomoditasItem[];
    fasilitas: FasilitasItem[];
    kecamatan: KecamatanHmtItem[];
}

// -----------------------------------------------------------------------------
// DEFAULT / FALLBACK DATA FOR DISEASES & PRODUCTION
// -----------------------------------------------------------------------------
const defaultDiseases: DiseaseItem[] = [
    {
        id: 1,
        kode: 'PMK',
        nama: 'Penyakit Mulut dan Kuku',
        nama_latin: 'Aphtae Epizooticae',
        hewan_rentan: 'Sapi Potong, Sapi Perah, Kerbau, Kambing, Babi',
        tingkat_bahaya: 'Sangat Tinggi',
        parameter_zonasi: { jendela_hari_aktif: 14, ambang_kuning_maks: 5 },
    },
    {
        id: 2,
        kode: 'LSD',
        nama: 'Lumpy Skin Disease',
        nama_latin: 'Capripoxvirus bovis',
        hewan_rentan: 'Sapi Potong, Sapi Perah, Kerbau',
        tingkat_bahaya: 'Tinggi',
        parameter_zonasi: { jendela_hari_aktif: 28, ambang_kuning_maks: 4 },
    },
    {
        id: 3,
        kode: 'ANTX',
        nama: 'Antraks (Radang Limpa)',
        nama_latin: 'Bacillus anthracis',
        hewan_rentan: 'Sapi, Kerbau, Kambing, Domba, Kuda (Zoonosis)',
        tingkat_bahaya: 'Sangat Tinggi',
        parameter_zonasi: { jendela_hari_aktif: 7, ambang_kuning_maks: 1 },
    },
    {
        id: 4,
        kode: 'SE',
        nama: 'Septicaemia Epizootica (Ngorok)',
        nama_latin: 'Pasteurella multocida',
        hewan_rentan: 'Kerbau, Sapi Potong, Sapi Perah',
        tingkat_bahaya: 'Tinggi',
        parameter_zonasi: { jendela_hari_aktif: 3, ambang_kuning_maks: 3 },
    },
    {
        id: 5,
        kode: 'AI',
        nama: 'Avian Influenza (Flu Burung)',
        nama_latin: 'HPAI H5N1 / H5N6',
        hewan_rentan: 'Ayam Broiler, Ayam Layer, Itik, Unggas Air (Zoonosis)',
        tingkat_bahaya: 'Sangat Tinggi',
        parameter_zonasi: { jendela_hari_aktif: 7, ambang_kuning_maks: 10 },
    },
    {
        id: 6,
        kode: 'BRUC',
        nama: 'Brucellosis (Keluron Menular)',
        nama_latin: 'Brucella abortus',
        hewan_rentan: 'Sapi Perah, Sapi Potong (Zoonosis)',
        tingkat_bahaya: 'Sedang',
        parameter_zonasi: { jendela_hari_aktif: 30, ambang_kuning_maks: 2 },
    },
    {
        id: 7,
        kode: 'CCPP',
        nama: 'Contagious Caprine Pleuropneumonia',
        nama_latin: 'Mycoplasma capricolum',
        hewan_rentan: 'Kambing, Domba',
        tingkat_bahaya: 'Sedang',
        parameter_zonasi: { jendela_hari_aktif: 10, ambang_kuning_maks: 3 },
    },
    {
        id: 8,
        kode: 'SCAB',
        nama: 'Scabies (Kudis Ternak)',
        nama_latin: 'Sarcoptes scabiei',
        hewan_rentan: 'Kambing, Domba, Kelinci, Sapi',
        tingkat_bahaya: 'Rendah',
        parameter_zonasi: { jendela_hari_aktif: 14, ambang_kuning_maks: 8 },
    },
    {
        id: 9,
        kode: 'HELM',
        nama: 'Helminthiasis (Cacingan)',
        nama_latin: 'Fasciola gigantica / Haemonchus',
        hewan_rentan: 'Sapi Potong, Kambing, Domba',
        tingkat_bahaya: 'Rendah',
        parameter_zonasi: { jendela_hari_aktif: 21, ambang_kuning_maks: 15 },
    },
];

const defaultProductionCategories: ProductionCategoryItem[] = [
    { id: 1, kode: 'PROD-DGG', nama: 'Daging', satuan: 'kg', deskripsi: 'Produksi karkas dan daging segar ternak potong (sapi, ayam, kambing, domba)' },
    { id: 2, kode: 'PROD-SSU', nama: 'Susu', satuan: 'liter', deskripsi: 'Produksi susu murni perah dari peternakan sapi dan kambing' },
    { id: 3, kode: 'PROD-TLR', nama: 'Telur', satuan: 'butir', deskripsi: 'Produksi telur konsumsi dari peternakan ayam petelur dan itik' },
    { id: 4, kode: 'PROD-MDU', nama: 'Madu', satuan: 'liter', deskripsi: 'Hasil komoditas budidaya lebah madu klanceng dan apis cerana' },
    { id: 5, kode: 'PROD-WOL', nama: 'Wol', satuan: 'kg', deskripsi: 'Produksi serat bulu wol domba batur / garut' },
    { id: 6, kode: 'PROD-KLT', nama: 'Kulit', satuan: 'lembar', deskripsi: 'Produksi kulit samak mentah ternak besar dan kecil' },
];

// -----------------------------------------------------------------------------
// PROPS & EMITS
// -----------------------------------------------------------------------------
const emit = defineEmits<{
    (e: 'showToast', message: string): void;
}>();

// -----------------------------------------------------------------------------
// STATE MASTER DATA & NAVIGASI SUB-TAB
// -----------------------------------------------------------------------------
type MasterSubTab = 'komoditas' | 'fasilitas' | 'hmt' | 'penyakit' | 'produksi';
const activeTab = ref<MasterSubTab>('komoditas');

const isLoading = ref<boolean>(false);
const fetchError = ref<string | null>(null);

const commodities = ref<KomoditasItem[]>([]);
const facilities = ref<FasilitasItem[]>([]);
const kecamatanList = ref<KecamatanHmtItem[]>([]);
const diseases = ref<DiseaseItem[]>([...defaultDiseases]);
const productionCategories = ref<ProductionCategoryItem[]>([...defaultProductionCategories]);

// -----------------------------------------------------------------------------
// SEARCH & FILTER STATE
// -----------------------------------------------------------------------------
const searchKomoditas = ref<string>('');
const filterKelompokKomoditas = ref<string>('all');

const searchFasilitas = ref<string>('');
const filterTipeFasilitas = ref<string>('all');

const searchHmt = ref<string>('');

const searchDisease = ref<string>('');
const filterTingkatBahaya = ref<string>('all');

const searchProduction = ref<string>('');

// -----------------------------------------------------------------------------
// MODAL STATE - KOMODITAS
// -----------------------------------------------------------------------------
const isModalKomoditasOpen = ref<boolean>(false);
const isEditingKomoditas = ref<boolean>(false);
const komoditasForm = ref<{
    id?: number;
    kode: string;
    nama: string;
    kelompok: string;
    aktif: boolean;
}>({
    kode: '',
    nama: '',
    kelompok: 'Ternak Besar',
    aktif: true,
});

const isDeleteKomoditasModalOpen = ref<boolean>(false);
const komoditasToDelete = ref<KomoditasItem | null>(null);

// -----------------------------------------------------------------------------
// MODAL STATE - FASILITAS
// -----------------------------------------------------------------------------
const isModalFasilitasOpen = ref<boolean>(false);
const isEditingFasilitas = ref<boolean>(false);
const fasilitasForm = ref<{
    id?: number;
    kecamatan_id: number | null;
    nama_rph: string;
    alamat_lengkap: string;
    tipe_fasilitas: string;
    status_operasional: string;
    lat: number | string;
    lng: number | string;
    hari_pasaran: string;
    wilayah_binaan: string;
    penanggung_jawab: string;
    nomor_kontak: string;
}>({
    kecamatan_id: null,
    nama_rph: '',
    alamat_lengkap: '',
    tipe_fasilitas: 'RPH',
    status_operasional: 'Aktif Operasional',
    lat: -7.4243,
    lng: 109.2302,
    hari_pasaran: '',
    wilayah_binaan: '',
    penanggung_jawab: '',
    nomor_kontak: '',
});

const isDeleteFasilitasModalOpen = ref<boolean>(false);
const fasilitasToDelete = ref<FasilitasItem | null>(null);

// -----------------------------------------------------------------------------
// MODAL STATE - LAHAN HMT
// -----------------------------------------------------------------------------
const isModalHmtOpen = ref<boolean>(false);
const hmtForm = ref<{
    id: number;
    nama_kecamatan: string;
    kode_kecamatan: string;
    luas_hmt_ha: number | string;
}>({
    id: 0,
    nama_kecamatan: '',
    kode_kecamatan: '',
    luas_hmt_ha: 0,
});

const isSaving = ref<boolean>(false);

// -----------------------------------------------------------------------------
// MODAL STATE - PENYAKIT & EWS
// -----------------------------------------------------------------------------
const isModalDiseaseOpen = ref<boolean>(false);
const isEditingDisease = ref<boolean>(false);
const diseaseForm = ref<{
    id?: number;
    kode: string;
    nama: string;
    nama_latin: string;
    hewan_rentan: string;
    tingkat_bahaya: string;
    masa_inkubasi_hari: number | string;
    ambang_kuning: number | string;
    ambang_merah: number | string;
    deskripsi: string;
}>({
    kode: '',
    nama: '',
    nama_latin: '',
    hewan_rentan: '',
    tingkat_bahaya: 'Tinggi',
    masa_inkubasi_hari: 14,
    ambang_kuning: 5,
    ambang_merah: 5,
    deskripsi: '',
});

const isDeleteDiseaseModalOpen = ref<boolean>(false);
const diseaseToDelete = ref<DiseaseItem | null>(null);

// -----------------------------------------------------------------------------
// MODAL STATE - KATEGORI PRODUKSI
// -----------------------------------------------------------------------------
const isModalProductionOpen = ref<boolean>(false);
const isEditingProduction = ref<boolean>(false);
const productionForm = ref<{
    id?: number;
    kode: string;
    nama: string;
    satuan: string;
    deskripsi: string;
}>({
    kode: '',
    nama: '',
    satuan: 'kg',
    deskripsi: '',
});

const isDeleteProductionModalOpen = ref<boolean>(false);
const productionToDelete = ref<ProductionCategoryItem | null>(null);

// -----------------------------------------------------------------------------
// CSRF & HTTP CLIENT HELPER
// -----------------------------------------------------------------------------
function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (cookieMatch && cookieMatch[1]) return decodeURIComponent(cookieMatch[1]);
    return '';
}

function notify(message: string): void {
    emit('showToast', message);
}

// -----------------------------------------------------------------------------
// FETCH LIVE MASTER DATA
// -----------------------------------------------------------------------------
async function fetchDiseases(): Promise<void> {
    try {
        const response = await fetch('/admin/api/diseases', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const res = await response.json();
            if (Array.isArray(res.data) && res.data.length > 0) {
                diseases.value = res.data.map((d: any) => {
                    const fallback = defaultDiseases.find(fd => fd.kode === d.kode || fd.id === d.id);
                    return {
                        id: d.id,
                        kode: d.kode,
                        nama: d.nama,
                        nama_latin: d.nama_latin || fallback?.nama_latin,
                        tingkat_bahaya: d.tingkat_bahaya || fallback?.tingkat_bahaya || 'Tinggi',
                        fcode: d.fcode,
                        hewan_rentan: fallback?.hewan_rentan || 'Sapi, Kerbau, Kambing',
                        parameter_zonasi: d.parameter_zonasi || d.parameterZonasi || fallback?.parameter_zonasi,
                    };
                });
            }
        }
    } catch {
        // Default diseases maintained
    }
}

async function fetchProductionCategories(): Promise<void> {
    try {
        const response = await fetch('/admin/api/production-categories', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const res = await response.json();
            if (Array.isArray(res.data) && res.data.length > 0) {
                productionCategories.value = res.data.map((p: any) => {
                    const fallback = defaultProductionCategories.find(
                        fp => fp.nama.toLowerCase() === p.nama.toLowerCase() || fp.id === p.id
                    );
                    return {
                        id: p.id,
                        kode: p.kode || fallback?.kode || `PROD-00${p.id}`,
                        nama: p.nama,
                        satuan: p.satuan,
                        deskripsi: fallback?.deskripsi || 'Kategori komoditas produksi peternakan',
                    };
                });
            }
        }
    } catch {
        // Default production categories maintained
    }
}

async function fetchMasterData(): Promise<void> {
    isLoading.value = true;
    fetchError.value = null;

    try {
        const [masterRes] = await Promise.all([
            fetch('/admin/api/master-data', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }),
            fetchDiseases(),
            fetchProductionCategories(),
        ]);

        if (!masterRes.ok) {
            throw new Error(`Gagal memuat master data (HTTP ${masterRes.status})`);
        }

        const data: MasterDataResponse = await masterRes.json();
        commodities.value = data.komoditas || [];
        facilities.value = data.fasilitas || [];
        kecamatanList.value = data.kecamatan || [];
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Terjadi kegagalan saat mengambil master data.';
        fetchError.value = message;
        notify(message);
    } finally {
        isLoading.value = false;
    }
}

// -----------------------------------------------------------------------------
// COMPUTED FILTERED LISTS
// -----------------------------------------------------------------------------
const filteredCommodities = computed<KomoditasItem[]>(() => {
    let list = commodities.value;
    const query = searchKomoditas.value.trim().toLowerCase();

    if (filterKelompokKomoditas.value !== 'all') {
        list = list.filter((item) => item.kelompok === filterKelompokKomoditas.value);
    }

    if (query) {
        list = list.filter((item) =>
            item.nama.toLowerCase().includes(query) ||
            item.kode.toLowerCase().includes(query) ||
            item.kelompok.toLowerCase().includes(query)
        );
    }

    return list;
});

const filteredFacilities = computed<FasilitasItem[]>(() => {
    let list = facilities.value;
    const query = searchFasilitas.value.trim().toLowerCase();

    if (filterTipeFasilitas.value !== 'all') {
        list = list.filter((item) => item.tipe_fasilitas === filterTipeFasilitas.value);
    }

    if (query) {
        list = list.filter((item) => {
            const kecName = item.kecamatan?.nama_kecamatan?.toLowerCase() || '';
            const nama = item.nama_rph?.toLowerCase() || '';
            const alamat = item.alamat_lengkap?.toLowerCase() || '';
            const kontak = item.nomor_kontak?.toLowerCase() || '';
            const pj = item.penanggung_jawab?.toLowerCase() || '';
            return (
                nama.includes(query) ||
                alamat.includes(query) ||
                kecName.includes(query) ||
                kontak.includes(query) ||
                pj.includes(query)
            );
        });
    }

    return list;
});

const filteredKecamatanHmt = computed<KecamatanHmtItem[]>(() => {
    let list = kecamatanList.value;
    const query = searchHmt.value.trim().toLowerCase();

    if (query) {
        list = list.filter((item) =>
            item.nama_kecamatan.toLowerCase().includes(query) ||
            item.kode_kecamatan.toLowerCase().includes(query)
        );
    }

    return list;
});

const filteredDiseases = computed<DiseaseItem[]>(() => {
    let list = diseases.value;
    const query = searchDisease.value.trim().toLowerCase();

    if (filterTingkatBahaya.value !== 'all') {
        list = list.filter((item) => item.tingkat_bahaya === filterTingkatBahaya.value);
    }

    if (query) {
        list = list.filter((item) =>
            item.nama.toLowerCase().includes(query) ||
            item.kode.toLowerCase().includes(query) ||
            (item.nama_latin && item.nama_latin.toLowerCase().includes(query)) ||
            (item.hewan_rentan && item.hewan_rentan.toLowerCase().includes(query))
        );
    }

    return list;
});

const filteredProductionCategories = computed<ProductionCategoryItem[]>(() => {
    let list = productionCategories.value;
    const query = searchProduction.value.trim().toLowerCase();

    if (query) {
        list = list.filter((item) =>
            item.nama.toLowerCase().includes(query) ||
            item.satuan.toLowerCase().includes(query) ||
            (item.kode && item.kode.toLowerCase().includes(query)) ||
            (item.deskripsi && item.deskripsi.toLowerCase().includes(query))
        );
    }

    return list;
});

// Ringkasan Statistik
const totalLuasHmtKabupaten = computed<number>(() => {
    return kecamatanList.value.reduce((acc, k) => acc + (Number(k.luas_hmt_ha) || 0), 0);
});

const totalKapasitasTampungSt = computed<number>(() => {
    return totalLuasHmtKabupaten.value * 6; // Standard 6 ST / ha
});

// -----------------------------------------------------------------------------
// CRUD - KOMODITAS
// -----------------------------------------------------------------------------
function openCreateKomoditasModal(): void {
    isEditingKomoditas.value = false;
    komoditasForm.value = {
        kode: '',
        nama: '',
        kelompok: 'Ternak Besar',
        aktif: true,
    };
    isModalKomoditasOpen.value = true;
}

function openEditKomoditasModal(item: KomoditasItem): void {
    isEditingKomoditas.value = true;
    komoditasForm.value = {
        id: item.id,
        kode: item.kode,
        nama: item.nama,
        kelompok: item.kelompok,
        aktif: Boolean(item.aktif),
    };
    isModalKomoditasOpen.value = true;
}

async function saveKomoditas(): Promise<void> {
    if (!komoditasForm.value.nama.trim()) {
        notify('Nama komoditas wajib diisi.');
        return;
    }

    if (!isEditingKomoditas.value && !komoditasForm.value.kode.trim()) {
        notify('Kode komoditas wajib diisi.');
        return;
    }

    isSaving.value = true;
    try {
        const url = isEditingKomoditas.value
            ? `/admin/api/commodities/${komoditasForm.value.id}`
            : '/admin/api/commodities';
        const method = isEditingKomoditas.value ? 'PUT' : 'POST';

        const payload: Record<string, unknown> = {
            nama: komoditasForm.value.nama.trim(),
            kelompok: komoditasForm.value.kelompok,
            aktif: komoditasForm.value.aktif,
        };

        if (!isEditingKomoditas.value) {
            payload.kode = komoditasForm.value.kode.trim().toUpperCase();
        }

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menyimpan komoditas.');
        }

        isModalKomoditasOpen.value = false;
        notify(resData.message || 'Komoditas berhasil disimpan.');
        await fetchMasterData();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menyimpan data komoditas.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

function openDeleteKomoditasModal(item: KomoditasItem): void {
    komoditasToDelete.value = item;
    isDeleteKomoditasModalOpen.value = true;
}

async function executeDeleteKomoditas(): Promise<void> {
    if (!komoditasToDelete.value) return;

    isSaving.value = true;
    try {
        const response = await fetch(`/admin/api/commodities/${komoditasToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menghapus komoditas.');
        }

        isDeleteKomoditasModalOpen.value = false;
        komoditasToDelete.value = null;
        notify(resData.message || 'Komoditas berhasil dihapus.');
        await fetchMasterData();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menghapus komoditas.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

// -----------------------------------------------------------------------------
// CRUD - FASILITAS
// -----------------------------------------------------------------------------
function openCreateFasilitasModal(): void {
    isEditingFasilitas.value = false;
    const defaultKecId = kecamatanList.value.length > 0 ? kecamatanList.value[0]?.id ?? null : null;
    fasilitasForm.value = {
        kecamatan_id: defaultKecId,
        nama_rph: '',
        alamat_lengkap: '',
        tipe_fasilitas: 'RPH',
        status_operasional: 'Aktif Operasional',
        lat: -7.4243,
        lng: 109.2302,
        hari_pasaran: '',
        wilayah_binaan: '',
        penanggung_jawab: '',
        nomor_kontak: '',
    };
    isModalFasilitasOpen.value = true;
}

function openEditFasilitasModal(item: FasilitasItem): void {
    isEditingFasilitas.value = true;
    fasilitasForm.value = {
        id: item.id,
        kecamatan_id: item.kecamatan_id,
        nama_rph: item.nama_rph,
        alamat_lengkap: item.alamat_lengkap,
        tipe_fasilitas: item.tipe_fasilitas,
        status_operasional: item.status_operasional,
        lat: item.lat ?? item.latitude ?? -7.4243,
        lng: item.lng ?? item.longitude ?? 109.2302,
        hari_pasaran: item.hari_pasaran || '',
        wilayah_binaan: item.wilayah_binaan || '',
        penanggung_jawab: item.penanggung_jawab || '',
        nomor_kontak: item.nomor_kontak || '',
    };
    isModalFasilitasOpen.value = true;
}

async function saveFasilitas(): Promise<void> {
    if (!fasilitasForm.value.nama_rph.trim()) {
        notify('Nama fasilitas wajib diisi.');
        return;
    }
    if (!fasilitasForm.value.kecamatan_id) {
        notify('Pilih kecamatan untuk fasilitas ini.');
        return;
    }
    if (!fasilitasForm.value.alamat_lengkap.trim()) {
        notify('Alamat lengkap wajib diisi.');
        return;
    }

    const rawLat = String(fasilitasForm.value.lat ?? '').trim();
    const rawLng = String(fasilitasForm.value.lng ?? '').trim();
    if (!rawLat || !rawLng) {
        notify('Gagal: Koordinat latitude dan longitude wajib diisi');
        return;
    }
    const latNum = Number(rawLat);
    const lngNum = Number(rawLng);
    if (isNaN(latNum) || isNaN(lngNum) || (latNum === 0 && lngNum === 0)) {
        notify('Gagal: Format koordinat angka tidak valid');
        return;
    }

    isSaving.value = true;
    try {
        const url = isEditingFasilitas.value
            ? `/admin/api/facilities/${fasilitasForm.value.id}`
            : '/admin/api/facilities';
        const method = isEditingFasilitas.value ? 'PUT' : 'POST';

        const payload = {
            kecamatan_id: fasilitasForm.value.kecamatan_id,
            nama_rph: fasilitasForm.value.nama_rph.trim(),
            alamat_lengkap: fasilitasForm.value.alamat_lengkap.trim(),
            tipe_fasilitas: fasilitasForm.value.tipe_fasilitas,
            status_operasional: fasilitasForm.value.status_operasional,
            lat: latNum,
            lng: lngNum,
            hari_pasaran: fasilitasForm.value.hari_pasaran.trim() || null,
            wilayah_binaan: fasilitasForm.value.wilayah_binaan.trim() || null,
            penanggung_jawab: fasilitasForm.value.penanggung_jawab.trim() || null,
            nomor_kontak: fasilitasForm.value.nomor_kontak.trim() || null,
        };

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menyimpan fasilitas.');
        }

        isModalFasilitasOpen.value = false;
        notify(resData.message || 'Fasilitas berhasil disimpan.');
        await fetchMasterData();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menyimpan data fasilitas.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

function openDeleteFasilitasModal(item: FasilitasItem): void {
    fasilitasToDelete.value = item;
    isDeleteFasilitasModalOpen.value = true;
}

async function executeDeleteFasilitas(): Promise<void> {
    if (!fasilitasToDelete.value) return;

    isSaving.value = true;
    try {
        const response = await fetch(`/admin/api/facilities/${fasilitasToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menghapus fasilitas.');
        }

        isDeleteFasilitasModalOpen.value = false;
        fasilitasToDelete.value = null;
        notify(resData.message || 'Fasilitas berhasil dihapus.');
        await fetchMasterData();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menghapus fasilitas.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

// -----------------------------------------------------------------------------
// CRUD - LAHAN HMT PER KECAMATAN
// -----------------------------------------------------------------------------
function openEditHmtModal(item: KecamatanHmtItem): void {
    hmtForm.value = {
        id: item.id,
        nama_kecamatan: item.nama_kecamatan,
        kode_kecamatan: item.kode_kecamatan,
        luas_hmt_ha: item.luas_hmt_ha,
    };
    isModalHmtOpen.value = true;
}

async function saveKecamatanHmt(): Promise<void> {
    const luas = Number(hmtForm.value.luas_hmt_ha);
    if (isNaN(luas) || luas < 0) {
        notify('Luas lahan HMT harus berupa angka positif.');
        return;
    }

    isSaving.value = true;
    try {
        const response = await fetch(`/admin/api/kecamatan/${hmtForm.value.id}/hmt`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ luas_hmt_ha: luas }),
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal memperbarui luas lahan HMT.');
        }

        isModalHmtOpen.value = false;
        notify(resData.message || 'Luas lahan HMT berhasil diperbarui.');
        await fetchMasterData();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal memperbarui luas lahan HMT.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

// -----------------------------------------------------------------------------
// CRUD - MASTER PENYAKIT & EWS
// -----------------------------------------------------------------------------
function openCreateDiseaseModal(): void {
    isEditingDisease.value = false;
    diseaseForm.value = {
        kode: '',
        nama: '',
        nama_latin: '',
        hewan_rentan: '',
        tingkat_bahaya: 'Tinggi',
        masa_inkubasi_hari: 14,
        ambang_kuning: 5,
        ambang_merah: 5,
        deskripsi: '',
    };
    isModalDiseaseOpen.value = true;
}

function openEditDiseaseModal(item: DiseaseItem): void {
    isEditingDisease.value = true;
    const pz = item.parameter_zonasi || item.parameterZonasi;
    diseaseForm.value = {
        id: item.id,
        kode: item.kode,
        nama: item.nama,
        nama_latin: item.nama_latin || '',
        hewan_rentan: item.hewan_rentan || '',
        tingkat_bahaya: item.tingkat_bahaya || 'Tinggi',
        masa_inkubasi_hari: pz?.jendela_hari_aktif ?? 14,
        ambang_kuning: pz?.ambang_kuning_maks ?? 5,
        ambang_merah: pz?.ambang_kuning_maks ?? 5,
        deskripsi: '',
    };
    isModalDiseaseOpen.value = true;
}

async function saveDisease(): Promise<void> {
    if (!diseaseForm.value.nama.trim()) {
        notify('Nama penyakit wajib diisi.');
        return;
    }

    if (!isEditingDisease.value && !diseaseForm.value.kode.trim()) {
        notify('Kode penyakit wajib diisi.');
        return;
    }

    isSaving.value = true;
    try {
        const url = isEditingDisease.value
            ? `/admin/api/diseases/${diseaseForm.value.id}`
            : '/admin/api/diseases';
        const method = isEditingDisease.value ? 'PUT' : 'POST';

        const payload: Record<string, unknown> = {
            nama: diseaseForm.value.nama.trim(),
            nama_latin: diseaseForm.value.nama_latin.trim() || null,
            tingkat_bahaya: diseaseForm.value.tingkat_bahaya,
            ambang_kuning_maks: Number(diseaseForm.value.ambang_kuning) || 5,
            jendela_hari_aktif: Number(diseaseForm.value.masa_inkubasi_hari) || 14,
        };

        if (!isEditingDisease.value) {
            payload.kode = diseaseForm.value.kode.trim().toUpperCase();
            payload.fcode = 'CA0010';
        }

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menyimpan data penyakit.');
        }

        isModalDiseaseOpen.value = false;
        notify(resData.message || 'Data penyakit berhasil disimpan.');
        await fetchDiseases();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menyimpan data penyakit.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

function openDeleteDiseaseModal(item: DiseaseItem): void {
    diseaseToDelete.value = item;
    isDeleteDiseaseModalOpen.value = true;
}

async function executeDeleteDisease(): Promise<void> {
    if (!diseaseToDelete.value) return;

    isSaving.value = true;
    try {
        const response = await fetch(`/admin/api/diseases/${diseaseToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menghapus data penyakit.');
        }

        isDeleteDiseaseModalOpen.value = false;
        diseaseToDelete.value = null;
        notify(resData.message || 'Data penyakit berhasil dihapus.');
        await fetchDiseases();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menghapus data penyakit.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

// -----------------------------------------------------------------------------
// CRUD - MASTER KATEGORI PRODUKSI
// -----------------------------------------------------------------------------
function openCreateProductionCategoryModal(): void {
    isEditingProduction.value = false;
    productionForm.value = {
        kode: `PROD-00${productionCategories.value.length + 1}`,
        nama: '',
        satuan: 'kg',
        deskripsi: '',
    };
    isModalProductionOpen.value = true;
}

function openEditProductionCategoryModal(item: ProductionCategoryItem): void {
    isEditingProduction.value = true;
    productionForm.value = {
        id: item.id,
        kode: item.kode || `PROD-00${item.id}`,
        nama: item.nama,
        satuan: item.satuan,
        deskripsi: item.deskripsi || '',
    };
    isModalProductionOpen.value = true;
}

async function saveProductionCategory(): Promise<void> {
    if (!productionForm.value.nama.trim()) {
        notify('Nama kategori produksi wajib diisi.');
        return;
    }

    if (!productionForm.value.satuan.trim()) {
        notify('Satuan standar wajib diisi.');
        return;
    }

    isSaving.value = true;
    try {
        const url = isEditingProduction.value
            ? `/admin/api/production-categories/${productionForm.value.id}`
            : '/admin/api/production-categories';
        const method = isEditingProduction.value ? 'PUT' : 'POST';

        const payload: Record<string, unknown> = {
            nama: productionForm.value.nama.trim(),
            satuan: productionForm.value.satuan.trim(),
        };

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menyimpan kategori produksi.');
        }

        isModalProductionOpen.value = false;
        notify(resData.message || 'Kategori produksi berhasil disimpan.');
        await fetchProductionCategories();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menyimpan kategori produksi.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

function openDeleteProductionCategoryModal(item: ProductionCategoryItem): void {
    productionToDelete.value = item;
    isDeleteProductionModalOpen.value = true;
}

async function executeDeleteProductionCategory(): Promise<void> {
    if (!productionToDelete.value) return;

    isSaving.value = true;
    try {
        const response = await fetch(`/admin/api/production-categories/${productionToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const resData = await response.json();
        if (!response.ok) {
            throw new Error(resData?.message || 'Gagal menghapus kategori produksi.');
        }

        isDeleteProductionModalOpen.value = false;
        productionToDelete.value = null;
        notify(resData.message || 'Kategori produksi berhasil dihapus.');
        await fetchProductionCategories();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Gagal menghapus kategori produksi.';
        notify(message);
    } finally {
        isSaving.value = false;
    }
}

function getDiseaseSeverityBadgeClass(tingkat: string): string {
    switch (tingkat) {
        case 'Sangat Tinggi':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        case 'Tinggi':
            return 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20';
        case 'Sedang':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'Rendah':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        default:
            return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20';
    }
}

// -----------------------------------------------------------------------------
// HELPER BADGE STYLES
// -----------------------------------------------------------------------------
function getFacilityBadgeClass(tipe: string): string {
    switch (tipe) {
        case 'RPH':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        case 'Puskeswan':
            return 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20';
        case 'Pasar Hewan':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        default:
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    }
}

function getCommodityGroupBadgeClass(kelompok: string): string {
    switch (kelompok) {
        case 'Ternak Besar':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
        case 'Ternak Kecil':
            return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20';
        case 'Unggas':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        default:
            return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20';
    }
}

function getHmtCapacityBadge(luasHa: number): { label: string; class: string } {
    if (luasHa >= 120) {
        return {
            label: 'Surplus Hijauan (Prima)',
            class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
        };
    } else if (luasHa >= 80) {
        return {
            label: 'Cukup / Waspada (Sedang)',
            class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
        };
    } else {
        return {
            label: 'Kritis / Terbatas (Defisit)',
            class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
        };
    }
}

// -----------------------------------------------------------------------------
// LIFECYCLE
// -----------------------------------------------------------------------------
onMounted(() => {
    fetchMasterData();
});
</script>

<template>
    <div class="w-full space-y-6 select-none font-ui">
        <!-- =================================================================== -->
        <!-- HEADER UTAMA & STATS STRIP                                          -->
        <!-- =================================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-center text-[#00D084] shadow-xs shrink-0">
                        <Database class="w-5 h-5 text-[#00D084]" />
                    </div>
                    <div>
                        <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                            Pusat Manajemen Master Data Peternakan
                        </h1>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Konfigurasi dinamis komoditas, fasilitas peternakan (RPH, Puskeswan, Pasar Hewan), dan alokasi luas lahan HMT 27 Kecamatan Banyumas.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Refresh Data -->
            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="fetchMasterData"
                    :disabled="isLoading"
                    class="h-9 px-3.5 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] dark:bg-[#181818] bg-white hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2 cursor-pointer transition-colors shadow-xs disabled:opacity-50"
                    title="Muat ulang seluruh data master dari server"
                >
                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
                    <span>{{ isLoading ? 'Memuat...' : 'Segarkan Data' }}</span>
                </button>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- KPI METRICS STRIP                                                   -->
        <!-- =================================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                    <span>Total Komoditas</span>
                    <Layers class="w-4 h-4 text-blue-500" />
                </div>
                <div class="font-display font-extrabold text-2xl text-[#171B17] dark:text-[#F5F5F5]">
                    {{ commodities.length }}
                    <span class="text-xs font-normal text-[#737B71] dark:text-[#858585]">jenis</span>
                </div>
                <div class="text-[11px] text-[#737B71] dark:text-[#858585]">
                    Aktif: <span class="font-semibold text-emerald-600 dark:text-[#00D084]">{{ commodities.filter(c => c.aktif).length }}</span> komoditas
                </div>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                    <span>Fasilitas Pelayanan</span>
                    <Building2 class="w-4 h-4 text-purple-500" />
                </div>
                <div class="font-display font-extrabold text-2xl text-[#171B17] dark:text-[#F5F5F5]">
                    {{ facilities.length }}
                    <span class="text-xs font-normal text-[#737B71] dark:text-[#858585]">unit</span>
                </div>
                <div class="text-[11px] text-[#737B71] dark:text-[#858585] truncate">
                    RPH ({{ facilities.filter(f => f.tipe_fasilitas === 'RPH').length }}), Puskeswan ({{ facilities.filter(f => f.tipe_fasilitas === 'Puskeswan').length }}), Pasar ({{ facilities.filter(f => f.tipe_fasilitas === 'Pasar Hewan').length }})
                </div>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                    <span>Lahan Hijauan (HMT)</span>
                    <Sprout class="w-4 h-4 text-emerald-500" />
                </div>
                <div class="font-display font-extrabold text-2xl text-emerald-600 dark:text-[#00D084]">
                    {{ totalLuasHmtKabupaten.toLocaleString('id-ID', { maximumFractionDigits: 1 }) }}
                    <span class="text-xs font-normal text-[#737B71] dark:text-[#858585]">ha</span>
                </div>
                <div class="text-[11px] text-[#737B71] dark:text-[#858585]">
                    Tersebar di 27 Kecamatan
                </div>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                    <span>Master Penyakit &amp; EWS</span>
                    <Activity class="w-4 h-4 text-rose-500" />
                </div>
                <div class="font-display font-extrabold text-2xl text-[#171B17] dark:text-[#F5F5F5]">
                    {{ diseases.length }}
                    <span class="text-xs font-normal text-[#737B71] dark:text-[#858585]">PHMS</span>
                </div>
                <div class="text-[11px] text-[#737B71] dark:text-[#858585]">
                    Bahaya Tinggi: <span class="font-semibold text-rose-600 dark:text-rose-400">{{ diseases.filter(d => d.tingkat_bahaya === 'Sangat Tinggi' || d.tingkat_bahaya === 'Tinggi').length }}</span> jenis
                </div>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-1">
                <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                    <span>Kategori Produksi</span>
                    <Scale class="w-4 h-4 text-cyan-500" />
                </div>
                <div class="font-display font-extrabold text-2xl text-[#171B17] dark:text-[#F5F5F5]">
                    {{ productionCategories.length }}
                    <span class="text-xs font-normal text-[#737B71] dark:text-[#858585]">kategori</span>
                </div>
                <div class="text-[11px] text-[#737B71] dark:text-[#858585]">
                    Komoditas resmi Banyumas
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SUB-TAB SWITCHER                                                    -->
        <!-- =================================================================== -->
        <div class="flex items-center flex-wrap gap-2 p-1.5 rounded-xl bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] w-fit">
            <button
                type="button"
                @click="activeTab = 'komoditas'"
                class="px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                :class="
                    activeTab === 'komoditas'
                        ? 'bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] shadow-xs border border-[#E0E4DD] dark:border-[#1E1E1E]'
                        : 'text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5]'
                "
            >
                <Layers class="w-4 h-4" :class="activeTab === 'komoditas' ? 'text-blue-500' : ''" />
                <span>Master Komoditas</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-mono">
                    {{ commodities.length }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'fasilitas'"
                class="px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                :class="
                    activeTab === 'fasilitas'
                        ? 'bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] shadow-xs border border-[#E0E4DD] dark:border-[#1E1E1E]'
                        : 'text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5]'
                "
            >
                <Building2 class="w-4 h-4" :class="activeTab === 'fasilitas' ? 'text-purple-500' : ''" />
                <span>Fasilitas Peternakan</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 font-mono">
                    {{ facilities.length }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'hmt'"
                class="px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                :class="
                    activeTab === 'hmt'
                        ? 'bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] shadow-xs border border-[#E0E4DD] dark:border-[#1E1E1E]'
                        : 'text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5]'
                "
            >
                <Sprout class="w-4 h-4" :class="activeTab === 'hmt' ? 'text-emerald-500' : ''" />
                <span>Lahan Pakan HMT</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-mono">
                    {{ kecamatanList.length }} Kec
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'penyakit'"
                class="px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                :class="
                    activeTab === 'penyakit'
                        ? 'bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] shadow-xs border border-[#E0E4DD] dark:border-[#1E1E1E]'
                        : 'text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5]'
                "
            >
                <Activity class="w-4 h-4" :class="activeTab === 'penyakit' ? 'text-rose-500' : ''" />
                <span>Master Penyakit &amp; EWS</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 font-mono">
                    {{ diseases.length }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'produksi'"
                class="px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                :class="
                    activeTab === 'produksi'
                        ? 'bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] shadow-xs border border-[#E0E4DD] dark:border-[#1E1E1E]'
                        : 'text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5]'
                "
            >
                <Scale class="w-4 h-4" :class="activeTab === 'produksi' ? 'text-cyan-500' : ''" />
                <span>Master Produksi</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 font-mono">
                    {{ productionCategories.length }}
                </span>
            </button>
        </div>

        <!-- =================================================================== -->
        <!-- ALERT ERROR JIKA FETCH GAGAL                                        -->
        <!-- =================================================================== -->
        <div
            v-if="fetchError"
            class="p-4 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 flex items-center justify-between gap-4"
        >
            <div class="flex items-center gap-3">
                <AlertTriangle class="w-5 h-5 shrink-0" />
                <span class="text-xs font-medium">{{ fetchError }}</span>
            </div>
            <button
                type="button"
                @click="fetchMasterData"
                class="px-3 py-1 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700 cursor-pointer transition-colors"
            >
                Coba Lagi
            </button>
        </div>

        <!-- =================================================================== -->
        <!-- SEKSI A: MASTER KOMODITAS                                           -->
        <!-- =================================================================== -->
        <div v-if="activeTab === 'komoditas'" class="space-y-4">
            <!-- Action bar & Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <div class="relative min-w-[240px] flex-1 max-w-md">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#737B71] dark:text-[#858585]" />
                        <input
                            v-model="searchKomoditas"
                            type="text"
                            placeholder="Cari nama komoditas atau kode..."
                            class="w-full h-9 pl-9 pr-3 rounded-lg text-xs bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <Filter class="w-3.5 h-3.5 text-[#737B71] dark:text-[#858585]" />
                        <select
                            v-model="filterKelompokKomoditas"
                            class="h-9 px-2.5 rounded-lg text-xs bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                        >
                            <option value="all">Semua Kelompok</option>
                            <option value="Ternak Besar">Ternak Besar</option>
                            <option value="Ternak Kecil">Ternak Kecil</option>
                            <option value="Unggas">Unggas</option>
                        </select>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openCreateKomoditasModal"
                    class="h-9 px-3.5 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] active:bg-[#00A86B] text-[#062117] text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-colors shadow-xs shrink-0"
                >
                    <Plus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Komoditas Baru</span>
                </button>
            </div>

            <!-- Tabel Komoditas -->
            <div class="rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#111111] overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] font-semibold uppercase tracking-wider border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Kode Komoditas</th>
                                <th class="py-3 px-4">Nama Komoditas</th>
                                <th class="py-3 px-4">Kelompok Ternak</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E]">
                            <tr
                                v-for="(item, idx) in filteredCommodities"
                                :key="item.id"
                                class="hover:bg-[#EDEFEB] dark:hover:bg-[#1A201A] transition-colors"
                            >
                                <td class="py-3 px-4 text-center font-mono text-[#737B71] dark:text-[#858585]">
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]">
                                    <span class="px-2 py-0.5 rounded-md bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                                        {{ item.kode }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-[#171B17] dark:text-[#F5F5F5]">
                                    {{ item.nama }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full font-medium text-[11px] border"
                                        :class="getCommodityGroupBadgeClass(item.kelompok)"
                                    >
                                        {{ item.kelompok }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="item.aktif
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                                            : 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20'"
                                    >
                                        {{ item.aktif ? 'AKTIF' : 'NONAKTIF' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditKomoditasModal(item)"
                                            class="w-7 h-7 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] hover:bg-[#EDEFEB] dark:hover:bg-[#202820] text-[#171B17] dark:text-[#F5F5F5] flex items-center justify-center cursor-pointer transition-colors"
                                            title="Ubah data komoditas"
                                            aria-label="Ubah data komoditas"
                                        >
                                            <Edit2 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteKomoditasModal(item)"
                                            class="w-7 h-7 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center cursor-pointer transition-colors"
                                            title="Hapus / Nonaktifkan komoditas"
                                            aria-label="Hapus atau nonaktifkan komoditas"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredCommodities.length === 0">
                                <td colspan="6" class="py-8 text-center text-[#737B71] dark:text-[#858585]">
                                    Tidak ada data komoditas yang sesuai filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SEKSI B: MASTER FASILITAS PETERNAKAN                                -->
        <!-- =================================================================== -->
        <div v-if="activeTab === 'fasilitas'" class="space-y-4">
            <!-- Action bar & Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <div class="relative min-w-[240px] flex-1 max-w-md">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#737B71] dark:text-[#858585]" />
                        <input
                            v-model="searchFasilitas"
                            type="text"
                            placeholder="Cari nama fasilitas, kecamatan, alamat, kontak..."
                            class="w-full h-9 pl-9 pr-3 rounded-lg text-xs bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <Filter class="w-3.5 h-3.5 text-[#737B71] dark:text-[#858585]" />
                        <select
                            v-model="filterTipeFasilitas"
                            class="h-9 px-2.5 rounded-lg text-xs bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                        >
                            <option value="all">Semua Tipe Fasilitas</option>
                            <option value="RPH">Rumah Potong Hewan (RPH)</option>
                            <option value="Puskeswan">Pusat Kesehatan Hewan (Puskeswan)</option>
                            <option value="Pasar Hewan">Pasar Hewan</option>
                        </select>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openCreateFasilitasModal"
                    class="h-9 px-3.5 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] active:bg-[#00A86B] text-[#062117] text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-colors shadow-xs shrink-0"
                >
                    <Plus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Fasilitas Baru</span>
                </button>
            </div>

            <!-- Tabel Fasilitas -->
            <div class="rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#111111] overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] font-semibold uppercase tracking-wider border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Nama &amp; Tipe Fasilitas</th>
                                <th class="py-3 px-4">Kecamatan &amp; Alamat</th>
                                <th class="py-3 px-4">Hari Pasaran / Binaan</th>
                                <th class="py-3 px-4">Kontak &amp; PJ</th>
                                <th class="py-3 px-4">Status &amp; GPS</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E]">
                            <tr
                                v-for="(item, idx) in filteredFacilities"
                                :key="item.id"
                                class="hover:bg-[#EDEFEB] dark:hover:bg-[#1A201A] transition-colors"
                            >
                                <td class="py-3 px-4 text-center font-mono text-[#737B71] dark:text-[#858585]">
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-[#171B17] dark:text-[#F5F5F5]">{{ item.nama_rph }}</div>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span
                                            class="px-2 py-0.5 rounded-full font-bold text-[10px] border"
                                            :class="getFacilityBadgeClass(item.tipe_fasilitas)"
                                        >
                                            {{ item.tipe_fasilitas }}
                                        </span>
                                        <span v-if="item.kapasitas_potong_ekor_hari" class="text-[11px] text-[#737B71] dark:text-[#858585]">
                                            Kap: {{ item.kapasitas_potong_ekor_hari }} ekor/hari
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <div class="font-semibold text-emerald-600 dark:text-[#00D084]">
                                        {{ item.kecamatan?.nama_kecamatan || 'Kecamatan Terpilih' }}
                                    </div>
                                    <div class="text-[11px] text-[#737B71] dark:text-[#858585] truncate mt-0.5" :title="item.alamat_lengkap">
                                        {{ item.alamat_lengkap }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div v-if="item.hari_pasaran" class="text-[#171B17] dark:text-[#F5F5F5]">
                                        <b class="text-[11px] text-[#737B71] dark:text-[#858585]">Pasaran:</b> {{ item.hari_pasaran }}
                                    </div>
                                    <div v-if="item.wilayah_binaan" class="text-[11px] text-[#737B71] dark:text-[#858585] mt-0.5">
                                        <b class="text-[#171B17] dark:text-[#F5F5F5]">Binaan:</b> {{ item.wilayah_binaan }}
                                    </div>
                                    <div v-if="!item.hari_pasaran && !item.wilayah_binaan" class="text-[11px] text-[#737B71] dark:text-[#858585] italic">
                                        -
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div v-if="item.penanggung_jawab" class="font-medium text-[#171B17] dark:text-[#F5F5F5]">
                                        {{ item.penanggung_jawab }}
                                    </div>
                                    <div v-if="item.nomor_kontak" class="text-[11px] text-teal-600 dark:text-teal-400 font-mono mt-0.5">
                                        {{ item.nomor_kontak }}
                                    </div>
                                    <div v-if="!item.penanggung_jawab && !item.nomor_kontak" class="text-[11px] text-[#737B71] dark:text-[#858585] italic">
                                        -
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                            :class="item.status_operasional.includes('Aktif')
                                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'"
                                        >
                                            {{ item.status_operasional }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] font-mono text-[#737B71] dark:text-[#858585] mt-1">
                                        {{ (item.lat ?? item.latitude)?.toFixed(4) }}, {{ (item.lng ?? item.longitude)?.toFixed(4) }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditFasilitasModal(item)"
                                            class="w-7 h-7 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] hover:bg-[#EDEFEB] dark:hover:bg-[#202820] text-[#171B17] dark:text-[#F5F5F5] flex items-center justify-center cursor-pointer transition-colors"
                                            title="Ubah data fasilitas"
                                            aria-label="Ubah data fasilitas"
                                        >
                                            <Edit2 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteFasilitasModal(item)"
                                            class="w-7 h-7 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center cursor-pointer transition-colors"
                                            title="Hapus fasilitas"
                                            aria-label="Hapus fasilitas"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredFacilities.length === 0">
                                <td colspan="7" class="py-8 text-center text-[#737B71] dark:text-[#858585]">
                                    Tidak ada data fasilitas yang sesuai filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SEKSI C: MASTER LAHAN PAKAN HMT PER KECAMATAN                       -->
        <!-- =================================================================== -->
        <div v-if="activeTab === 'hmt'" class="space-y-4">
            <!-- Search & Info Notice -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs">
                <div class="relative min-w-[240px] flex-1 max-w-md">
                    <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#737B71] dark:text-[#858585]" />
                    <input
                        v-model="searchHmt"
                        type="text"
                        placeholder="Cari kecamatan atau kode wilayah..."
                        class="w-full h-9 pl-9 pr-3 rounded-lg text-xs bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                    />
                </div>

                <div class="text-xs text-[#737B71] dark:text-[#858585] flex items-center gap-2">
                    <Shield class="w-4 h-4 text-[#00D084]" />
                    <span>Daya Dukung: <b>1 Hektar HMT = 6.0 Satuan Ternak (ST)</b> (Standar Kementan)</span>
                </div>
            </div>

            <!-- Tabel HMT 27 Kecamatan -->
            <div class="rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#111111] overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] font-semibold uppercase tracking-wider border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Kode Wilayah</th>
                                <th class="py-3 px-4">Nama Kecamatan</th>
                                <th class="py-3 px-4 text-right">Luas HMT (Hektar)</th>
                                <th class="py-3 px-4 text-right">Kapasitas Pakan (ST)</th>
                                <th class="py-3 px-4">Status Daya Dukung Hijauan</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E]">
                            <tr
                                v-for="(item, idx) in filteredKecamatanHmt"
                                :key="item.id"
                                class="hover:bg-[#EDEFEB] dark:hover:bg-[#1A201A] transition-colors"
                            >
                                <td class="py-3 px-4 text-center font-mono text-[#737B71] dark:text-[#858585]">
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-3 px-4 font-mono font-medium text-[#737B71] dark:text-[#858585]">
                                    {{ item.kode_kecamatan }}
                                </td>
                                <td class="py-3 px-4 font-bold text-[#171B17] dark:text-[#F5F5F5]">
                                    {{ item.nama_kecamatan }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-[#171B17] dark:text-[#F5F5F5]">
                                    {{ Number(item.luas_hmt_ha).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) }} ha
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-[#00D084]">
                                    {{ Math.round(Number(item.luas_hmt_ha) * 6).toLocaleString('id-ID') }} ST
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full font-semibold text-[11px] border"
                                        :class="getHmtCapacityBadge(Number(item.luas_hmt_ha)).class"
                                    >
                                        {{ getHmtCapacityBadge(Number(item.luas_hmt_ha)).label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button
                                        type="button"
                                        @click="openEditHmtModal(item)"
                                        class="h-7 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] hover:bg-[#EDEFEB] dark:hover:bg-[#202820] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer transition-colors"
                                    >
                                        <Edit2 class="w-3 h-3 text-[#00D084]" />
                                        <span>Ubah Luas HMT</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredKecamatanHmt.length === 0">
                                <td colspan="7" class="py-8 text-center text-[#737B71] dark:text-[#858585]">
                                    Tidak ada data kecamatan yang sesuai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SEKSI D: MASTER PENYAKIT & AMBANG BATAS EWS                        -->
        <!-- =================================================================== -->
        <div v-if="activeTab === 'penyakit'" class="space-y-4">
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display font-bold text-lg text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Activity class="w-5 h-5 text-rose-500" />
                            <span>Master Data Penyakit Hewan &amp; Ambang Batas EWS</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Konfigurasi Penyakit Hewan Menular Strategis (PHMS), masa inkubasi (jendela hari aktif), dan ambang batas peringatan dini zonasi risiko wilayah.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openCreateDiseaseModal"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold flex items-center gap-2 cursor-pointer transition-colors shadow-xs shrink-0"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Penyakit Baru</span>
                    </button>
                </div>

                <!-- Baris Filter & Pencarian -->
                <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                    <div class="relative flex-1 w-full">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#737B71] dark:text-[#858585]" />
                        <input
                            v-model="searchDisease"
                            type="text"
                            placeholder="Cari kode, nama penyakit, nama latin, atau hewan rentan..."
                            class="w-full h-9 pl-9 pr-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <Filter class="w-3.5 h-3.5 text-[#737B71] dark:text-[#858585] shrink-0" />
                        <select
                            v-model="filterTingkatBahaya"
                            class="h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                        >
                            <option value="all">Semua Tingkat Bahaya</option>
                            <option value="Sangat Tinggi">Sangat Tinggi</option>
                            <option value="Tinggi">Tinggi</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Rendah">Rendah</option>
                        </select>
                    </div>
                </div>

                <!-- Tabel Data Penyakit -->
                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] border-b border-[#E0E4DD] dark:border-[#1E1E1E] uppercase text-[10px] tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-4 py-3">Kode</th>
                                <th scope="col" class="px-4 py-3">Nama Penyakit &amp; Latin</th>
                                <th scope="col" class="px-4 py-3">Hewan Rentan</th>
                                <th scope="col" class="px-4 py-3 text-center">Masa Inkubasi</th>
                                <th scope="col" class="px-4 py-3 text-center">Ambang Status EWS</th>
                                <th scope="col" class="px-4 py-3 text-center">Tingkat Bahaya</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="d in filteredDiseases"
                                :key="d.id"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-xs text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    {{ d.kode }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-xs text-[#171B17] dark:text-[#F5F5F5]">
                                        {{ d.nama }}
                                    </div>
                                    <div v-if="d.nama_latin" class="text-[11px] italic text-[#737B71] dark:text-[#858585]">
                                        {{ d.nama_latin }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[#444C43] dark:text-[#B3B3B3] max-w-xs">
                                    {{ d.hewan_rentan || 'Sapi, Kerbau, Kambing' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="font-mono font-semibold">
                                        {{ (d.parameter_zonasi || d.parameterZonasi)?.jendela_hari_aktif ?? 14 }}
                                    </span>
                                    <span class="text-[10px] text-[#737B71] dark:text-[#858585] ml-1">Hari</span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 text-[11px]">
                                        <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono font-semibold border border-amber-500/20" title="Batas Kuning (Waspada)">
                                            &le; {{ (d.parameter_zonasi || d.parameterZonasi)?.ambang_kuning_maks ?? 5 }}
                                        </span>
                                        <span class="text-[#858585]">/</span>
                                        <span class="px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-600 dark:text-rose-400 font-mono font-semibold border border-rose-500/20" title="Batas Merah (Bahaya)">
                                            &gt; {{ (d.parameter_zonasi || d.parameterZonasi)?.ambang_kuning_maks ?? 5 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                        :class="getDiseaseSeverityBadgeClass(d.tingkat_bahaya)"
                                    >
                                        {{ d.tingkat_bahaya }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="openEditDiseaseModal(d)"
                                            class="h-7 px-2.5 rounded-md border border-[#E0E4DD] dark:border-[#242424] hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                            title="Ubah parameter penyakit & EWS"
                                        >
                                            <Edit2 class="w-3 h-3 text-[#00D084]" />
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteDiseaseModal(d)"
                                            class="h-7 px-2.5 rounded-md border border-rose-200 dark:border-rose-500/20 hover:bg-rose-50 dark:hover:bg-rose-500/10 text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                            title="Hapus penyakit"
                                        >
                                            <Trash2 class="w-3 h-3" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredDiseases.length === 0">
                                <td colspan="7" class="py-8 text-center text-[#737B71] dark:text-[#858585]">
                                    Tidak ada data penyakit yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SEKSI E: MASTER KATEGORI PRODUKSI                                  -->
        <!-- =================================================================== -->
        <div v-if="activeTab === 'produksi'" class="space-y-4">
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display font-bold text-lg text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Scale class="w-5 h-5 text-cyan-500" />
                            <span>Master Kategori Produksi Peternakan</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Klasifikasi kategori komoditas hasil peternakan (daging, susu, telur, madu, wol, kulit) beserta satuan standar pengukuran resmi Kabupaten Banyumas.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openCreateProductionCategoryModal"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold flex items-center gap-2 cursor-pointer transition-colors shadow-xs shrink-0"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Kategori Produksi</span>
                    </button>
                </div>

                <!-- Baris Filter & Pencarian -->
                <div class="flex items-center gap-3 pt-2">
                    <div class="relative flex-1 w-full max-w-md">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#737B71] dark:text-[#858585]" />
                        <input
                            v-model="searchProduction"
                            type="text"
                            placeholder="Cari kategori produksi atau satuan standar..."
                            class="w-full h-9 pl-9 pr-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>
                </div>

                <!-- Tabel Data Kategori Produksi -->
                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] border-b border-[#E0E4DD] dark:border-[#1E1E1E] uppercase text-[10px] tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-4 py-3">Kode / ID</th>
                                <th scope="col" class="px-4 py-3">Nama Kategori</th>
                                <th scope="col" class="px-4 py-3">Satuan Standar</th>
                                <th scope="col" class="px-4 py-3">Deskripsi Komoditas</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="p in filteredProductionCategories"
                                :key="p.id"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-xs text-cyan-600 dark:text-cyan-400 whitespace-nowrap">
                                    {{ p.kode || `PROD-00${p.id}` }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs text-[#171B17] dark:text-[#F5F5F5] whitespace-nowrap">
                                    {{ p.nama }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                        {{ p.satuan }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[#444C43] dark:text-[#B3B3B3] max-w-md">
                                    {{ p.deskripsi || 'Kategori komoditas hasil produksi peternakan Kabupaten Banyumas.' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="openEditProductionCategoryModal(p)"
                                            class="h-7 px-2.5 rounded-md border border-[#E0E4DD] dark:border-[#242424] hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                            title="Ubah kategori produksi"
                                        >
                                            <Edit2 class="w-3 h-3 text-[#00D084]" />
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteProductionCategoryModal(p)"
                                            class="h-7 px-2.5 rounded-md border border-rose-200 dark:border-rose-500/20 hover:bg-rose-50 dark:hover:bg-rose-500/10 text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1 cursor-pointer transition-colors"
                                            title="Hapus kategori produksi"
                                        >
                                            <Trash2 class="w-3 h-3" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredProductionCategories.length === 0">
                                <td colspan="5" class="py-8 text-center text-[#737B71] dark:text-[#858585]">
                                    Tidak ada kategori produksi yang cocok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL FORM: TAMBAH / UBAH KOMODITAS                                 -->
        <!-- =================================================================== -->
        <div
            v-if="isModalKomoditasOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center">
                            <Layers class="w-4 h-4" />
                        </div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            {{ isEditingKomoditas ? 'Ubah Master Komoditas' : 'Tambah Komoditas Baru' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="isModalKomoditasOpen = false"
                        class="w-7 h-7 rounded-lg text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5] flex items-center justify-center cursor-pointer"
                        aria-label="Tutup modal komoditas"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Kode Komoditas (Uppercase)
                        </label>
                        <input
                            v-model="komoditasForm.kode"
                            :disabled="isEditingKomoditas"
                            type="text"
                            placeholder="Contoh: SAPI_POTONG, KAMBING, BEBEK"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] uppercase font-mono placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084] disabled:opacity-60 disabled:cursor-not-allowed"
                        />
                        <p v-if="!isEditingKomoditas" class="text-[11px] text-[#737B71] dark:text-[#858585] mt-1">
                            Kode identifikasi unik untuk integrasi agregasi sensus populasi.
                        </p>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Nama Komoditas
                        </label>
                        <input
                            v-model="komoditasForm.nama"
                            type="text"
                            placeholder="Contoh: Sapi Potong Limousin / Brahman"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Kelompok Ternak
                        </label>
                        <select
                            v-model="komoditasForm.kelompok"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                        >
                            <option value="Ternak Besar">Ternak Besar (Sapi, Kerbau, Kuda)</option>
                            <option value="Ternak Kecil">Ternak Kecil (Kambing, Domba, Kelinci)</option>
                            <option value="Unggas">Unggas (Ayam Broiler, Layer, Bebek)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2.5 pt-2">
                        <input
                            id="komoditas-aktif-chk"
                            v-model="komoditasForm.aktif"
                            type="checkbox"
                            class="w-4 h-4 rounded text-[#00D084] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        />
                        <label for="komoditas-aktif-chk" class="font-semibold text-[#171B17] dark:text-[#F5F5F5] cursor-pointer">
                            Status Aktif dalam Sistem dan Form Pencatatan
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isModalKomoditasOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveKomoditas"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Menyimpan...' : 'Simpan Komoditas' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL KONFIRMASI HAPUS KOMODITAS                                    -->
        <!-- =================================================================== -->
        <div
            v-if="isDeleteKomoditasModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-[#141414] border border-rose-200 dark:border-rose-500/20 shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Hapus Komoditas?
                        </h3>
                        <p class="text-xs text-[#737B71] dark:text-[#858585]">
                            {{ komoditasToDelete?.nama }} ({{ komoditasToDelete?.kode }})
                        </p>
                    </div>
                </div>

                <p class="text-xs text-[#737B71] dark:text-[#858585] leading-relaxed">
                    Jika komoditas ini sudah memiliki riwayat sensus populasi atau kasus wabah, sistem akan secara otomatis menonaktifkan statusnya agar integritas data historis tetap terjaga.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="isDeleteKomoditasModalOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteKomoditas"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Memproses...' : 'Ya, Hapus/Nonaktifkan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL FORM: TAMBAH / UBAH FASILITAS                                 -->
        <!-- =================================================================== -->
        <div
            v-if="isModalFasilitasOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-lg my-8 rounded-2xl bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center">
                            <Building2 class="w-4 h-4" />
                        </div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            {{ isEditingFasilitas ? 'Ubah Fasilitas Peternakan' : 'Tambah Fasilitas Peternakan Baru' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="isModalFasilitasOpen = false"
                        class="w-7 h-7 rounded-lg text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5] flex items-center justify-center cursor-pointer"
                        aria-label="Tutup modal fasilitas"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5 text-xs max-h-[70vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Tipe Fasilitas *
                            </label>
                            <select
                                v-model="fasilitasForm.tipe_fasilitas"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                            >
                                <option value="RPH">RPH (Rumah Potong Hewan)</option>
                                <option value="Puskeswan">Puskeswan (Pusat Kesehatan Hewan)</option>
                                <option value="Pasar Hewan">Pasar Hewan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Kecamatan Wilayah *
                            </label>
                            <select
                                v-model="fasilitasForm.kecamatan_id"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                            >
                                <option :value="null" disabled>Pilih Kecamatan</option>
                                <option v-for="kec in kecamatanList" :key="kec.id" :value="kec.id">
                                    {{ kec.nama_kecamatan }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Nama Fasilitas *
                        </label>
                        <input
                            v-model="fasilitasForm.nama_rph"
                            type="text"
                            placeholder="Contoh: RPH Gandasuli, Puskeswan Ajibarang, Pasar Hewan Ajibarang"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Alamat Lengkap *
                        </label>
                        <textarea
                            v-model="fasilitasForm.alamat_lengkap"
                            rows="2"
                            placeholder="Alamat jalan, desa/kelurahan, dan patokan lokasi..."
                            class="w-full p-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Latitude (GPS Y) *
                            </label>
                            <input
                                v-model="fasilitasForm.lat"
                                type="number"
                                step="0.000001"
                                placeholder="-7.4243"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono focus:outline-none focus:border-[#00D084]"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Longitude (GPS X) *
                            </label>
                            <input
                                v-model="fasilitasForm.lng"
                                type="number"
                                step="0.000001"
                                placeholder="109.2302"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono focus:outline-none focus:border-[#00D084]"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Status Operasional
                            </label>
                            <select
                                v-model="fasilitasForm.status_operasional"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                            >
                                <option value="Aktif Operasional">Aktif Operasional</option>
                                <option value="Pemeliharaan Terjadwal">Pemeliharaan Terjadwal</option>
                                <option value="Non-Aktif Sementara">Non-Aktif Sementara</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Hari Pasaran (Khusus Pasar)
                            </label>
                            <input
                                v-model="fasilitasForm.hari_pasaran"
                                type="text"
                                placeholder="Contoh: Wage & Pon / Setiap Hari"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Penanggung Jawab
                            </label>
                            <input
                                v-model="fasilitasForm.penanggung_jawab"
                                type="text"
                                placeholder="Nama kepala / koordinator..."
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Nomor Kontak / WA
                            </label>
                            <input
                                v-model="fasilitasForm.nomor_kontak"
                                type="text"
                                placeholder="Contoh: 0812-3456-7890"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Wilayah Binaan (Khusus Puskeswan)
                        </label>
                        <input
                            v-model="fasilitasForm.wilayah_binaan"
                            type="text"
                            placeholder="Contoh: Kec. Ajibarang, Pekuncen, Gumelar"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isModalFasilitasOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveFasilitas"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Menyimpan...' : 'Simpan Fasilitas' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL KONFIRMASI HAPUS FASILITAS                                    -->
        <!-- =================================================================== -->
        <div
            v-if="isDeleteFasilitasModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-[#141414] border border-rose-200 dark:border-rose-500/20 shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Hapus Fasilitas?
                        </h3>
                        <p class="text-xs text-[#737B71] dark:text-[#858585]">
                            {{ fasilitasToDelete?.nama_rph }}
                        </p>
                    </div>
                </div>

                <p class="text-xs text-[#737B71] dark:text-[#858585] leading-relaxed">
                    Data fasilitas ini beserta titik koordinat geospasialnya akan dihapus permanen dari basis data sistem Ternakverse.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="isDeleteFasilitasModalOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteFasilitas"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Menghapus...' : 'Ya, Hapus Fasilitas' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL FORM: UBAH LUAS LAHAN HMT KECAMATAN                           -->
        <!-- =================================================================== -->
        <div
            v-if="isModalHmtOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                            <Sprout class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                Alokasi Luas HMT
                            </h3>
                            <p class="text-xs text-emerald-600 dark:text-[#00D084] font-semibold">
                                Kecamatan {{ hmtForm.nama_kecamatan }} ({{ hmtForm.kode_kecamatan }})
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isModalHmtOpen = false"
                        class="w-7 h-7 rounded-lg text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5] flex items-center justify-center cursor-pointer"
                        aria-label="Tutup modal alokasi HMT"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Luas Lahan HMT (Hektar)
                        </label>
                        <div class="relative">
                            <input
                                v-model="hmtForm.luas_hmt_ha"
                                type="number"
                                step="0.1"
                                min="0"
                                placeholder="100.0"
                                class="w-full h-10 px-3 pr-12 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono text-sm font-bold focus:outline-none focus:border-[#00D084]"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#737B71] dark:text-[#858585]">
                                Ha
                            </span>
                        </div>
                        <p class="text-[11px] text-[#737B71] dark:text-[#858585] mt-1">
                            Total luas kebun hijauan makanan ternak produktif (rumput gajah / odot / indigofera).
                        </p>
                    </div>

                    <!-- Kalkulator Daya Tampung Otomatis -->
                    <div class="p-3.5 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] space-y-1.5">
                        <div class="text-[11px] font-semibold text-[#737B71] dark:text-[#858585]">
                            Estimasi Daya Dukung Hijauan (Carrying Capacity):
                        </div>
                        <div class="font-display font-extrabold text-xl text-emerald-600 dark:text-[#00D084]">
                            {{ Math.round((Number(hmtForm.luas_hmt_ha) || 0) * 6).toLocaleString('id-ID') }}
                            <span class="text-xs font-normal text-[#171B17] dark:text-[#F5F5F5]">Satuan Ternak (ST)</span>
                        </div>
                        <div class="text-[10px] text-[#737B71] dark:text-[#858585]">
                            1 ST setara 1 ekor sapi dewasa (350-400 kg) atau 7 ekor kambing/domba dewasa.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isModalHmtOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveKecamatanHmt"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Menyimpan...' : 'Simpan Luas HMT' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL FORM: TAMBAH / UBAH PENYAKIT & EWS                            -->
        <!-- =================================================================== -->
        <div
            v-if="isModalDiseaseOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                            <Activity class="w-4 h-4" />
                        </div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            {{ isEditingDisease ? 'Ubah Data Penyakit & Ambang EWS' : 'Tambah Penyakit Baru' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="isModalDiseaseOpen = false"
                        class="w-7 h-7 rounded-lg text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5] flex items-center justify-center cursor-pointer"
                        aria-label="Tutup modal penyakit"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5 text-xs max-h-[70vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Kode Penyakit (Uppercase) *
                            </label>
                            <input
                                v-model="diseaseForm.kode"
                                :disabled="isEditingDisease"
                                type="text"
                                placeholder="Contoh: PMK, LSD, ANTX"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] uppercase font-mono placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084] disabled:opacity-60 disabled:cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Tingkat Bahaya / Prioritas *
                            </label>
                            <select
                                v-model="diseaseForm.tingkat_bahaya"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:border-[#00D084]"
                            >
                                <option value="Sangat Tinggi">Sangat Tinggi (Emergency / Wabah)</option>
                                <option value="Tinggi">Tinggi (Menular Cepat)</option>
                                <option value="Sedang">Sedang (Perlu Perawatan)</option>
                                <option value="Rendah">Rendah (Endemik Terkendali)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Nama Penyakit *
                        </label>
                        <input
                            v-model="diseaseForm.nama"
                            type="text"
                            placeholder="Contoh: Penyakit Mulut dan Kuku (PMK)"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Nama Latin / Ilmiah
                        </label>
                        <input
                            v-model="diseaseForm.nama_latin"
                            type="text"
                            placeholder="Contoh: Aphtae Epizooticae / Bacillus anthracis"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] italic placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Hewan Rentan Terinfeksi *
                        </label>
                        <input
                            v-model="diseaseForm.hewan_rentan"
                            type="text"
                            placeholder="Contoh: Sapi Potong, Sapi Perah, Kerbau, Kambing, Babi"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Masa Inkubasi / Jendela Aktif (Hari) *
                            </label>
                            <input
                                v-model="diseaseForm.masa_inkubasi_hari"
                                type="number"
                                min="1"
                                placeholder="14"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono focus:outline-none focus:border-[#00D084]"
                            />
                            <p class="text-[10px] text-[#737B71] dark:text-[#858585] mt-0.5">
                                Rentang hari rekaman kasus dianggap aktif dalam kalkulasi zonasi.
                            </p>
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                                Ambang Kasus Kuning (Waspada) *
                            </label>
                            <input
                                v-model="diseaseForm.ambang_kuning"
                                type="number"
                                min="1"
                                placeholder="5"
                                class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono focus:outline-none focus:border-[#00D084]"
                            />
                            <p class="text-[10px] text-[#737B71] dark:text-[#858585] mt-0.5">
                                Kasus 1 s/d ambang ini = Zona Kuning; Di atas ambang = Zona Merah.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Deskripsi / Gejala Klinis
                        </label>
                        <textarea
                            v-model="diseaseForm.deskripsi"
                            rows="2"
                            placeholder="Keterangan gejala klinis khas, penanganan pertama, atau rujukan vaksinasi..."
                            class="w-full p-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isModalDiseaseOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveDisease"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Menyimpan...' : 'Simpan Penyakit' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL KONFIRMASI HAPUS PENYAKIT                                     -->
        <!-- =================================================================== -->
        <div
            v-if="isDeleteDiseaseModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-[#141414] border border-rose-200 dark:border-rose-500/20 shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Hapus Data Penyakit?
                        </h3>
                        <p class="text-xs text-[#737B71] dark:text-[#858585]">
                            {{ diseaseToDelete?.nama }} ({{ diseaseToDelete?.kode }})
                        </p>
                    </div>
                </div>

                <p class="text-xs text-[#737B71] dark:text-[#858585] leading-relaxed">
                    Penyakit yang memiliki relasi kasus aktif atau snapshot historis zonasi tidak dapat dihapus dari basis data demi menjaga keabsahan pelaporan dinas.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="isDeleteDiseaseModalOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteDisease"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Memproses...' : 'Ya, Hapus Penyakit' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL FORM: TAMBAH / UBAH KATEGORI PRODUKSI                         -->
        <!-- =================================================================== -->
        <div
            v-if="isModalProductionOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-500 flex items-center justify-center">
                            <Scale class="w-4 h-4" />
                        </div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            {{ isEditingProduction ? 'Ubah Kategori Produksi' : 'Tambah Kategori Produksi Baru' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="isModalProductionOpen = false"
                        class="w-7 h-7 rounded-lg text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5] flex items-center justify-center cursor-pointer"
                        aria-label="Tutup modal produksi"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Nama Kategori Produksi *
                        </label>
                        <input
                            v-model="productionForm.nama"
                            type="text"
                            placeholder="Contoh: Daging, Susu, Telur, Madu"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Satuan Standar *
                        </label>
                        <input
                            v-model="productionForm.satuan"
                            type="text"
                            placeholder="Contoh: kg, liter, butir, lembar"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] font-mono placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1">
                            Deskripsi / Peruntukan
                        </label>
                        <textarea
                            v-model="productionForm.deskripsi"
                            rows="2"
                            placeholder="Keterangan komoditas dan cakupan pencatatan hasil ternak..."
                            class="w-full p-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#293129] text-[#171B17] dark:text-[#F5F5F5] placeholder-[#737B71] dark:placeholder-[#858585] focus:outline-none focus:border-[#00D084]"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isModalProductionOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveProductionCategory"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-[#00D084] hover:bg-[#00F0A0] text-[#062117] text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Menyimpan...' : 'Simpan Kategori' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL KONFIRMASI HAPUS KATEGORI PRODUKSI                            -->
        <!-- =================================================================== -->
        <div
            v-if="isDeleteProductionModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-[#141414] border border-rose-200 dark:border-rose-500/20 shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Hapus Kategori Produksi?
                        </h3>
                        <p class="text-xs text-[#737B71] dark:text-[#858585]">
                            {{ productionToDelete?.nama }} ({{ productionToDelete?.satuan }})
                        </p>
                    </div>
                </div>

                <p class="text-xs text-[#737B71] dark:text-[#858585] leading-relaxed">
                    Kategori produksi yang telah memiliki rekaman entri produksi ternak di database tidak dapat dihapus demi konsistensi laporan berkala.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="isDeleteProductionModalOpen = false"
                        class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteProductionCategory"
                        :disabled="isSaving"
                        class="h-9 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Memproses...' : 'Ya, Hapus Kategori' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
