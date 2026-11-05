<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
    ShieldCheck,
    Users,
    Plus,
    Check,
    X,
    Lock,
    Edit,
    Trash2,
    SlidersHorizontal,
    Search,
    RotateCcw,
    AlertTriangle,
    Info,
    LayoutDashboard,
    Layers,
    TrendingUp,
    ShieldAlert,
    Compass,
    Radio,
    FileText,
    History,
    Key,
    ExternalLink,
} from 'lucide-vue-next';

// -----------------------------------------------------------------------------
// TIPE DATA & INTERFACE RBAC
// -----------------------------------------------------------------------------
export interface RoleItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    is_active: boolean;
    users_count: number;
    permissions_count: number;
}

export interface PermissionItem {
    slug: string;
    name: string;
    module: string;
    description: string;
}

export interface ModuleDefinition {
    id: string;
    name: string;
    description: string;
    icon: typeof LayoutDashboard;
}

// -----------------------------------------------------------------------------
// 10 MODUL RESMI SESUAI PERMISSION SERVICE
// -----------------------------------------------------------------------------
const MODULE_DEFINITIONS: ModuleDefinition[] = [
    {
        id: 'Dashboard',
        name: 'Dashboard Eksekutif',
        description: 'Pemantauan visual metrik utama peternakan Banyumas dan drilldown analitik',
        icon: LayoutDashboard,
    },
    {
        id: 'Populasi',
        name: 'Populasi Ternak',
        description: 'Manajemen sensus populasi ternak per kecamatan, desa, dan kepemilikan',
        icon: Layers,
    },
    {
        id: 'Produksi',
        name: 'Produksi Komoditas',
        description: 'Pencatatan dan analisis hasil produksi daging sapi, susu segar, dan telur',
        icon: TrendingUp,
    },
    {
        id: 'Kasus Penyakit',
        name: 'Kasus Penyakit (Wabah)',
        description: 'Pelaporan surveilans dan penanganan kasus epidemiologi PMK & LSD',
        icon: ShieldAlert,
    },
    {
        id: 'WebGIS',
        name: 'Peta Geospasial WebGIS',
        description: 'Akses visualisasi spasial, choropleth sebaran ternak, dan fasilitas RPH',
        icon: Compass,
    },
    {
        id: 'Early Warning System',
        name: 'Early Warning System (EWS)',
        description: 'Peringatan dini status zonasi wilayah (Hijau/Kuning/Merah) & ambang batas',
        icon: Radio,
    },
    {
        id: 'Laporan',
        name: 'Ekspor Laporan & Standar Geospasial',
        description: 'Penerbitan rekapitulasi PDF, Excel, katalog KUGI BIG, dan metadata ISO 19115',
        icon: FileText,
    },
    {
        id: 'Audit Trail',
        name: 'Audit Trail & Forensik',
        description: 'Pemantauan riwayat log aktivitas pengguna, mutasi data, dan keamanan sistem',
        icon: History,
    },
    {
        id: 'Pengguna',
        name: 'Manajemen Pengguna',
        description: 'Pendaftaran akun, penugasan peran dinas, dan otorisasi petugas teknis',
        icon: Users,
    },
    {
        id: 'RBAC',
        name: 'RBAC & Hak Akses',
        description: 'Konfigurasi peran dinamis, master izin akses, dan proteksi kebijakan sistem',
        icon: Key,
    },
];

// -----------------------------------------------------------------------------
// KATALOG 39 PERMISSION STANDAR SISTEM TERNAKVERSE
// -----------------------------------------------------------------------------
const DEFAULT_PERMISSIONS: PermissionItem[] = [
    // 1. Dashboard (2)
    {
        slug: 'dashboard.view',
        name: 'Lihat Dashboard Eksekutif',
        module: 'Dashboard',
        description: 'Akses pemantauan visual metrik utama peternakan Banyumas',
    },
    {
        slug: 'dashboard.analytics',
        name: 'Analisis Metrik & ECharts',
        module: 'Dashboard',
        description: 'Akses drilldown grafik interaktif, statistik tren, dan visualisasi spasial',
    },

    // 2. Populasi (5)
    {
        slug: 'population.view',
        name: 'Lihat Populasi Ternak',
        module: 'Populasi',
        description: 'Melihat data sensus populasi ternak per kecamatan dan desa',
    },
    {
        slug: 'population.create',
        name: 'Tambah Data Populasi',
        module: 'Populasi',
        description: 'Menambahkan data sensus populasi ternak baru',
    },
    {
        slug: 'population.update',
        name: 'Edit Data Populasi',
        module: 'Populasi',
        description: 'Mengubah data populasi ternak yang telah tercatat',
    },
    {
        slug: 'population.delete',
        name: 'Hapus Data Populasi',
        module: 'Populasi',
        description: 'Menghapus catatan data populasi ternak',
    },
    {
        slug: 'population.export',
        name: 'Ekspor Data Populasi',
        module: 'Populasi',
        description: 'Mengekspor data tabular populasi ternak ke file spreadsheet/CSV',
    },

    // 3. Produksi (5)
    {
        slug: 'production.view',
        name: 'Lihat Produksi Ternak',
        module: 'Produksi',
        description: 'Melihat data hasil produksi ternak (daging, susu, telur)',
    },
    {
        slug: 'production.create',
        name: 'Tambah Data Produksi',
        module: 'Produksi',
        description: 'Mencatat hasil produksi peternakan baru',
    },
    {
        slug: 'production.update',
        name: 'Edit Data Produksi',
        module: 'Produksi',
        description: 'Memperbarui data hasil produksi peternakan',
    },
    {
        slug: 'production.delete',
        name: 'Hapus Data Produksi',
        module: 'Produksi',
        description: 'Menghapus data hasil produksi peternakan',
    },
    {
        slug: 'production.export',
        name: 'Ekspor Data Produksi',
        module: 'Produksi',
        description: 'Mengekspor data produksi komoditas peternakan',
    },

    // 4. Kasus Penyakit (5)
    {
        slug: 'disease.view',
        name: 'Lihat Kasus Penyakit',
        module: 'Kasus Penyakit',
        description: 'Melihat rekaman wabah penyakit hewan ternak (PMK, LSD, dll)',
    },
    {
        slug: 'disease.create',
        name: 'Tambah Kasus Penyakit',
        module: 'Kasus Penyakit',
        description: 'Melaporkan dan mencatat kejadian kasus penyakit baru',
    },
    {
        slug: 'disease.update',
        name: 'Edit Kasus Penyakit',
        module: 'Kasus Penyakit',
        description: 'Memperbarui status penanganan kasus penyakit hewan',
    },
    {
        slug: 'disease.delete',
        name: 'Hapus Kasus Penyakit',
        module: 'Kasus Penyakit',
        description: 'Menghapus rekaman kasus penyakit hewan',
    },
    {
        slug: 'disease.export',
        name: 'Ekspor Data Penyakit',
        module: 'Kasus Penyakit',
        description: 'Mengekspor rekapitulasi epidemiologi penyakit hewan',
    },

    // 5. WebGIS (3)
    {
        slug: 'webgis.view',
        name: 'Akses Peta WebGIS',
        module: 'WebGIS',
        description: 'Melihat peta interaktif tematik geospasial peternakan',
    },
    {
        slug: 'webgis.manage_layers',
        name: 'Kelola Layer Geospasial',
        module: 'WebGIS',
        description: 'Mengatur layer batas administrasi, choropleth, dan styling peta',
    },
    {
        slug: 'webgis.manage_points',
        name: 'Kelola Titik Fasilitas Geospasial',
        module: 'WebGIS',
        description: 'Menambah, mengubah, dan menghapus titik spasial fasilitas RPH & pasar',
    },

    // 6. Early Warning System (3)
    {
        slug: 'ews.view',
        name: 'Lihat Status EWS',
        module: 'Early Warning System',
        description: 'Melihat status peringatan dini dan zonasi kerentanan wilayah',
    },
    {
        slug: 'ews.manage_threshold',
        name: 'Kelola Ambang Batas EWS',
        module: 'Early Warning System',
        description: 'Mengubah bobot scoring dan threshold status zonasi (Hijau/Kuning/Merah)',
    },
    {
        slug: 'ews.recalculate',
        name: 'Rekalkulasi Status EWS',
        module: 'Early Warning System',
        description: 'Memicu kalkulasi ulang algoritma zonasi epidemiologi wilayah',
    },

    // 7. Laporan (5)
    {
        slug: 'reports.view',
        name: 'Akses Laporan Resmi',
        module: 'Laporan',
        description: 'Melihat rekapitulasi data laporan berkala dinas',
    },
    {
        slug: 'reports.export_pdf',
        name: 'Ekspor Laporan PDF',
        module: 'Laporan',
        description: 'Mengunduh laporan resmi dinas berformat PDF',
    },
    {
        slug: 'reports.export_excel',
        name: 'Ekspor Laporan Excel',
        module: 'Laporan',
        description: 'Mengunduh laporan resmi dinas berformat Excel (XLSX)',
    },
    {
        slug: 'reports.export_kugi',
        name: 'Ekspor Katalog KUGI',
        module: 'Laporan',
        description: 'Mengekspor data geospasial sesuai standar KUGI BIG',
    },
    {
        slug: 'reports.export_iso_xml',
        name: 'Ekspor Metadata ISO 19115',
        module: 'Laporan',
        description: 'Mengekspor file metadata geospasial standar ISO 19115 XML',
    },

    // 8. Audit Trail (2)
    {
        slug: 'audit.view',
        name: 'Akses Log Audit',
        module: 'Audit Trail',
        description: 'Melihat catatan log aktivitas forensik operator dan sistem',
    },
    {
        slug: 'audit.export',
        name: 'Ekspor Log Audit',
        module: 'Audit Trail',
        description: 'Mengekspor riwayat log audit forensik',
    },

    // 9. Pengguna (5)
    {
        slug: 'users.view',
        name: 'Lihat Daftar Pengguna',
        module: 'Pengguna',
        description: 'Melihat daftar akun petugas dinas dan pimpinan',
    },
    {
        slug: 'users.create',
        name: 'Tambah Pengguna Baru',
        module: 'Pengguna',
        description: 'Mendaftarkan akun petugas dinas baru',
    },
    {
        slug: 'users.update',
        name: 'Perbarui Data Pengguna',
        module: 'Pengguna',
        description: 'Mengubah identitas, email, dan status aktif pengguna',
    },
    {
        slug: 'users.delete',
        name: 'Hapus Pengguna',
        module: 'Pengguna',
        description: 'Menghapus atau menonaktifkan akun pengguna',
    },
    {
        slug: 'users.assign_role',
        name: 'Tetapkan Peran Pengguna',
        module: 'Pengguna',
        description: 'Mengubah penugasan peran (role) kepada akun pengguna',
    },

    // 10. RBAC (4)
    {
        slug: 'rbac.view',
        name: 'Lihat Konfigurasi RBAC',
        module: 'RBAC',
        description: 'Melihat daftar peran dinamis dan matriks hak akses',
    },
    {
        slug: 'rbac.manage_roles',
        name: 'Kelola Peran (Roles)',
        module: 'RBAC',
        description: 'Membuat, mengubah, dan menghapus peran kustom',
    },
    {
        slug: 'rbac.manage_permissions',
        name: 'Kelola Master Izin Akses',
        module: 'RBAC',
        description: 'Mengonfigurasi deskripsi dan master hak akses sistem',
    },
    {
        slug: 'rbac.assign_permissions',
        name: 'Tetapkan Hak Akses ke Peran',
        module: 'RBAC',
        description: 'Menyesuaikan matriks hak akses untuk setiap peran',
    },
];

// Fallback daftar role awal
const INITIAL_ROLES: RoleItem[] = [
    {
        id: 1,
        name: 'Super Administrator',
        slug: 'admin',
        description: 'Akses penuh ke seluruh modul sistem dan konfigurasi master data',
        is_system: true,
        is_active: true,
        users_count: 1,
        permissions_count: 39,
    },
    {
        id: 2,
        name: 'Pimpinan & Pemangku Kepentingan',
        slug: 'stakeholder',
        description: 'Akses eksekutif read-only untuk pengawasan, analitik, dan ekspor dokumen',
        is_system: true,
        is_active: true,
        users_count: 1,
        permissions_count: 12,
    },
    {
        id: 3,
        name: 'Operator Teknis Peternakan',
        slug: 'operator',
        description: 'Petugas dinas lapangan untuk input dan pemutakhiran data peternakan & EWS',
        is_system: false,
        is_active: true,
        users_count: 3,
        permissions_count: 18,
    },
    {
        id: 4,
        name: 'Auditor Geospasial Independen',
        slug: 'auditor',
        description: 'Pemeriksa kualitas spasial batas wilayah dan riwayat log forensik',
        is_system: false,
        is_active: true,
        users_count: 1,
        permissions_count: 8,
    },
];

// Fallback izin per role awal
const ALL_SLUGS = DEFAULT_PERMISSIONS.map((p) => p.slug);
const STAKEHOLDER_SLUGS = [
    'dashboard.view',
    'dashboard.analytics',
    'population.view',
    'production.view',
    'disease.view',
    'webgis.view',
    'ews.view',
    'reports.view',
    'reports.export_pdf',
    'reports.export_excel',
    'reports.export_kugi',
    'reports.export_iso_xml',
];
const OPERATOR_SLUGS = [
    'dashboard.view',
    'dashboard.analytics',
    'population.view',
    'population.create',
    'population.update',
    'population.export',
    'production.view',
    'production.create',
    'production.update',
    'production.export',
    'disease.view',
    'disease.create',
    'disease.update',
    'disease.export',
    'webgis.view',
    'ews.view',
    'reports.view',
    'reports.export_pdf',
];
const AUDITOR_SLUGS = [
    'dashboard.view',
    'dashboard.analytics',
    'webgis.view',
    'reports.view',
    'reports.export_pdf',
    'reports.export_kugi',
    'audit.view',
    'audit.export',
];

// -----------------------------------------------------------------------------
// REAKTIF STATE UTAMA
// -----------------------------------------------------------------------------
const activeSubTab = ref<'matrix' | 'roles'>('matrix');
const selectedRoleSlug = ref<string>('admin');
const roles = ref<RoleItem[]>(INITIAL_ROLES);
const permissionMatrix = ref<Record<string, string[]>>({
    admin: [...ALL_SLUGS],
    stakeholder: [...STAKEHOLDER_SLUGS],
    operator: [...OPERATOR_SLUGS],
    auditor: [...AUDITOR_SLUGS],
});

// State penyaringan dan pencarian
const searchQuery = ref('');
const isSaving = ref(false);
const hasUnsavedChanges = ref(false);
const toastMsg = ref<string | null>(null);
const toastType = ref<'success' | 'warning' | 'error'>('success');

// Modal State
const isConfirmSaveOpen = ref(false);
const isAddRoleOpen = ref(false);
const isEditRoleOpen = ref(false);
const isDeleteRoleOpen = ref(false);

const roleForm = ref({
    id: 0,
    name: '',
    slug: '',
    description: '',
    is_active: true,
});
const roleToDelete = ref<RoleItem | null>(null);

// -----------------------------------------------------------------------------
// COMPUTED DATA
// -----------------------------------------------------------------------------
const currentRole = computed<RoleItem | undefined>(() => {
    return roles.value.find((r) => r.slug === selectedRoleSlug.value);
});

const currentRolePermissions = computed<Set<string>>({
    get() {
        const list = permissionMatrix.value[selectedRoleSlug.value] || [];
        return new Set(list);
    },
    set(newSet) {
        permissionMatrix.value[selectedRoleSlug.value] = Array.from(newSet);
    },
});

// Guardrail Proteksi
function isPermissionLocked(roleSlug: string, permSlug: string): boolean {
    if (roleSlug === 'admin') {
        // Admin wajib memiliki izin-izin fundamental sistem
        return ['rbac.manage_permissions', 'rbac.view', 'dashboard.view'].includes(permSlug);
    }
    if (roleSlug === 'stakeholder') {
        // Stakeholder dibatasi kebijakan Read-Only
        if (
            permSlug.endsWith('.create') ||
            permSlug.endsWith('.update') ||
            permSlug.endsWith('.delete') ||
            permSlug === 'ews.manage_threshold' ||
            permSlug === 'ews.recalculate' ||
            permSlug === 'webgis.manage_layers' ||
            permSlug === 'webgis.manage_points' ||
            permSlug.startsWith('users.') ||
            permSlug.startsWith('rbac.') ||
            permSlug.startsWith('audit.')
        ) {
            return true;
        }
    }
    return false;
}

function getPermissionLockBadge(roleSlug: string, permSlug: string): { label: string; isAmber: boolean } | null {
    if (roleSlug === 'admin' && ['rbac.manage_permissions', 'rbac.view', 'dashboard.view'].includes(permSlug)) {
        return { label: '🔒 Wajib Sistem', isAmber: false };
    }
    if (roleSlug === 'stakeholder' && isPermissionLocked('stakeholder', permSlug)) {
        return { label: '🔒 Terkunci (Read-Only Policy)', isAmber: true };
    }
    return null;
}

// Pengelompokan Permissions per Modul dengan filter pencarian
const filteredModules = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return MODULE_DEFINITIONS.map((mod) => {
        const perms = DEFAULT_PERMISSIONS.filter((p) => {
            if (p.module !== mod.id) return false;
            if (!q) return true;
            return (
                p.name.toLowerCase().includes(q) ||
                p.slug.toLowerCase().includes(q) ||
                p.description.toLowerCase().includes(q) ||
                mod.name.toLowerCase().includes(q)
            );
        });

        const activeCount = perms.filter((p) => currentRolePermissions.value.has(p.slug)).length;

        return {
            ...mod,
            permissions: perms,
            activeCount,
            totalCount: perms.length,
        };
    }).filter((mod) => mod.permissions.length > 0);
});

// -----------------------------------------------------------------------------
// TOGGLE SWITCH HANDLER
// -----------------------------------------------------------------------------
function togglePermission(slug: string): void {
    if (isPermissionLocked(selectedRoleSlug.value, slug)) {
        triggerToast('Izin ini diproteksi oleh kebijakan keamanan sistem dan tidak dapat diubah.', 'warning');
        return;
    }

    const currentList = permissionMatrix.value[selectedRoleSlug.value] || [];
    const set = new Set(currentList);

    if (set.has(slug)) {
        set.delete(slug);
    } else {
        set.add(slug);
    }

    permissionMatrix.value[selectedRoleSlug.value] = Array.from(set);
    hasUnsavedChanges.value = true;
    updateRolePermissionCount(selectedRoleSlug.value);
}

function enableAllInModule(moduleId: string): void {
    const perms = DEFAULT_PERMISSIONS.filter((p) => p.module === moduleId);
    const set = new Set(permissionMatrix.value[selectedRoleSlug.value] || []);
    let modified = false;

    for (const p of perms) {
        if (!isPermissionLocked(selectedRoleSlug.value, p.slug) && !set.has(p.slug)) {
            set.add(p.slug);
            modified = true;
        }
    }

    if (modified) {
        permissionMatrix.value[selectedRoleSlug.value] = Array.from(set);
        hasUnsavedChanges.value = true;
        updateRolePermissionCount(selectedRoleSlug.value);
    }
}

function disableAllInModule(moduleId: string): void {
    const perms = DEFAULT_PERMISSIONS.filter((p) => p.module === moduleId);
    const set = new Set(permissionMatrix.value[selectedRoleSlug.value] || []);
    let modified = false;

    for (const p of perms) {
        if (!isPermissionLocked(selectedRoleSlug.value, p.slug) && set.has(p.slug)) {
            set.delete(p.slug);
            modified = true;
        }
    }

    if (modified) {
        permissionMatrix.value[selectedRoleSlug.value] = Array.from(set);
        hasUnsavedChanges.value = true;
        updateRolePermissionCount(selectedRoleSlug.value);
    }
}

function updateRolePermissionCount(slug: string): void {
    const target = roles.value.find((r) => r.slug === slug);
    if (target) {
        target.permissions_count = (permissionMatrix.value[slug] || []).length;
    }
}

// -----------------------------------------------------------------------------
// ROLE MANAGEMENT HANDLERS (SUB-TAB 2)
// -----------------------------------------------------------------------------
function toggleRoleActiveStatus(role: RoleItem): void {
    if (role.is_system) {
        triggerToast('Peran sistem bawaan selalu aktif dan tidak dapat dinonaktifkan.', 'warning');
        return;
    }

    role.is_active = !role.is_active;
    triggerToast(
        `Status peran '${role.name}' diubah menjadi ${role.is_active ? 'AKTIF (ON)' : 'NONAKTIF (OFF)'}.`,
        'success'
    );
}

function openAddRoleModal(): void {
    roleForm.value = {
        id: 0,
        name: '',
        slug: '',
        description: '',
        is_active: true,
    };
    isAddRoleOpen.value = true;
}

function handleNameInput(): void {
    if (!roleForm.value.id) {
        roleForm.value.slug = roleForm.value.name
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }
}

function submitAddRole(): void {
    if (!roleForm.value.name.trim() || !roleForm.value.slug.trim()) {
        triggerToast('Nama peran dan slug wajib diisi.', 'error');
        return;
    }

    const existing = roles.value.find((r) => r.slug === roleForm.value.slug);
    if (existing) {
        triggerToast(`Peran dengan slug '${roleForm.value.slug}' sudah ada.`, 'error');
        return;
    }

    fetch('/api/v1/internal/admin/rbac/roles', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            name: roleForm.value.name.trim(),
            slug: roleForm.value.slug.trim(),
            description: roleForm.value.description.trim() || null,
            is_active: roleForm.value.is_active,
        }),
    })
        .then(async (response) => {
            if (!response.ok) {
                const errData = await response.json().catch(() => null);
                triggerToast(errData?.message || `Gagal menambahkan peran (HTTP ${response.status}).`, 'error');
                return;
            }

            const resData = await response.json().catch(() => null);
            const createdRole: RoleItem = resData?.data || {
                id: Math.max(...roles.value.map((r) => r.id), 0) + 1,
                name: roleForm.value.name.trim(),
                slug: roleForm.value.slug.trim(),
                description: roleForm.value.description.trim() || null,
                is_system: false,
                is_active: roleForm.value.is_active,
                users_count: 0,
                permissions_count: 0,
            };

            roles.value.push(createdRole);
            permissionMatrix.value[createdRole.slug] = ['dashboard.view', 'webgis.view'];
            updateRolePermissionCount(createdRole.slug);

            isAddRoleOpen.value = false;
            triggerToast(`Peran kustom '${createdRole.name}' berhasil ditambahkan.`, 'success');
        })
        .catch((err: any) => {
            triggerToast(`Gagal menambahkan peran: ${err?.message || 'Terjadi kesalahan sistem.'}`, 'error');
        });
}

function openEditRoleModal(role: RoleItem): void {
    roleForm.value = {
        id: role.id,
        name: role.name,
        slug: role.slug,
        description: role.description || '',
        is_active: role.is_active,
    };
    isEditRoleOpen.value = true;
}

function submitEditRole(): void {
    const role = roles.value.find((r) => r.id === roleForm.value.id);
    if (!role) return;

    if (!roleForm.value.name.trim()) {
        triggerToast('Nama peran tidak boleh kosong.', 'error');
        return;
    }

    fetch('/api/v1/internal/admin/rbac/roles/' + role.id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            name: roleForm.value.name.trim(),
            description: roleForm.value.description.trim() || null,
            is_active: roleForm.value.is_active,
        }),
    })
        .then(async (response) => {
            if (!response.ok) {
                const errData = await response.json().catch(() => null);
                triggerToast(errData?.message || `Gagal memperbarui peran (HTTP ${response.status}).`, 'error');
                return;
            }

            role.name = roleForm.value.name.trim();
            role.description = roleForm.value.description.trim() || null;
            if (!role.is_system) {
                role.is_active = roleForm.value.is_active;
            }

            isEditRoleOpen.value = false;
            triggerToast(`Peran '${role.name}' berhasil diperbarui.`, 'success');
        })
        .catch((err: any) => {
            triggerToast(`Gagal memperbarui peran: ${err?.message || 'Terjadi kesalahan sistem.'}`, 'error');
        });
}

function confirmDeleteRole(role: RoleItem): void {
    if (role.is_system) {
        triggerToast('Peran sistem bawaan tidak dapat dihapus.', 'error');
        return;
    }
    roleToDelete.value = role;
    isDeleteRoleOpen.value = true;
}

function executeDeleteRole(): void {
    if (!roleToDelete.value) return;

    const id = roleToDelete.value.id;
    const slug = roleToDelete.value.slug;
    const name = roleToDelete.value.name;

    fetch('/api/v1/internal/admin/rbac/roles/' + id, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(async (response) => {
            if (!response.ok) {
                const errData = await response.json().catch(() => null);
                triggerToast(errData?.message || `Gagal menghapus peran (HTTP ${response.status}).`, 'error');
                return;
            }

            roles.value = roles.value.filter((r) => r.id !== id);
            delete permissionMatrix.value[slug];

            if (selectedRoleSlug.value === slug) {
                selectedRoleSlug.value = 'admin';
            }

            isDeleteRoleOpen.value = false;
            roleToDelete.value = null;
            triggerToast(`Peran '${name}' berhasil dihapus.`, 'success');
        })
        .catch((err: any) => {
            triggerToast(`Gagal menghapus peran: ${err?.message || 'Terjadi kesalahan sistem.'}`, 'error');
        });
}

function selectRoleAndGoToMatrix(slug: string): void {
    selectedRoleSlug.value = slug;
    activeSubTab.value = 'matrix';
}

// -----------------------------------------------------------------------------
// SIMPAN PERUBAHAN IZIN KE SERVER
// -----------------------------------------------------------------------------
async function confirmSavePermissions(): Promise<void> {
    isSaving.value = true;
    const targetSlug = selectedRoleSlug.value;
    const targetPermissions = permissionMatrix.value[targetSlug] || [];

    try {
        const response = await fetch('/api/v1/internal/admin/rbac/assign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                role_slug: targetSlug,
                permissions: targetPermissions,
            }),
        });

        if (response.ok) {
            hasUnsavedChanges.value = false;
            isConfirmSaveOpen.value = false;
            triggerToast(
                `Hak akses untuk peran '${currentRole.value?.name || targetSlug}' berhasil disimpan ke sistem.`,
                'success'
            );
        } else {
            const errData = await response.json().catch(() => null);
            triggerToast(
                errData?.message || `Gagal menyimpan hak akses (HTTP ${response.status}).`,
                'error'
            );
        }
    } catch (err: any) {
        triggerToast(
            `Gagal menyimpan hak akses: ${err?.message || 'Koneksi ke server terputus.'}`,
            'error'
        );
    } finally {
        isSaving.value = false;
    }
}

function resetChanges(): void {
    if (selectedRoleSlug.value === 'admin') {
        permissionMatrix.value.admin = [...ALL_SLUGS];
    } else if (selectedRoleSlug.value === 'stakeholder') {
        permissionMatrix.value.stakeholder = [...STAKEHOLDER_SLUGS];
    } else if (selectedRoleSlug.value === 'operator') {
        permissionMatrix.value.operator = [...OPERATOR_SLUGS];
    } else if (selectedRoleSlug.value === 'auditor') {
        permissionMatrix.value.auditor = [...AUDITOR_SLUGS];
    }
    hasUnsavedChanges.value = false;
    updateRolePermissionCount(selectedRoleSlug.value);
    triggerToast('Perubahan izin dikembalikan ke konfigurasi tersimpan.', 'warning');
}

function triggerToast(message: string, type: 'success' | 'warning' | 'error' = 'success'): void {
    toastMsg.value = message;
    toastType.value = type;
    setTimeout(() => {
        toastMsg.value = null;
    }, 3500);
}

// -----------------------------------------------------------------------------
// SINKRONISASI API SAAT KOMPONEN DIMUAT
// -----------------------------------------------------------------------------
onMounted(async () => {
    try {
        const [rolesRes, matrixRes, permsRes] = await Promise.allSettled([
            fetch('/api/v1/internal/admin/rbac/roles'),
            fetch('/api/v1/internal/admin/rbac/matrix'),
            fetch('/api/v1/internal/admin/rbac/permissions'),
        ]);

        if (rolesRes.status === 'fulfilled' && rolesRes.value.ok) {
            const data = await rolesRes.value.json();
            if (data?.data && Array.isArray(data.data) && data.data.length > 0) {
                roles.value = data.data;
            }
        }

        if (matrixRes.status === 'fulfilled' && matrixRes.value.ok) {
            const mData = await matrixRes.value.json();
            if (mData?.data?.matrix) {
                permissionMatrix.value = {
                    ...permissionMatrix.value,
                    ...mData.data.matrix,
                };
            }
        }
    } catch {
        // Gunakan state default yang sudah mencakup 39 permissions
    }
});
</script>

<template>
    <div class="w-full space-y-6 pb-24 text-[#171B17] dark:text-[#F5F5F5] font-ui antialiased">
        <!-- ================================================================= -->
        <!-- HEADER UTAMA & SUB-TAB SWITCHER                                   -->
        <!-- ================================================================= -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-[#171B17] dark:text-[#00D084] shadow-xs">
                        <ShieldCheck class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                            Manajemen Role &amp; Hak Akses (Dynamic RBAC)
                        </h1>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Konfigurasi matriks izin akses berbasis peran dan proteksi endpoint operasional
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action CTA & Sub-tab Switcher -->
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    @click="openAddRoleModal"
                    class="h-9 px-4 rounded-lg bg-[#171B17] hover:bg-[#282828] text-white dark:bg-[#00D084] dark:hover:bg-[#00F0A0] dark:text-[#062117] font-bold text-xs flex items-center gap-2 shadow-sm transition-all cursor-pointer focus-visible:ring-2 focus-visible:ring-[#171B17] dark:focus-visible:ring-[#00D084]"
                >
                    <Plus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Role Baru</span>
                </button>

                <!-- Sub-tab Selector -->
                <div class="inline-flex p-1 rounded-xl bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="activeSubTab = 'matrix'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                        :class="[
                            activeSubTab === 'matrix'
                                ? 'bg-[#171B17] text-white dark:bg-[#00D084] dark:text-[#062117] font-bold shadow-sm'
                                : 'text-[#737B71] hover:text-[#171B17] hover:bg-[#E8EBE5] dark:text-[#858585] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]/50'
                        ]"
                    >
                        <ShieldCheck class="w-3.5 h-3.5" />
                        <span>Matriks Izin</span>
                    </button>
                    <button
                        type="button"
                        @click="activeSubTab = 'roles'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                        :class="[
                            activeSubTab === 'roles'
                                ? 'bg-[#171B17] text-white dark:bg-[#00D084] dark:text-[#062117] font-bold shadow-sm'
                                : 'text-[#737B71] hover:text-[#171B17] hover:bg-[#E8EBE5] dark:text-[#858585] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]/50'
                        ]"
                    >
                        <Users class="w-3.5 h-3.5" />
                        <span>Daftar Role</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- SUB-TAB 1: MATRIKS IZIN (PERMISSION MATRIX)                       -->
        <!-- ================================================================= -->
        <div v-if="activeSubTab === 'matrix'" class="space-y-6">
            <!-- ROLE SELECTOR CHIPS & FILTER BAR -->
            <div class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-4 md:p-5 space-y-4 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2">
                        <SlidersHorizontal class="w-4 h-4 text-[#171B17] dark:text-[#00D084]" />
                        <span class="text-xs font-bold uppercase tracking-wider text-[#444C43] dark:text-[#B3B3B3]">
                            Pilih Peran Untuk Konfigurasi Matriks:
                        </span>
                    </div>

                    <!-- Search Filter Input -->
                    <div class="relative w-full sm:w-72">
                        <Search class="w-4 h-4 text-[#737B71] dark:text-[#858585] absolute left-3 top-2.5" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari izin atau modul..."
                            class="w-full h-9 pl-9 pr-3 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71] dark:placeholder:text-[#858585] focus:outline-none focus:border-[#171B17] dark:focus:border-[#00D084]"
                        />
                    </div>
                </div>

                <!-- Chips Role -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        v-for="role in roles"
                        :key="role.slug"
                        type="button"
                        @click="selectedRoleSlug = role.slug"
                        class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer flex items-center gap-2.5"
                        :class="[
                            selectedRoleSlug === role.slug
                                ? 'bg-[#171B17] border-[#171B17] text-white shadow-sm dark:bg-[#181818] dark:border-[#00D084] dark:text-[#00D084] dark:ring-1 dark:ring-[#00D084]'
                                : 'bg-white border-[#E0E4DD] text-[#444C43] hover:text-[#171B17] hover:border-[#CBD2C8] hover:bg-[#E8EBE5] dark:bg-[#111111] dark:border-[#1E1E1E] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:border-[#282828] dark:hover:bg-[#181818]'
                        ]"
                    >
                        <span class="font-bold">{{ role.name }}</span>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                            :class="
                                selectedRoleSlug === role.slug
                                    ? 'bg-white text-[#171B17] dark:bg-[#00D084] dark:text-[#062117]'
                                    : 'bg-[#E8EBE5] text-[#737B71] dark:bg-[#181818] dark:text-[#858585]'
                            "
                        >
                            {{ (permissionMatrix[role.slug] || []).length }}/39
                        </span>
                        <span
                            v-if="role.is_system"
                            class="text-[9px] px-1.5 py-0.2 rounded border uppercase font-mono tracking-wider"
                            :class="
                                selectedRoleSlug === role.slug
                                    ? 'border-white/40 text-white/90 dark:border-[#00D084]/50 dark:text-[#00D084]'
                                    : 'border-[#CBD2C8] text-[#737B71] dark:border-[#282828] dark:text-[#858585]'
                            "
                        >
                            Sistem
                        </span>
                    </button>
                </div>

                <!-- Active Role Overview Card -->
                <div v-if="currentRole" class="p-3.5 rounded-xl bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-sm text-[#171B17] dark:text-[#F5F5F5]">{{ currentRole.name }}</span>
                            <span class="font-mono text-[10px] px-2 py-0.5 rounded bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3]">
                                slug: {{ currentRole.slug }}
                            </span>
                            <span
                                v-if="currentRole.is_system"
                                class="text-[10px] px-2 py-0.5 rounded bg-[#E8EBE5] dark:bg-[#00D084]/15 border border-[#CBD2C8] dark:border-[#00D084]/30 text-[#171B17] dark:text-[#00D084] font-semibold"
                            >
                                Peran Sistem Bawaan
                            </span>
                        </div>
                        <p class="text-[#737B71] dark:text-[#858585] text-xs">
                            {{ currentRole.description || 'Tidak ada deskripsi tambahan untuk peran ini.' }}
                        </p>
                    </div>

                    <!-- Role Policy Badge Notice -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div
                            v-if="currentRole.slug === 'stakeholder'"
                            class="px-3 py-1.5 rounded-lg bg-[#E8EBE5] dark:bg-amber-500/10 border border-[#CBD2C8] dark:border-amber-500/30 text-[#171B17] dark:text-amber-300 text-[11px] font-semibold flex items-center gap-1.5"
                        >
                            <Lock class="w-3.5 h-3.5 text-[#171B17] dark:text-amber-400 shrink-0" />
                            <span>Read-Only Policy Aktif (Operasi Mutasi Terkunci)</span>
                        </div>
                        <div
                            v-else-if="currentRole.slug === 'admin'"
                            class="px-3 py-1.5 rounded-lg bg-[#E8EBE5] dark:bg-[#00D084]/10 border border-[#CBD2C8] dark:border-[#00D084]/30 text-[#171B17] dark:text-[#00D084] text-[11px] font-semibold flex items-center gap-1.5"
                        >
                            <ShieldCheck class="w-3.5 h-3.5 text-[#171B17] dark:text-[#00D084] shrink-0" />
                            <span>Full Privileges (Izin Fundamental Terkunci)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- BENTO GRID KARTU MODUL PERMISSIONS (10 MODUL)                 -->
            <!-- ============================================================= -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <div
                    v-for="mod in filteredModules"
                    :key="mod.id"
                    class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:border-[#CBD2C8] dark:hover:border-[#00D084]/30 transition-all duration-200"
                >
                    <div>
                        <!-- Header Kartu Modul -->
                        <div class="flex items-start justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-xl bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#00D084] flex items-center justify-center shrink-0">
                                    <component :is="mod.icon" class="w-4 h-4 stroke-[2.2]" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-display font-bold text-sm md:text-base text-[#171B17] dark:text-[#F5F5F5]">
                                            {{ mod.name }}
                                        </h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3]">
                                            {{ mod.activeCount }}/{{ mod.totalCount }} Aktif
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-[#737B71] dark:text-[#858585] mt-0.5 leading-snug">
                                        {{ mod.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Tombol Cepat: Aktifkan Semua / Matikan Semua -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    @click="enableAllInModule(mod.id)"
                                    title="Aktifkan seluruh izin modul ini"
                                    class="px-2 py-1 rounded bg-white hover:bg-[#E8EBE5] border border-[#E0E4DD] text-[10px] font-semibold text-[#171B17] dark:bg-[#181818] dark:hover:bg-[#1E1E1E] dark:border-[#1E1E1E] dark:text-[#00D084] transition-colors cursor-pointer"
                                >
                                    Semua ON
                                </button>
                                <button
                                    type="button"
                                    @click="disableAllInModule(mod.id)"
                                    title="Matikan seluruh izin modul ini (kecuali yang terkunci)"
                                    class="px-2 py-1 rounded bg-white hover:bg-[#E8EBE5] border border-[#E0E4DD] text-[10px] font-semibold text-[#737B71] hover:text-[#171B17] dark:bg-[#181818] dark:hover:bg-[#1E1E1E] dark:border-[#1E1E1E] dark:text-rose-400 transition-colors cursor-pointer"
                                >
                                    Semua OFF
                                </button>
                            </div>
                        </div>

                        <!-- Daftar Izin Dalam Modul -->
                        <div class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] pt-1">
                            <div
                                v-for="perm in mod.permissions"
                                :key="perm.slug"
                                class="py-3 flex items-center justify-between gap-3 group hover:bg-[#E8EBE5] dark:hover:bg-[#181818]/60 px-2 rounded-lg transition-colors"
                            >
                                <div class="min-w-0 flex-1 pr-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5]">
                                            {{ perm.name }}
                                        </span>
                                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#737B71] dark:text-[#858585]">
                                            {{ perm.slug }}
                                        </span>
                                        <!-- Guardrail Lock Badge -->
                                        <span
                                            v-if="getPermissionLockBadge(selectedRoleSlug, perm.slug)"
                                            class="text-[10px] px-2 py-0.5 rounded font-semibold border flex items-center gap-1"
                                            :class="
                                                getPermissionLockBadge(selectedRoleSlug, perm.slug)?.isAmber
                                                    ? 'bg-[#E8EBE5] border-[#CBD2C8] text-[#171B17] dark:bg-amber-500/10 dark:border-amber-500/30 dark:text-amber-300'
                                                    : 'bg-[#E8EBE5] border-[#CBD2C8] text-[#171B17] dark:bg-[#00D084]/10 dark:border-[#00D084]/30 dark:text-[#00D084]'
                                            "
                                        >
                                            {{ getPermissionLockBadge(selectedRoleSlug, perm.slug)?.label }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-[#737B71] dark:text-[#858585] mt-1 line-clamp-2">
                                        {{ perm.description }}
                                    </p>
                                </div>

                                <!-- ============================================= -->
                                <!-- CRITICAL: ACCESSIBLE HIGH-CONTRAST TOGGLE     -->
                                <!-- SWITCH (ON/OFF) — ZERO CHECKBOXES ALLOWED!    -->
                                <!-- ============================================= -->
                                <div class="shrink-0 flex items-center">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="currentRolePermissions.has(perm.slug)"
                                        :aria-label="`Sakelar izin ${perm.name}`"
                                        :disabled="isPermissionLocked(selectedRoleSlug, perm.slug)"
                                        @click="togglePermission(perm.slug)"
                                        class="relative inline-flex h-7 w-16 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#171B17] dark:focus-visible:ring-[#00D084] focus-visible:ring-offset-2 focus-visible:ring-offset-[#F4F5F2] dark:focus-visible:ring-offset-[#111111]"
                                        :class="[
                                            currentRolePermissions.has(perm.slug)
                                                ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]'
                                                : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]',
                                            isPermissionLocked(selectedRoleSlug, perm.slug)
                                                ? 'cursor-not-allowed opacity-60'
                                                : 'hover:opacity-90'
                                        ]"
                                        :title="
                                            isPermissionLocked(selectedRoleSlug, perm.slug)
                                                ? 'Izin ini diproteksi oleh kebijakan keamanan'
                                                : `Klik untuk mengubah status izin (saat ini ${currentRolePermissions.has(perm.slug) ? 'ON' : 'OFF'})`
                                        "
                                    >
                                        <span class="sr-only">{{ perm.name }}</span>

                                        <!-- Text Indicator Label on Track -->
                                        <span
                                            class="absolute text-[10px] font-black tracking-wider select-none transition-all duration-200"
                                            :class="
                                                currentRolePermissions.has(perm.slug)
                                                    ? 'left-2 text-white dark:text-[#062117] font-extrabold'
                                                    : 'right-2 text-[#737B71] dark:text-[#858585] font-bold'
                                            "
                                        >
                                            {{ currentRolePermissions.has(perm.slug) ? 'ON' : 'OFF' }}
                                        </span>

                                        <!-- Sliding Knob -->
                                        <span
                                            aria-hidden="true"
                                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out flex items-center justify-center"
                                            :class="currentRolePermissions.has(perm.slug) ? 'translate-x-9' : 'translate-x-0'"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full"
                                                :class="currentRolePermissions.has(perm.slug) ? 'bg-[#171B17] dark:bg-[#00D084]' : 'bg-[#737B71] dark:bg-[#858585]'"
                                            ></span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State bila hasil pencarian nihil -->
            <div
                v-if="filteredModules.length === 0"
                class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-12 text-center space-y-3"
            >
                <AlertTriangle class="w-10 h-10 text-[#171B17] dark:text-amber-400 mx-auto" />
                <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                    Tidak Ditemukan Izin yang Cocok
                </h3>
                <p class="text-xs text-[#737B71] dark:text-[#858585] max-w-md mx-auto">
                    Pencarian dengan kata kunci "{{ searchQuery }}" tidak menemukan izin atau modul yang sesuai.
                </p>
                <button
                    type="button"
                    @click="searchQuery = ''"
                    class="h-8 px-4 rounded-lg bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] transition-colors cursor-pointer"
                >
                    Bersihkan Pencarian
                </button>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- SUB-TAB 2: DAFTAR ROLE & PENGGUNA                                 -->
        <!-- ================================================================= -->
        <div v-else-if="activeSubTab === 'roles'" class="space-y-6">
            <div class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Daftar Peran &amp; Akun Terdaftar
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Kelola profil peran sistem, peran kustom dinas, serta status aktivasi operasional
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openAddRoleModal"
                        class="h-9 px-4 rounded-lg bg-[#171B17] hover:bg-[#282828] text-white dark:bg-[#00D084] dark:hover:bg-[#00F0A0] dark:text-[#062117] font-bold text-xs flex items-center gap-2 self-start sm:self-auto cursor-pointer"
                    >
                        <Plus class="w-4 h-4 stroke-[2.5]" />
                        <span>Tambah Peran Kustom</span>
                    </button>
                </div>

                <!-- Tabel Data Role -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-[#E0E4DD] dark:border-[#1E1E1E] text-[11px] font-bold text-[#737B71] dark:text-[#858585] uppercase tracking-wider bg-white dark:bg-[#181818]">
                                <th class="py-3 px-4">Nama Peran</th>
                                <th class="py-3 px-4">Slug Identifier</th>
                                <th class="py-3 px-4">Deskripsi</th>
                                <th class="py-3 px-4 text-center">Pengguna</th>
                                <th class="py-3 px-4 text-center">Jumlah Izin</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Tipe</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E]">
                            <tr
                                v-for="role in roles"
                                :key="role.id"
                                class="hover:bg-[#E8EBE5] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-bold text-[#171B17] dark:text-[#F5F5F5] whitespace-nowrap">
                                    {{ role.name }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-[#171B17] dark:text-[#00D084] whitespace-nowrap">
                                    {{ role.slug }}
                                </td>
                                <td class="py-3.5 px-4 text-[#737B71] dark:text-[#858585] max-w-xs truncate" :title="role.description || ''">
                                    {{ role.description || '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 font-mono font-semibold px-2 py-0.5 rounded bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3]">
                                        <Users class="w-3 h-3 text-[#737B71] dark:text-[#858585]" />
                                        {{ role.users_count }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-mono font-bold px-2 py-0.5 rounded bg-[#E8EBE5] dark:bg-[#00D084]/15 border border-[#CBD2C8] dark:border-[#00D084]/30 text-[#171B17] dark:text-[#00D084]">
                                        {{ (permissionMatrix[role.slug] || []).length }}/39
                                    </span>
                                </td>
                                <!-- TOGGLE STATUS ROLE (ON/OFF) -->
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="role.is_active"
                                        :disabled="role.is_system"
                                        @click="toggleRoleActiveStatus(role)"
                                        class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="[
                                            role.is_active
                                                ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]'
                                                : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]',
                                            role.is_system ? 'cursor-not-allowed opacity-60' : 'hover:opacity-90'
                                        ]"
                                        :title="role.is_system ? 'Peran sistem tidak dapat dinonaktifkan' : 'Klik untuk mengubah status aktif'"
                                    >
                                        <span
                                            class="absolute text-[9px] font-black tracking-wider select-none"
                                            :class="role.is_active ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                                        >
                                            {{ role.is_active ? 'ON' : 'OFF' }}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                            :class="role.is_active ? 'translate-x-8' : 'translate-x-0'"
                                        ></span>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border"
                                        :class="
                                            role.is_system
                                                ? 'bg-[#E8EBE5] dark:bg-[#00D084]/15 border-[#CBD2C8] dark:border-[#00D084]/30 text-[#171B17] dark:text-[#00D084]'
                                                : 'bg-white dark:bg-[#181818] border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3]'
                                        "
                                    >
                                        {{ role.is_system ? 'Sistem' : 'Kustom' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Kelola Matriks -->
                                        <button
                                            type="button"
                                            @click="selectRoleAndGoToMatrix(role.slug)"
                                            class="p-1.5 rounded-lg bg-white dark:bg-[#181818] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#00D084] transition-colors cursor-pointer"
                                            title="Buka Matriks Izin peran ini"
                                        >
                                            <SlidersHorizontal class="w-3.5 h-3.5" />
                                        </button>
                                        <!-- Edit Role -->
                                        <button
                                            type="button"
                                            @click="openEditRoleModal(role)"
                                            class="p-1.5 rounded-lg bg-white dark:bg-[#181818] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-white transition-colors cursor-pointer"
                                            title="Edit metadata peran"
                                        >
                                            <Edit class="w-3.5 h-3.5" />
                                        </button>
                                        <!-- Delete Role -->
                                        <button
                                            type="button"
                                            :disabled="role.is_system"
                                            @click="confirmDeleteRole(role)"
                                            class="p-1.5 rounded-lg border transition-colors cursor-pointer"
                                            :class="
                                                role.is_system
                                                    ? 'border-transparent text-[#CBD2C8] dark:text-[#282828] cursor-not-allowed'
                                                    : 'bg-white dark:bg-[#181818] hover:bg-rose-100 dark:hover:bg-rose-950/40 border-[#E0E4DD] dark:border-[#1E1E1E] text-rose-600 dark:text-rose-400 hover:border-rose-400 dark:hover:border-rose-500/40'
                                            "
                                            :title="role.is_system ? 'Peran sistem tidak dapat dihapus' : 'Hapus peran kustom'"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- FLOATING STICKY SAVE BAR                                          -->
        <!-- ================================================================= -->
        <div
            v-if="activeSubTab === 'matrix'"
            class="fixed bottom-4 left-4 right-4 lg:left-64 z-30 flex justify-center"
        >
            <div class="w-full max-w-4xl bg-white/95 dark:bg-[#111111]/95 backdrop-blur-md border border-[#171B17]/20 dark:border-[#00D084]/40 rounded-2xl p-4 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-3 ring-1 ring-[#171B17]/10 dark:ring-[#00D084]/20">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#00D084]/30 flex items-center justify-center text-[#171B17] dark:text-[#00D084]">
                        <Key class="w-4 h-4" />
                    </div>
                    <div class="text-xs">
                        <div class="font-extrabold text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <span>Peran: {{ currentRole?.name }}</span>
                            <span class="px-2 py-0.5 rounded font-mono font-bold bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#00D084]">
                                {{ (permissionMatrix[selectedRoleSlug] || []).length }}/39 Izin Aktif
                            </span>
                        </div>
                        <p class="text-[11px] text-[#737B71] dark:text-[#858585] mt-0.5">
                            {{ hasUnsavedChanges ? '⚠️ Terdapat perubahan izin yang belum disimpan ke database sistem.' : 'Konfigurasi izin tersinkronisasi.' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button
                        type="button"
                        @click="resetChanges"
                        :disabled="!hasUnsavedChanges"
                        class="h-9 px-3.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-white hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset</span>
                    </button>
                    <button
                        type="button"
                        @click="isConfirmSaveOpen = true"
                        :disabled="isSaving"
                        class="h-9 px-5 rounded-lg bg-[#171B17] hover:bg-[#282828] text-white dark:bg-[#00D084] dark:hover:bg-[#00F0A0] dark:text-[#062117] font-bold text-xs flex items-center gap-2 shadow-lg transition-all cursor-pointer disabled:opacity-60"
                    >
                        <Check class="w-4 h-4 stroke-[2.5]" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan Izin' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 1: KONFIRMASI SIMPAN PERUBAHAN MATRIKS                      -->
        <!-- ================================================================= -->
        <div
            v-if="isConfirmSaveOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="isConfirmSaveOpen = false"></div>
            <div class="relative bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#F4F5F2] dark:bg-[#00D084]/10 border border-[#E0E4DD] dark:border-[#00D084]/30 flex items-center justify-center text-[#171B17] dark:text-[#00D084] shrink-0">
                        <ShieldCheck class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Konfirmasi Simpan Hak Akses
                        </h3>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-1">
                            Anda akan memperbarui hak akses operasional untuk peran:
                        </p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-[#737B71] dark:text-[#858585] font-semibold">Nama Peran:</span>
                        <span class="font-bold text-[#171B17] dark:text-[#F5F5F5]">{{ currentRole?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#737B71] dark:text-[#858585] font-semibold">Slug:</span>
                        <span class="font-mono text-[#171B17] dark:text-[#00D084]">{{ currentRole?.slug }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#737B71] dark:text-[#858585] font-semibold">Total Izin Ditetapkan:</span>
                        <span class="font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]">
                            {{ (permissionMatrix[selectedRoleSlug] || []).length }} dari 39 Izin
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-[#737B71] dark:text-[#858585] leading-relaxed">
                    Perubahan izin akan langsung berdampak pada akun pengguna yang terhubung ke peran ini secara realtime.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isConfirmSaveOpen = false"
                        class="h-9 px-4 rounded-lg bg-white dark:bg-[#181818] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-white cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmSavePermissions"
                        :disabled="isSaving"
                        class="h-9 px-5 rounded-lg bg-[#171B17] hover:bg-[#282828] text-white dark:bg-[#00D084] dark:hover:bg-[#00F0A0] dark:text-[#062117] text-xs font-bold flex items-center gap-2 cursor-pointer disabled:opacity-60"
                    >
                        <Check class="w-4 h-4 stroke-[2.5]" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'Konfirmasi Simpan' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 2: TAMBAH ROLE BARU                                         -->
        <!-- ================================================================= -->
        <div
            v-if="isAddRoleOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="isAddRoleOpen = false"></div>
            <div class="relative bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#00D084] flex items-center justify-center">
                            <Plus class="w-4 h-4 stroke-[2.5]" />
                        </div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Tambah Peran Kustom Baru
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="isAddRoleOpen = false"
                        class="text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-[#444C43] dark:text-[#B3B3B3] mb-1">Nama Peran *</label>
                        <input
                            v-model="roleForm.name"
                            @input="handleNameInput"
                            type="text"
                            placeholder="Contoh: Petugas Lapangan Puskeswan"
                            class="w-full h-9 px-3 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71] dark:placeholder:text-[#858585] focus:outline-none focus:border-[#171B17] dark:focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#444C43] dark:text-[#B3B3B3] mb-1">Slug Identifier *</label>
                        <input
                            v-model="roleForm.slug"
                            type="text"
                            placeholder="petugas_puskeswan"
                            class="w-full h-9 px-3 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] font-mono text-[#171B17] dark:text-[#00D084] placeholder:text-[#737B71] dark:placeholder:text-[#858585] focus:outline-none focus:border-[#171B17] dark:focus:border-[#00D084]"
                        />
                        <span class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 block">
                            Hanya huruf kecil, angka, dan underscore (_).
                        </span>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#444C43] dark:text-[#B3B3B3] mb-1">Deskripsi Peran</label>
                        <textarea
                            v-model="roleForm.description"
                            rows="2"
                            placeholder="Deskripsi tugas dan batasan akses peran..."
                            class="w-full p-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71] dark:placeholder:text-[#858585] focus:outline-none focus:border-[#171B17] dark:focus:border-[#00D084]"
                        ></textarea>
                    </div>

                    <!-- Toggle Status Awal -->
                    <div class="p-3 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-[#171B17] dark:text-[#F5F5F5] block">Status Aktivasi</span>
                            <span class="text-[11px] text-[#737B71] dark:text-[#858585]">Aktifkan peran ini langsung di sistem</span>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="roleForm.is_active"
                            @click="roleForm.is_active = !roleForm.is_active"
                            class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200"
                            :class="
                                roleForm.is_active
                                    ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]'
                                    : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'
                            "
                        >
                            <span
                                class="absolute text-[9px] font-black tracking-wider select-none"
                                :class="roleForm.is_active ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                            >
                                {{ roleForm.is_active ? 'ON' : 'OFF' }}
                            </span>
                            <span
                                aria-hidden="true"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                :class="roleForm.is_active ? 'translate-x-8' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isAddRoleOpen = false"
                        class="h-9 px-4 rounded-lg bg-white dark:bg-[#181818] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-white cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitAddRole"
                        class="h-9 px-5 rounded-lg bg-[#171B17] hover:bg-[#282828] text-white dark:bg-[#00D084] dark:hover:bg-[#00F0A0] dark:text-[#062117] font-bold text-xs flex items-center gap-2 cursor-pointer"
                    >
                        <Plus class="w-4 h-4 stroke-[2.5]" />
                        <span>Buat Peran Baru</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 3: EDIT ROLE                                                -->
        <!-- ================================================================= -->
        <div
            v-if="isEditRoleOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="isEditRoleOpen = false"></div>
            <div class="relative bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#00D084] flex items-center justify-center">
                            <Edit class="w-4 h-4" />
                        </div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Edit Peran: {{ roleForm.name }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="isEditRoleOpen = false"
                        class="text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-[#444C43] dark:text-[#B3B3B3] mb-1">Nama Peran *</label>
                        <input
                            v-model="roleForm.name"
                            type="text"
                            class="w-full h-9 px-3 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71] dark:placeholder:text-[#858585] focus:outline-none focus:border-[#171B17] dark:focus:border-[#00D084]"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#737B71] dark:text-[#858585] mb-1">Slug (Identifier Permanen)</label>
                        <input
                            :value="roleForm.slug"
                            disabled
                            type="text"
                            class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#CBD2C8] dark:border-[#282828] font-mono text-[#737B71] dark:text-[#858585] cursor-not-allowed opacity-75"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-[#444C43] dark:text-[#B3B3B3] mb-1">Deskripsi Peran</label>
                        <textarea
                            v-model="roleForm.description"
                            rows="2"
                            class="w-full p-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71] dark:placeholder:text-[#858585] focus:outline-none focus:border-[#171B17] dark:focus:border-[#00D084]"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isEditRoleOpen = false"
                        class="h-9 px-4 rounded-lg bg-white dark:bg-[#181818] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-white cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitEditRole"
                        class="h-9 px-5 rounded-lg bg-[#171B17] hover:bg-[#282828] text-white dark:bg-[#00D084] dark:hover:bg-[#00F0A0] dark:text-[#062117] font-bold text-xs flex items-center gap-2 cursor-pointer"
                    >
                        <Check class="w-4 h-4 stroke-[2.5]" />
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 4: HAPUS ROLE                                               -->
        <!-- ================================================================= -->
        <div
            v-if="isDeleteRoleOpen && roleToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="isDeleteRoleOpen = false"></div>
            <div class="relative bg-white dark:bg-[#111111] border border-rose-500/40 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4 text-[#171B17] dark:text-[#F5F5F5]">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <Trash2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Hapus Peran Kustom?
                        </h3>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-1">
                            Anda akan menghapus peran <b class="text-rose-600 dark:text-rose-400 font-bold">{{ roleToDelete.name }}</b> ({{ roleToDelete.slug }}). Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isDeleteRoleOpen = false"
                        class="h-9 px-4 rounded-lg bg-white dark:bg-[#181818] hover:bg-[#E8EBE5] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-white cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteRole"
                        class="h-9 px-5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs flex items-center gap-2 cursor-pointer"
                    >
                        <Trash2 class="w-4 h-4" />
                        <span>Hapus Peran</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TOAST FEEDBACK NOTIFICATION                                       -->
        <!-- ================================================================= -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastMsg"
                class="fixed top-5 right-5 z-50 max-w-sm w-full p-4 rounded-xl shadow-2xl flex items-start gap-3 border text-xs font-semibold backdrop-blur-md"
                :class="[
                    toastType === 'success'
                        ? 'bg-white/95 dark:bg-[#111111]/95 border-[#171B17] dark:border-[#00D084] text-[#171B17] dark:text-[#F5F5F5]'
                        : toastType === 'warning'
                        ? 'bg-white/95 dark:bg-[#111111]/95 border-[#171B17] dark:border-amber-500 text-[#171B17] dark:text-amber-200'
                        : 'bg-white/95 dark:bg-[#111111]/95 border-rose-500 text-rose-700 dark:text-rose-200'
                ]"
            >
                <Check v-if="toastType === 'success'" class="w-4 h-4 text-[#171B17] dark:text-[#00D084] shrink-0 mt-0.5" />
                <AlertTriangle v-else-if="toastType === 'warning'" class="w-4 h-4 text-[#171B17] dark:text-amber-400 shrink-0 mt-0.5" />
                <X v-else class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                <div class="flex-1">{{ toastMsg }}</div>
                <button
                    type="button"
                    @click="toastMsg = null"
                    class="text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-white cursor-pointer ml-auto"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>
        </transition>
    </div>
</template>
