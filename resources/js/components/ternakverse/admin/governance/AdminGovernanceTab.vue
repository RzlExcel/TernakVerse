<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
    SlidersHorizontal,
    Activity,
    Database,
    Shield,
    FileText,
    Radio,
    CheckCircle2,
    AlertTriangle,
    RefreshCw,
    Trash2,
    Download,
    HardDrive,
    Terminal,
    Plus,
    Search,
    Lock,
    Check,
    X,
    Clock,
    Server,
    Globe,
    Mail,
    MessageSquare,
    Key,
    Eye,
    Copy,
    AlertCircle,
    ChevronLeft,
    ChevronRight,
    Map as MapIcon,
    Wrench,
    Save,
    RotateCcw,
} from 'lucide-vue-next';
import {
    useAdminGovernance,
    type ThematicInterval,
    type ConversionFactors,
    type BackupRecord,
    type SessionRecord,
    type IpRuleRecord,
    type AuditLogItem,
    type SystemLogItem,
    type IntegrationsConfig,
} from '../../../../composables/ternakverse/admin/useAdminGovernance';

const emit = defineEmits<{
    (e: 'showToast', message: string): void;
}>();

const {
    isLoading,
    actionLoading,
    thematicIntervals,
    conversionFactors,
    recalculatePreview,
    systemState,
    backups,
    queueStatus,
    sessions,
    ipRules,
    securityPolicies,
    auditLogs,
    auditLogMeta,
    systemLogs,
    systemHealth,
    integrations,
    // Methods
    fetchThematicIntervals,
    saveThematicIntervals,
    recalculateThematicIntervals,
    saveConversionFactors,
    fetchSystemState,
    updateMaintenanceMode,
    updateEmergencyBanner,
    updateFeatureFlags,
    clearCache,
    fetchBackups,
    createBackup,
    deleteBackup,
    restoreBackup,
    getBackupDownloadUrl,
    fetchQueueStatus,
    fetchSessions,
    terminateSession,
    terminateAllOtherSessions,
    fetchIpRules,
    createIpRule,
    deleteIpRule,
    updateSecurityPolicies,
    fetchAuditLogs,
    fetchSystemLogs,
    fetchSystemHealth,
    fetchIntegrations,
    updateIntegrations,
    testIntegration,
    initializeAll,
} = useAdminGovernance();

// -----------------------------------------------------------------------------
// SUBTAB STATE
// -----------------------------------------------------------------------------
export type GovernanceSubTab =
    | 'kebijakan'
    | 'ketersediaan'
    | 'ketahanan'
    | 'keamanan'
    | 'observabilitas'
    | 'integrasi';

const activeSubTab = ref<GovernanceSubTab>('kebijakan');

const SUBTABS = [
    { id: 'kebijakan' as const, label: 'Kebijakan & Interval', icon: SlidersHorizontal, desc: 'Ambang batas tematik & faktor konversi' },
    { id: 'ketersediaan' as const, label: 'Ketersediaan & Maintenance', icon: Wrench, desc: 'Mode pemeliharaan, banner, & cache' },
    { id: 'ketahanan' as const, label: 'Ketahanan & Backup', icon: Database, desc: 'Snapshot database & antrian job' },
    { id: 'keamanan' as const, label: 'Keamanan & Akses', icon: Shield, desc: 'Sesi aktif, firewall IP, & 2FA' },
    { id: 'observabilitas' as const, label: 'Observabilitas & Audit', icon: Activity, desc: 'Audit trail, error log, & diagnostik' },
    { id: 'integrasi' as const, label: 'Integrasi & Gateway', icon: Globe, desc: 'WhatsApp, SMTP, GeoPortal, & BPS' },
];

function triggerToast(message: string): void {
    emit('showToast', message);
}

// -----------------------------------------------------------------------------
// 1. KEBIJAKAN & INTERVAL TEMATIK LOGIC
// -----------------------------------------------------------------------------
const selectedKategori = ref<'populasi' | 'produksi' | 'penyakit' | 'fasilitas'>('populasi');
const isRecalculateModalOpen = ref<boolean>(false);
const recalculateMethod = ref<'jenks' | 'quantile'>('jenks');
const recalculateClasses = ref<number>(5);

// Baseline local copy for editing
const editedIntervals = ref<ThematicInterval[]>([]);
const localFactors = ref<ConversionFactors>({
    conversion_st_sapi: 1.0,
    conversion_st_kambing: 0.14,
    conversion_st_unggas: 0.01,
    hmt_multiplier: 10.0,
});

// Category options
const KATEGORI_OPTIONS = [
    { id: 'populasi' as const, label: 'Populasi Ternak', defaultSatuan: 'ekor' },
    { id: 'produksi' as const, label: 'Produksi Komoditas', defaultSatuan: 'kg' },
    { id: 'penyakit' as const, label: 'Kasus Penyakit & EWS', defaultSatuan: 'kasus' },
    { id: 'fasilitas' as const, label: 'Fasilitas & Kapasitas RPH', defaultSatuan: 'unit' },
];

// Active filtered intervals for selected category
const currentCategoryIntervals = computed<ThematicInterval[]>(() => {
    return editedIntervals.value.filter((item: ThematicInterval) => item.kategori === selectedKategori.value);
});

// Sync intervals from composable to local editable copy
function syncLocalIntervals(): void {
    if (thematicIntervals.value.length > 0) {
        editedIntervals.value = JSON.parse(JSON.stringify(thematicIntervals.value));
    } else {
        // Fallback default 5 tiers if table is empty
        const defaultTiers: ThematicInterval[] = [
            { id: 1, kategori: 'populasi', tier: 1, label: 'Sangat Rendah', min_value: 0, max_value: 100, color_hex: '#10B981', satuan: 'ekor', calculation_mode: 'manual', is_active: true },
            { id: 2, kategori: 'populasi', tier: 2, label: 'Rendah', min_value: 101, max_value: 500, color_hex: '#3B82F6', satuan: 'ekor', calculation_mode: 'manual', is_active: true },
            { id: 3, kategori: 'populasi', tier: 3, label: 'Sedang', min_value: 501, max_value: 1500, color_hex: '#F59E0B', satuan: 'ekor', calculation_mode: 'manual', is_active: true },
            { id: 4, kategori: 'populasi', tier: 4, label: 'Tinggi', min_value: 1501, max_value: 3500, color_hex: '#EF4444', satuan: 'ekor', calculation_mode: 'manual', is_active: true },
            { id: 5, kategori: 'populasi', tier: 5, label: 'Sangat Tinggi', min_value: 3501, max_value: null, color_hex: '#7C3AED', satuan: 'ekor', calculation_mode: 'manual', is_active: true },
        ];
        editedIntervals.value = defaultTiers;
    }
}

async function handleSaveIntervals(): Promise<void> {
    try {
        const msg = await saveThematicIntervals(editedIntervals.value);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menyimpan interval tematik.');
    }
}

async function handleRecalculate(apply: boolean): Promise<void> {
    try {
        const result = await recalculateThematicIntervals({
            kategori: selectedKategori.value,
            method: recalculateMethod.value,
            classes: recalculateClasses.value,
            apply,
        });

        if (apply) {
            syncLocalIntervals();
            isRecalculateModalOpen.value = false;
            triggerToast(`Ambang batas ${selectedKategori.value} berhasil dihitung dan diterapkan.`);
        } else {
            triggerToast(`Pratinjau ${result.tiers_count} kelas dihitung (${result.sample_size} sampel).`);
        }
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menghitung ulang interval tematik.');
    }
}

async function handleSaveFactors(): Promise<void> {
    try {
        const msg = await saveConversionFactors(localFactors.value);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menyimpan faktor konversi.');
    }
}

// -----------------------------------------------------------------------------
// 2. KETERSEDIAAN & PEMELIHARAAN LOGIC
// -----------------------------------------------------------------------------
const maintenanceForm = ref({
    active: false,
    message: '',
    scheduled_end: '',
});

const bannerForm = ref({
    enabled: false,
    message: '',
    level: 'info' as 'info' | 'warning' | 'danger' | 'alert',
});

const lastArtisanOutput = ref<string | null>(null);

function syncStateForms(): void {
    if (systemState.value) {
        maintenanceForm.value = {
            active: systemState.value.maintenance.active,
            message: systemState.value.maintenance.message || '',
            scheduled_end: systemState.value.maintenance.scheduled_end || '',
        };
        bannerForm.value = {
            enabled: systemState.value.emergency_banner.enabled,
            message: systemState.value.emergency_banner.message || '',
            level: systemState.value.emergency_banner.level || 'info',
        };
    }
}

async function handleUpdateMaintenance(): Promise<void> {
    try {
        const msg = await updateMaintenanceMode({
            enabled: maintenanceForm.value.active,
            message: maintenanceForm.value.message,
            scheduled_end: maintenanceForm.value.scheduled_end || null,
        });
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal memperbarui status pemeliharaan.');
    }
}

async function handleUpdateBanner(): Promise<void> {
    try {
        const msg = await updateEmergencyBanner({
            enabled: bannerForm.value.enabled,
            message: bannerForm.value.message,
            level: bannerForm.value.level,
        });
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal memperbarui banner pengumuman.');
    }
}

async function handleClearCache(target: 'all' | 'app' | 'config' | 'route' | 'query' | 'gis'): Promise<void> {
    try {
        const res = await clearCache(target);
        lastArtisanOutput.value = res.output || res.message;
        triggerToast(res.message);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal membersihkan cache.');
    }
}

async function handleToggleFeature(featureKey: string, currentValue: boolean): Promise<void> {
    try {
        const updated = {
            ...systemState.value.features,
            [featureKey]: !currentValue,
        };
        const msg = await updateFeatureFlags(updated);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal memperbarui feature flag.');
    }
}

// -----------------------------------------------------------------------------
// 3. KETAHANAN & BACKUP LOGIC
// -----------------------------------------------------------------------------
const backupNotes = ref<string>('');
const isRestoreModalOpen = ref<boolean>(false);
const restoreTarget = ref<BackupRecord | null>(null);
const restoreConfirmCode = ref<string>('');

async function handleCreateBackup(): Promise<void> {
    try {
        const res = await createBackup(backupNotes.value);
        backupNotes.value = '';
        triggerToast(res.message);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal membuat cadangan database.');
    }
}

async function handleDeleteBackup(id: number): Promise<void> {
    if (!confirm(`Apakah Anda yakin ingin menghapus arsip cadangan #${id}? File fisik akan dihapus dari server.`)) {
        return;
    }
    try {
        const msg = await deleteBackup(id);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menghapus cadangan.');
    }
}

function openRestoreModal(backup: BackupRecord): void {
    restoreTarget.value = backup;
    restoreConfirmCode.value = '';
    isRestoreModalOpen.value = true;
}

async function handleConfirmRestore(): Promise<void> {
    if (!restoreTarget.value) return;
    if (restoreConfirmCode.value !== 'RESTORE_DATABASE') {
        triggerToast('Kode konfirmasi tidak valid. Harap ketik "RESTORE_DATABASE".');
        return;
    }

    try {
        const msg = await restoreBackup(restoreTarget.value.id, restoreConfirmCode.value);
        isRestoreModalOpen.value = false;
        restoreTarget.value = null;
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal memulihkan database.');
    }
}

// -----------------------------------------------------------------------------
// 4. KEAMANAN & AKSES LOGIC
// -----------------------------------------------------------------------------
const isAddIpModalOpen = ref<boolean>(false);
const ipRuleForm = ref({
    ip_address: '',
    rule_type: 'whitelist' as 'whitelist' | 'blacklist',
    description: '',
    is_active: true,
});

async function handleTerminateSession(sessionId: string): Promise<void> {
    if (!confirm('Hentikan sesi pengguna ini secara paksa? Pengguna akan langsung ter-logout.')) {
        return;
    }
    try {
        const msg = await terminateSession(sessionId);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menghentikan sesi.');
    }
}

async function handleTerminateAllOtherSessions(): Promise<void> {
    if (!confirm('Hentikan semua sesi pengguna aktif lainnya di seluruh perangkat?')) {
        return;
    }
    try {
        const res = await terminateAllOtherSessions();
        triggerToast(res.message);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menghentikan sesi lainnya.');
    }
}

async function handleCreateIpRule(): Promise<void> {
    if (!ipRuleForm.value.ip_address.trim()) {
        triggerToast('Alamat IP atau rentang CIDR wajib diisi.');
        return;
    }
    try {
        const res = await createIpRule(ipRuleForm.value);
        isAddIpModalOpen.value = false;
        ipRuleForm.value = { ip_address: '', rule_type: 'whitelist', description: '', is_active: true };
        triggerToast(res.message);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menyimpan aturan IP.');
    }
}

async function handleDeleteIpRule(id: number): Promise<void> {
    if (!confirm('Hapus aturan IP ini?')) return;
    try {
        const msg = await deleteIpRule(id);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal menghapus aturan IP.');
    }
}

async function handleSaveSecurityPolicies(): Promise<void> {
    try {
        const msg = await updateSecurityPolicies(securityPolicies.value);
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal memperbarui kebijakan keamanan.');
    }
}

// -----------------------------------------------------------------------------
// 5. OBSERVABILITAS & AUDIT LOGIC
// -----------------------------------------------------------------------------
const auditSearch = ref<string>('');
const auditModule = ref<string>('');
const auditStartDate = ref<string>('');
const auditEndDate = ref<string>('');
const isAuditDetailModalOpen = ref<boolean>(false);
const selectedAuditLog = ref<AuditLogItem | null>(null);

const logLimit = ref<number>(100);
const logLevel = ref<string>('');
const logSearch = ref<string>('');
const isLogTraceModalOpen = ref<boolean>(false);
const selectedSystemLog = ref<SystemLogItem | null>(null);

async function handleSearchAuditLogs(page: number = 1): Promise<void> {
    try {
        await fetchAuditLogs({
            search: auditSearch.value || undefined,
            module: auditModule.value || undefined,
            start_date: auditStartDate.value || undefined,
            end_date: auditEndDate.value || undefined,
            page,
            per_page: 25,
        });
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal mengambil log audit.');
    }
}

function resetAuditFilter(): void {
    auditSearch.value = '';
    auditModule.value = '';
    auditStartDate.value = '';
    auditEndDate.value = '';
    handleSearchAuditLogs(1);
}

function openAuditDetail(item: AuditLogItem): void {
    selectedAuditLog.value = item;
    isAuditDetailModalOpen.value = true;
}

async function handleFetchSystemLogs(): Promise<void> {
    try {
        await fetchSystemLogs({
            limit: logLimit.value,
            level: logLevel.value || undefined,
            search: logSearch.value || undefined,
        });
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal mengambil log sistem.');
    }
}

function openLogTrace(item: SystemLogItem): void {
    selectedSystemLog.value = item;
    isLogTraceModalOpen.value = true;
}

function copyToClipboard(text: string): void {
    navigator.clipboard?.writeText(text);
    triggerToast('Teks berhasil disalin ke clipboard.');
}

// -----------------------------------------------------------------------------
// 6. INTEGRASI & GATEWAY LOGIC
// -----------------------------------------------------------------------------
const isEditIntegrationModalOpen = ref<boolean>(false);
const editingGateway = ref<'whatsapp' | 'smtp' | 'geoportal' | 'bps' | null>(null);
const integrationForm = ref<Record<string, unknown>>({});
const testResults = ref<Record<string, { connected: boolean; latency_ms: number; message: string }>>({});

function openEditIntegration(gateway: 'whatsapp' | 'smtp' | 'geoportal' | 'bps'): void {
    editingGateway.value = gateway;
    integrationForm.value = JSON.parse(JSON.stringify(integrations.value[gateway] || {}));
    isEditIntegrationModalOpen.value = true;
}

async function handleSaveIntegration(): Promise<void> {
    if (!editingGateway.value) return;
    try {
        const payload = {
            [editingGateway.value]: integrationForm.value,
        };
        const msg = await updateIntegrations(payload);
        isEditIntegrationModalOpen.value = false;
        triggerToast(msg);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : 'Gagal memperbarui konfigurasi integrasi.');
    }
}

async function handleTestIntegration(gateway: 'whatsapp' | 'smtp' | 'geoportal' | 'bps'): Promise<void> {
    try {
        const res = await testIntegration(gateway);
        testResults.value = {
            ...testResults.value,
            [gateway]: {
                connected: res.connected,
                latency_ms: res.latency_ms,
                message: res.message,
            },
        };
        triggerToast(res.message);
    } catch (e: unknown) {
        triggerToast(e instanceof Error ? e.message : `Gagal menguji koneksi ${gateway}.`);
    }
}

// -----------------------------------------------------------------------------
// LIFECYCLE INITIALIZATION
// -----------------------------------------------------------------------------
onMounted(async () => {
    await initializeAll();
    syncLocalIntervals();
    syncStateForms();
    if (conversionFactors.value) {
        localFactors.value = { ...conversionFactors.value };
    }
});
</script>

<template>
    <div class="space-y-6 pb-16 font-ui">
        <!-- =================================================================== -->
        <!-- HEADER GOVERNANCE & COMMAND CENTER                                 -->
        <!-- =================================================================== -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-[#00D084] shadow-xs shrink-0 mt-0.5">
                    <Shield class="w-5 h-5 text-[#00D084]" />
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="font-display font-extrabold text-2xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                            Tata Kelola Sistem &amp; Administrasi Operasional
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#00D084]/15 border border-[#00D084]/30 text-[#00D084]">
                            SUPERADMIN ONLY
                        </span>
                    </div>
                    <p class="text-xs text-[#737B71] dark:text-[#858585] mt-1">
                        Pusat orkestrasi 6 domain tata kelola: interval tematik, pemeliharaan server, pencadangan basis data, firewall IP, observabilitas audit, dan integrasi API.
                    </p>
                </div>
            </div>

            <!-- Global Refresh Action -->
            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="initializeAll"
                    :disabled="isLoading"
                    class="h-9 px-4 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#181818] hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2 cursor-pointer transition-all shadow-xs disabled:opacity-50"
                    title="Segarkan seluruh status tata kelola backend"
                >
                    <RefreshCw class="w-3.5 h-3.5 text-[#00D084]" :class="{ 'animate-spin': isLoading }" />
                    <span>{{ isLoading ? 'Menyinkronkan...' : 'Segarkan Tata Kelola' }}</span>
                </button>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 6 MODULAR SUBTABS NAVIGATOR (FOREST INTELLIGENCE PILL BAR)          -->
        <!-- =================================================================== -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
            <button
                v-for="tab in SUBTABS"
                :key="tab.id"
                type="button"
                @click="activeSubTab = tab.id"
                class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2.5 shrink-0 transition-all cursor-pointer border"
                :class="[
                    activeSubTab === tab.id
                        ? 'bg-[#171B17] text-white border-[#171B17] dark:bg-[#181818] dark:border-[#00D084] dark:text-[#00D084] shadow-sm ring-1 ring-[#00D084]/20'
                        : 'bg-white border-[#E0E4DD] text-[#737B71] hover:text-[#171B17] hover:bg-[#EDEFEB] dark:bg-[#111111] dark:border-[#1E1E1E] dark:text-[#858585] dark:hover:text-[#F5F5F5] dark:hover:bg-[#181818]'
                ]"
            >
                <component :is="tab.icon" class="w-4 h-4 stroke-[2]" />
                <span class="font-bold">{{ tab.label }}</span>
            </button>
        </div>

        <!-- =================================================================== -->
        <!-- 1. SUBTAB: KEBIJAKAN & INTERVAL TEMATIK                              -->
        <!-- =================================================================== -->
        <div v-if="activeSubTab === 'kebijakan'" class="space-y-6">
            <!-- Header Actions & Kategori Selector -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <SlidersHorizontal class="w-4 h-4 text-[#00D084]" />
                            <span>Klasifikasi Interval Tematik Choropleth WebGIS (5 Tier)</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Menetapkan batas bawah, batas atas, label zonasi, dan palet warna representasi visual peta 27 kecamatan.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isRecalculateModalOpen = true"
                            class="h-8 px-3.5 rounded-lg border border-[#00D084]/40 bg-[#00D084]/10 text-[#00D084] hover:bg-[#00D084]/20 font-semibold text-xs flex items-center gap-1.5 cursor-pointer transition-colors"
                        >
                            <Activity class="w-3.5 h-3.5" />
                            <span>Hitung Ulang Statistik</span>
                        </button>

                        <button
                            type="button"
                            @click="handleSaveIntervals"
                            :disabled="actionLoading['save_intervals']"
                            class="h-8 px-4 rounded-lg bg-[#00D084] text-[#062117] hover:bg-[#00B98B] font-bold text-xs flex items-center gap-1.5 cursor-pointer transition-colors shadow-xs disabled:opacity-50"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ actionLoading['save_intervals'] ? 'Menyimpan...' : 'Simpan Interval' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Kategori Selector Tabs -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="kat in KATEGORI_OPTIONS"
                        :key="kat.id"
                        type="button"
                        @click="selectedKategori = kat.id"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer transition-all border"
                        :class="[
                            selectedKategori === kat.id
                                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-[#00D084] font-bold'
                                : 'bg-[#F4F5F2] dark:bg-[#181818] border-[#E0E4DD] dark:border-[#242424] text-[#737B71] dark:text-[#858585] hover:text-[#171B17] dark:hover:text-[#F5F5F5]'
                        ]"
                    >
                        {{ kat.label }}
                    </button>
                </div>

                <!-- Tiers Table -->
                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] uppercase text-[10px] tracking-wider font-semibold border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center w-16">Tier</th>
                                <th scope="col" class="px-4 py-3">Label Zonasi</th>
                                <th scope="col" class="px-4 py-3">Nilai Minimum</th>
                                <th scope="col" class="px-4 py-3">Nilai Maksimum</th>
                                <th scope="col" class="px-4 py-3">Warna Hex</th>
                                <th scope="col" class="px-4 py-3">Satuan</th>
                                <th scope="col" class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="(tier, idx) in currentCategoryIntervals"
                                :key="tier.tier || idx"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 text-center font-mono font-bold">
                                    <span class="w-6 h-6 rounded-md bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] inline-flex items-center justify-center">
                                        {{ tier.tier }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="tier.label"
                                        type="text"
                                        class="h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] font-semibold w-40"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model.number="tier.min_value"
                                        type="number"
                                        step="any"
                                        class="h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono text-[#171B17] dark:text-[#F5F5F5] w-28"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model.number="tier.max_value"
                                        type="number"
                                        step="any"
                                        placeholder="Tak Hingga"
                                        class="h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono text-[#171B17] dark:text-[#F5F5F5] w-28 placeholder:text-[10px]"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="tier.color_hex"
                                            type="color"
                                            class="w-7 h-7 rounded border border-[#E0E4DD] dark:border-[#242424] cursor-pointer bg-transparent"
                                        />
                                        <input
                                            v-model="tier.color_hex"
                                            type="text"
                                            class="h-8 px-2 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono text-[#171B17] dark:text-[#F5F5F5] w-24 uppercase"
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="tier.satuan"
                                        type="text"
                                        class="h-8 px-2 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] w-20"
                                    />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="tier.is_active"
                                        :aria-label="`Sakelar aktif tier ${tier.label}`"
                                        class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                                        :class="tier.is_active ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]' : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'"
                                        @click="tier.is_active = !tier.is_active"
                                    >
                                        <span
                                            class="absolute text-[9px] font-black tracking-wider transition-all duration-200"
                                            :class="tier.is_active ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                                        >
                                            {{ tier.is_active ? 'ON' : 'OFF' }}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                            :class="tier.is_active ? 'translate-x-8' : 'translate-x-0'"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full"
                                                :class="tier.is_active ? 'bg-[#00D084]' : 'bg-slate-400'"
                                            ></span>
                                        </span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Faktor Konversi Satuan Ternak (ST) & HMT -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Activity class="w-4 h-4 text-[#06B6D4]" />
                            <span>Faktor Konversi Satuan Ternak (ST) &amp; Daya Dukung Hijauan (HMT)</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Standar bobot konversi Kementerian Pertanian untuk menghitung kapasitas daya dukung wilayah Banyumas.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="handleSaveFactors"
                        :disabled="actionLoading['save_conversion_factors']"
                        class="h-8 px-3.5 rounded-lg bg-[#06B6D4] text-slate-900 font-bold text-xs flex items-center gap-1.5 hover:bg-[#0891B2] cursor-pointer transition-colors shadow-xs disabled:opacity-50"
                    >
                        <Save class="w-3.5 h-3.5" />
                        <span>{{ actionLoading['save_conversion_factors'] ? 'Menyimpan...' : 'Simpan Faktor Konversi' }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Sapi Potong &amp; Perah (ST)</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="localFactors.conversion_st_sapi"
                                type="number"
                                step="0.05"
                                class="w-full h-8 px-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]"
                            />
                            <span class="text-xs text-[#737B71] dark:text-[#858585] font-semibold">ST</span>
                        </div>
                        <p class="text-[10px] text-[#737B71] dark:text-[#858585]">Standar: 1.0 ST per ekor dewasa</p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Kambing &amp; Domba (ST)</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="localFactors.conversion_st_kambing"
                                type="number"
                                step="0.01"
                                class="w-full h-8 px-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]"
                            />
                            <span class="text-xs text-[#737B71] dark:text-[#858585] font-semibold">ST</span>
                        </div>
                        <p class="text-[10px] text-[#737B71] dark:text-[#858585]">Standar: 0.14 ST per ekor</p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Unggas &amp; Ayam (ST)</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="localFactors.conversion_st_unggas"
                                type="number"
                                step="0.001"
                                class="w-full h-8 px-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]"
                            />
                            <span class="text-xs text-[#737B71] dark:text-[#858585] font-semibold">ST</span>
                        </div>
                        <p class="text-[10px] text-[#737B71] dark:text-[#858585]">Standar: 0.01 ST per ekor</p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Kebutuhan Pakan HMT / ST</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="localFactors.hmt_multiplier"
                                type="number"
                                step="0.5"
                                class="w-full h-8 px-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]"
                            />
                            <span class="text-xs text-[#737B71] dark:text-[#858585] font-semibold">Ton/Thn</span>
                        </div>
                        <p class="text-[10px] text-[#737B71] dark:text-[#858585]">Standar: 10.0 Ton HMT / ST / Thn</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 2. SUBTAB: KETERSEDIAAN & PEMELIHARAAN                               -->
        <!-- =================================================================== -->
        <div v-else-if="activeSubTab === 'ketersediaan'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Maintenance Mode Card -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <div class="flex items-center gap-2">
                            <Wrench class="w-4 h-4 text-amber-500" />
                            <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                Saklar Mode Pemeliharaan Sistem
                            </h2>
                        </div>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                            :class="maintenanceForm.active ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-[#00D084] border border-emerald-500/20'"
                        >
                            {{ maintenanceForm.active ? 'MAINTENANCE AKTIF' : 'SISTEM ONLINE' }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424]">
                            <div>
                                <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5]">Kunci Akses Publik &amp; Dinas</div>
                                <div class="text-[10px] text-[#737B71] dark:text-[#858585]">Hanya Super Administrator yang dapat login saat aktif</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="maintenanceForm.active"
                                aria-label="Sakelar Kunci Akses Pemeliharaan"
                                class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                                :class="maintenanceForm.active ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]' : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'"
                                @click="maintenanceForm.active = !maintenanceForm.active"
                            >
                                <span
                                    class="absolute text-[9px] font-black tracking-wider transition-all duration-200"
                                    :class="maintenanceForm.active ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                                >
                                    {{ maintenanceForm.active ? 'ON' : 'OFF' }}
                                </span>
                                <span
                                    aria-hidden="true"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                    :class="maintenanceForm.active ? 'translate-x-8' : 'translate-x-0'"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="maintenanceForm.active ? 'bg-[#00D084]' : 'bg-slate-400'"
                                    ></span>
                                </span>
                            </button>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Pesan Pengumuman Pemeliharaan</label>
                            <textarea
                                v-model="maintenanceForm.message"
                                rows="3"
                                class="w-full p-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71]"
                                placeholder="Tuliskan pesan yang ditampilkan kepada pengunjung..."
                            ></textarea>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Perkiraan Waktu Selesai (Opsional)</label>
                            <input
                                v-model="maintenanceForm.scheduled_end"
                                type="datetime-local"
                                class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono text-[#171B17] dark:text-[#F5F5F5]"
                            />
                        </div>

                        <button
                            type="button"
                            @click="handleUpdateMaintenance"
                            :disabled="actionLoading['update_maintenance']"
                            class="w-full h-8 rounded-lg bg-amber-500 text-slate-900 font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-amber-600 cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ actionLoading['update_maintenance'] ? 'Menyimpan...' : 'Perbarui Status Pemeliharaan' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Emergency Broadcast Banner Card -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <div class="flex items-center gap-2">
                            <Radio class="w-4 h-4 text-rose-500" />
                            <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                Banner Siaran Darurat WebGIS
                            </h2>
                        </div>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                            :class="bannerForm.enabled ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : 'bg-slate-500/10 text-slate-400 border border-slate-500/20'"
                        >
                            {{ bannerForm.enabled ? 'SIARAN TAYANG' : 'NONAKTIF' }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424]">
                            <div>
                                <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5]">Aktifkan Banner Header</div>
                                <div class="text-[10px] text-[#737B71] dark:text-[#858585]">Muncul di atas header portal publik dan internal</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="bannerForm.enabled"
                                aria-label="Sakelar Banner Siaran Darurat"
                                class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                                :class="bannerForm.enabled ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]' : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'"
                                @click="bannerForm.enabled = !bannerForm.enabled"
                            >
                                <span
                                    class="absolute text-[9px] font-black tracking-wider transition-all duration-200"
                                    :class="bannerForm.enabled ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                                >
                                    {{ bannerForm.enabled ? 'ON' : 'OFF' }}
                                </span>
                                <span
                                    aria-hidden="true"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                    :class="bannerForm.enabled ? 'translate-x-8' : 'translate-x-0'"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="bannerForm.enabled ? 'bg-[#00D084]' : 'bg-slate-400'"
                                    ></span>
                                </span>
                            </button>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Tingkat Keparahan (Severity Level)</label>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="lvl in ['info', 'warning', 'danger', 'alert']"
                                    :key="lvl"
                                    type="button"
                                    @click="bannerForm.level = lvl as any"
                                    class="py-1.5 rounded-lg text-xs font-bold uppercase transition-all border cursor-pointer text-center"
                                    :class="bannerForm.level === lvl ? 'bg-[#171B17] text-white dark:bg-[#242424] border-[#00D084] text-[#00D084]' : 'bg-[#F4F5F2] dark:bg-[#181818] border-[#E0E4DD] dark:border-[#242424] text-[#737B71]'"
                                >
                                    {{ lvl }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Isi Teks Pengumuman</label>
                            <textarea
                                v-model="bannerForm.message"
                                rows="3"
                                class="w-full p-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#737B71]"
                                placeholder="Contoh: Peringatan Wabah PMK di Kec. Cilongok! Pembatasan lalu lintas ternak diberlakukan."
                            ></textarea>
                        </div>

                        <button
                            type="button"
                            @click="handleUpdateBanner"
                            :disabled="actionLoading['update_banner']"
                            class="w-full h-8 rounded-lg bg-rose-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-rose-600 cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ actionLoading['update_banner'] ? 'Menyimpan...' : 'Perbarui Banner Darurat' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Granular Cache Purging (6 Action Grid) -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div>
                    <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                        <Trash2 class="w-4 h-4 text-amber-500" />
                        <span>Pembersihan Cache Granular Arsitektur Sistem</span>
                    </h2>
                    <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                        Bersihkan lapisan cache spesifik tanpa menghapus seluruh state cache yang masih relevan.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- 1. All -->
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5]">Optimasi Menyeluruh (All)</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 leading-snug">
                                Eksekusi artisan optimize:clear dan flush seluruh store Redis/File.
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleClearCache('all')"
                            :disabled="actionLoading['clear_cache_all']"
                            class="h-8 px-3 rounded-lg bg-amber-500 text-slate-900 font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-amber-600 cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': actionLoading['clear_cache_all'] }" />
                            <span>{{ actionLoading['clear_cache_all'] ? 'Membersihkan...' : 'Bersihkan Semua' }}</span>
                        </button>
                    </div>

                    <!-- 2. GIS -->
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5]">Dataset Geospasial (GIS)</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 leading-snug">
                                Hapus cache GeoJSON batas wilayah 27 kecamatan dan agregasi choropleth.
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleClearCache('gis')"
                            :disabled="actionLoading['clear_cache_gis']"
                            class="h-8 px-3 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-[#00B98B] cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': actionLoading['clear_cache_gis'] }" />
                            <span>{{ actionLoading['clear_cache_gis'] ? 'Membersihkan...' : 'Bersihkan GIS' }}</span>
                        </button>
                    </div>

                    <!-- 3. Query -->
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5]">Cache Query &amp; Agregasi</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 leading-snug">
                                Hapus cache rekapitulasi data daya dukung, populasi, dan produksi dinas.
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleClearCache('query')"
                            :disabled="actionLoading['clear_cache_query']"
                            class="h-8 px-3 rounded-lg bg-[#06B6D4] text-slate-900 font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-[#0891B2] cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': actionLoading['clear_cache_query'] }" />
                            <span>{{ actionLoading['clear_cache_query'] ? 'Membersihkan...' : 'Bersihkan Query' }}</span>
                        </button>
                    </div>

                    <!-- 4. Route -->
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5]">Cache Rute (Route Cache)</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 leading-snug">
                                Eksekusi artisan route:clear untuk memulihkan rute web/API terbaru.
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleClearCache('route')"
                            :disabled="actionLoading['clear_cache_route']"
                            class="h-8 px-3 rounded-lg bg-slate-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-slate-600 cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': actionLoading['clear_cache_route'] }" />
                            <span>Bersihkan Rute</span>
                        </button>
                    </div>

                    <!-- 5. Config -->
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5]">Cache Konfigurasi (Config)</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 leading-snug">
                                Eksekusi artisan config:clear untuk membaca file .env secara langsung.
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleClearCache('config')"
                            :disabled="actionLoading['clear_cache_config']"
                            class="h-8 px-3 rounded-lg bg-slate-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-slate-600 cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': actionLoading['clear_cache_config'] }" />
                            <span>Bersihkan Config</span>
                        </button>
                    </div>

                    <!-- 6. App / View -->
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5]">Cache View &amp; Aplikasi</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 leading-snug">
                                Eksekusi artisan cache:clear untuk mereset seluruh template blade dan sesi transient.
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleClearCache('app')"
                            :disabled="actionLoading['clear_cache_app']"
                            class="h-8 px-3 rounded-lg bg-slate-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-slate-600 cursor-pointer transition-colors disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': actionLoading['clear_cache_app'] }" />
                            <span>Bersihkan App</span>
                        </button>
                    </div>
                </div>

                <!-- Console Output Viewer -->
                <div v-if="lastArtisanOutput" class="p-3.5 rounded-xl bg-[#111111] text-[#00D084] font-mono text-[11px] border border-[#242424] space-y-1">
                    <div class="flex items-center justify-between text-[#858585] text-[10px] pb-1 border-b border-[#242424]">
                        <div class="flex items-center gap-1.5">
                            <Terminal class="w-3.5 h-3.5 text-[#00D084]" />
                            <span>Artisan CLI Output</span>
                        </div>
                        <button type="button" @click="lastArtisanOutput = null" class="hover:text-white cursor-pointer">Tutup</button>
                    </div>
                    <pre class="whitespace-pre-wrap leading-relaxed">{{ lastArtisanOutput }}</pre>
                </div>
            </div>

            <!-- Feature Flags Switchboard -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div>
                    <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                        <Key class="w-4 h-4 text-[#00D084]" />
                        <span>Papan Kendali Fitur Sistem (Feature Flags)</span>
                    </h2>
                    <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                        Aktifkan atau nonaktifkan fitur spesifik tanpa perlu deployment ulang kode server.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                        v-for="(val, key) in systemState.features"
                        :key="key"
                        class="p-3.5 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex items-center justify-between"
                    >
                        <div>
                            <div class="font-mono text-xs font-bold text-[#171B17] dark:text-[#F5F5F5]">{{ key }}</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585]">
                                Status: <span :class="val ? 'text-[#00D084] font-semibold' : 'text-slate-400'">{{ val ? 'ENABLED' : 'DISABLED' }}</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="Boolean(val)"
                            :aria-label="`Sakelar fitur ${String(key)}`"
                            class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                            :class="Boolean(val) ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]' : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'"
                            @click="handleToggleFeature(String(key), Boolean(val))"
                        >
                            <span
                                class="absolute text-[9px] font-black tracking-wider transition-all duration-200"
                                :class="Boolean(val) ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                            >
                                {{ Boolean(val) ? 'ON' : 'OFF' }}
                            </span>
                            <span
                                aria-hidden="true"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                :class="Boolean(val) ? 'translate-x-8' : 'translate-x-0'"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="Boolean(val) ? 'bg-[#00D084]' : 'bg-slate-400'"
                                ></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 3. SUBTAB: KETAHANAN & BACKUP                                       -->
        <!-- =================================================================== -->
        <div v-else-if="activeSubTab === 'ketahanan'" class="space-y-6">
            <!-- Create Backup Card -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Database class="w-4 h-4 text-[#00D084]" />
                            <span>Pencadangan Basis Data PostGIS (Real Snapshot Dump)</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Menghasilkan arsip terkompresi SQL dump skema PostgreSQL/PostGIS, tabel master, dan audit log.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            v-model="backupNotes"
                            type="text"
                            placeholder="Catatan snapshot (opsional)..."
                            class="h-8 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5] w-64"
                        />
                        <button
                            type="button"
                            @click="handleCreateBackup"
                            :disabled="actionLoading['create_backup']"
                            class="h-8 px-4 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs flex items-center gap-1.5 hover:bg-[#00B98B] cursor-pointer transition-colors shadow-xs disabled:opacity-50"
                        >
                            <Download class="w-3.5 h-3.5" :class="{ 'animate-bounce': actionLoading['create_backup'] }" />
                            <span>{{ actionLoading['create_backup'] ? 'Memproses Snapshot...' : 'Buat Snapshot Sekarang' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Backup Catalog Table -->
                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] uppercase text-[10px] tracking-wider font-semibold border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th scope="col" class="px-4 py-3">Nama Berkas Snapshot</th>
                                <th scope="col" class="px-4 py-3">Waktu Pembuatan</th>
                                <th scope="col" class="px-4 py-3">Ukuran</th>
                                <th scope="col" class="px-4 py-3">Checksum SHA-256</th>
                                <th scope="col" class="px-4 py-3">Dibuat Oleh</th>
                                <th scope="col" class="px-4 py-3 text-center">Aksi Operasional</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="b in backups"
                                :key="b.id"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-emerald-600 dark:text-[#00D084] flex items-center gap-2">
                                    <HardDrive class="w-3.5 h-3.5 text-slate-400" />
                                    <span>{{ b.filename }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-[#737B71] dark:text-[#858585]">
                                    {{ b.created_at }}
                                </td>
                                <td class="px-4 py-3 font-mono">
                                    {{ b.size_formatted }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-[#737B71] dark:text-[#858585]">
                                    <span :title="b.checksum">{{ b.checksum.substring(0, 16) }}...</span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ b.created_by_name || 'System Auto' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a
                                            :href="getBackupDownloadUrl(b.id)"
                                            class="h-7 px-2.5 rounded-md bg-slate-700 hover:bg-slate-600 text-white font-semibold text-[11px] inline-flex items-center gap-1"
                                            title="Unduh berkas .sql.gz"
                                        >
                                            <Download class="w-3 h-3" />
                                            <span>Unduh</span>
                                        </a>

                                        <button
                                            type="button"
                                            @click="openRestoreModal(b)"
                                            class="h-7 px-2.5 rounded-md bg-amber-500/10 border border-amber-500/30 text-amber-500 hover:bg-amber-500/20 font-semibold text-[11px] inline-flex items-center gap-1 cursor-pointer"
                                        >
                                            <RotateCcw class="w-3 h-3" />
                                            <span>Pulihkan</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="handleDeleteBackup(b.id)"
                                            class="h-7 px-2 rounded-md bg-rose-500/10 border border-rose-500/30 text-rose-500 hover:bg-rose-500/20 font-semibold text-[11px] inline-flex items-center justify-center cursor-pointer"
                                            title="Hapus cadangan"
                                        >
                                            <Trash2 class="w-3 h-3" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="backups.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-[#737B71] dark:text-[#858585]">
                                    Belum ada arsip cadangan database. Klik tombol "Buat Snapshot Sekarang" di atas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Queue & Background Jobs Status -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2">
                        <Server class="w-4 h-4 text-purple-500" />
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Status Antrian &amp; Pekerjaan Latar Belakang (Queue Worker)
                        </h2>
                    </div>

                    <button
                        type="button"
                        @click="fetchQueueStatus"
                        class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center gap-1.5 cursor-pointer"
                    >
                        <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': actionLoading['fetch_queue'] }" />
                        <span>Segarkan Antrian</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-1">
                        <span class="text-[10px] text-[#737B71] dark:text-[#858585] uppercase tracking-wider font-semibold">Driver Antrian</span>
                        <div class="font-mono font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">{{ queueStatus.driver.toUpperCase() }}</div>
                    </div>
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-1">
                        <span class="text-[10px] text-[#737B71] dark:text-[#858585] uppercase tracking-wider font-semibold">Pekerjaan Tertunda (Pending)</span>
                        <div class="font-mono font-bold text-base text-blue-500">{{ queueStatus.pending_jobs }}</div>
                    </div>
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-1">
                        <span class="text-[10px] text-[#737B71] dark:text-[#858585] uppercase tracking-wider font-semibold">Pekerjaan Gagal (Failed)</span>
                        <div class="font-mono font-bold text-base" :class="queueStatus.failed_jobs > 0 ? 'text-rose-500' : 'text-[#00D084]'">
                            {{ queueStatus.failed_jobs }}
                        </div>
                    </div>
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-1">
                        <span class="text-[10px] text-[#737B71] dark:text-[#858585] uppercase tracking-wider font-semibold">Status Kesehatan Worker</span>
                        <div class="font-bold text-sm" :class="queueStatus.status === 'healthy' ? 'text-[#00D084]' : 'text-amber-500'">
                            {{ queueStatus.status === 'healthy' ? 'SEHAT (OPERASIONAL)' : 'PERLU PERHATIAN' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 4. SUBTAB: KEAMANAN & AKSES                                         -->
        <!-- =================================================================== -->
        <div v-else-if="activeSubTab === 'keamanan'" class="space-y-6">
            <!-- Active Sessions Card -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Key class="w-4 h-4 text-[#00D084]" />
                            <span>Sesi Login Pengguna Aktif &amp; Kontrol Perangkat</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Pantau sesi aktif administrator dan petugas dinas, deteksi akses anomali, dan hentikan sesi secara paksa.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="handleTerminateAllOtherSessions"
                            :disabled="actionLoading['terminate_other_sessions']"
                            class="h-8 px-3.5 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-500 hover:bg-rose-500/20 font-bold text-xs flex items-center gap-1.5 cursor-pointer transition-colors"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                            <span>Hentikan Semua Sesi Lain</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] uppercase text-[10px] tracking-wider font-semibold border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th scope="col" class="px-4 py-3">Pengguna</th>
                                <th scope="col" class="px-4 py-3">Alamat IP</th>
                                <th scope="col" class="px-4 py-3">Perangkat / Browser</th>
                                <th scope="col" class="px-4 py-3">Aktivitas Terakhir</th>
                                <th scope="col" class="px-4 py-3 text-center">Status</th>
                                <th scope="col" class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="s in sessions"
                                :key="s.id"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-semibold">
                                    {{ s.user_name || 'Petugas' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-blue-500">
                                    {{ s.ip_address }}
                                </td>
                                <td class="px-4 py-3 text-[#737B71] dark:text-[#858585] max-w-xs truncate">
                                    {{ s.device || s.user_agent }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    {{ s.last_activity_formatted }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="s.is_current"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-[#00D084] border border-emerald-500/20"
                                    >
                                        SESI INI
                                    </span>
                                    <span v-else class="text-[10px] text-slate-400">Aktif</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        v-if="!s.is_current"
                                        type="button"
                                        @click="handleTerminateSession(s.id)"
                                        class="h-6 px-2 rounded bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 font-semibold text-[10px] cursor-pointer"
                                    >
                                        Putuskan
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- IP Access Rules & Firewall -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Shield class="w-4 h-4 text-[#06B6D4]" />
                            <span>Aturan Firewall Akses IP (Whitelist &amp; Blacklist)</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Batasi alamat IP dinas yang berhak mengakses endpoint sensitif atau blokir IP anomali.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="isAddIpModalOpen = true"
                        class="h-8 px-3.5 rounded-lg bg-[#06B6D4] text-slate-900 font-bold text-xs flex items-center gap-1.5 hover:bg-[#0891B2] cursor-pointer transition-colors shadow-xs"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Tambah Aturan IP</span>
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] uppercase text-[10px] tracking-wider font-semibold border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th scope="col" class="px-4 py-3">Alamat IP / CIDR</th>
                                <th scope="col" class="px-4 py-3">Tipe Aturan</th>
                                <th scope="col" class="px-4 py-3">Deskripsi / Pemilik</th>
                                <th scope="col" class="px-4 py-3 text-center">Status</th>
                                <th scope="col" class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="rule in ipRules"
                                :key="rule.id"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-mono font-bold">
                                    {{ rule.ip_address }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="rule.rule_type === 'whitelist' || rule.rule_type === 'allow' ? 'bg-emerald-500/10 text-emerald-600 dark:text-[#00D084] border border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'"
                                    >
                                        {{ rule.rule_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[#737B71] dark:text-[#858585]">
                                    {{ rule.description || 'Tidak ada deskripsi' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="rule.is_active ? 'text-[#00D084]' : 'text-slate-400'">
                                        {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="handleDeleteIpRule(rule.id)"
                                        class="h-6 px-2 rounded bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 font-semibold text-[10px] cursor-pointer"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="ipRules.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-xs text-[#737B71] dark:text-[#858585]">
                                    Belum ada aturan IP firewall kustom yang terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Global Security Policies Settings -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Lock class="w-4 h-4 text-amber-500" />
                            <span>Parameter Kebijakan Otentikasi &amp; Keamanan Sesi</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Ambang batas proteksi brute-force, masa berlaku token, dan penegakan 2FA.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="handleSaveSecurityPolicies"
                        :disabled="actionLoading['update_security_policies']"
                        class="h-8 px-4 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs flex items-center gap-1.5 hover:bg-[#00B98B] cursor-pointer transition-colors shadow-xs disabled:opacity-50"
                    >
                        <Save class="w-3.5 h-3.5" />
                        <span>{{ actionLoading['update_security_policies'] ? 'Menyimpan...' : 'Simpan Kebijakan' }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Batas Percobaan Login Maksimum</label>
                        <input
                            v-model.number="securityPolicies.max_login_attempts"
                            type="number"
                            class="w-full h-8 px-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]"
                        />
                        <p class="text-[10px] text-[#737B71] dark:text-[#858585]">Akun dikunci sementara jika gagal melebihi batas ini.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <label class="text-[11px] font-bold text-[#171B17] dark:text-[#F5F5F5]">Batas Waktu Sesi Tidak Aktif (Menit)</label>
                        <input
                            v-model.number="securityPolicies.session_lifetime_minutes"
                            type="number"
                            class="w-full h-8 px-2.5 rounded-lg bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono font-bold text-[#171B17] dark:text-[#F5F5F5]"
                        />
                        <p class="text-[10px] text-[#737B71] dark:text-[#858585]">Otomatis logout saat tidak ada aktivitas pengguna.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5]">Wajibkan 2FA Staf Dinas</div>
                            <div class="text-[10px] text-[#737B71] dark:text-[#858585]">Wajibkan OTP dua faktor saat login</div>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="securityPolicies.two_factor_required"
                            aria-label="Sakelar Wajibkan 2FA Staf Dinas"
                            class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                            :class="securityPolicies.two_factor_required ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]' : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'"
                            @click="securityPolicies.two_factor_required = !securityPolicies.two_factor_required"
                        >
                            <span
                                class="absolute text-[9px] font-black tracking-wider transition-all duration-200"
                                :class="securityPolicies.two_factor_required ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                            >
                                {{ securityPolicies.two_factor_required ? 'ON' : 'OFF' }}
                            </span>
                            <span
                                aria-hidden="true"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                :class="securityPolicies.two_factor_required ? 'translate-x-8' : 'translate-x-0'"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="securityPolicies.two_factor_required ? 'bg-[#00D084]' : 'bg-slate-400'"
                                ></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 5. SUBTAB: OBSERVABILITAS & AUDIT                                   -->
        <!-- =================================================================== -->
        <div v-else-if="activeSubTab === 'observabilitas'" class="space-y-6">
            <!-- System Health Gauges -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-2">
                    <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                        <span class="font-medium">Koneksi Database PostGIS</span>
                        <Database class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#00D084] animate-pulse"></span>
                            <div class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                {{ systemHealth?.db_connection ? 'Operasional' : 'Terputus' }}
                            </div>
                        </div>
                        <div class="text-[11px] font-mono text-[#737B71] dark:text-[#858585] mt-1">
                            Latensi Query: <span class="text-[#00D084] font-semibold">{{ systemHealth?.db_latency_ms ?? 1.8 }} ms</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-2">
                    <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                        <span class="font-medium">Status Cache Ping</span>
                        <HardDrive class="w-4 h-4 text-blue-500" />
                    </div>
                    <div>
                        <div class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            {{ systemHealth?.cache_alive ? 'Aktif' : 'Nonaktif' }}
                        </div>
                        <div class="text-[11px] text-[#737B71] dark:text-[#858585] mt-1">
                            Driver: <span class="font-mono font-semibold">Redis / File</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-2">
                    <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                        <span class="font-medium">Penyimpanan Server Bebas</span>
                        <Server class="w-4 h-4 text-purple-500" />
                    </div>
                    <div>
                        <div class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            {{ systemHealth?.storage_free_formatted || 'Tersedia' }}
                        </div>
                        <div class="text-[11px] text-[#737B71] dark:text-[#858585] mt-1">
                            Memori PHP: {{ systemHealth?.memory_usage_mb ?? 32 }} MB
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-2">
                    <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585]">
                        <span class="font-medium">Runtime Stack</span>
                        <Activity class="w-4 h-4 text-amber-500" />
                    </div>
                    <div>
                        <div class="font-display font-bold text-sm text-[#171B17] dark:text-[#F5F5F5]">
                            Laravel 12 / PHP 8.2+
                        </div>
                        <div class="text-[10px] text-[#737B71] dark:text-[#858585] mt-1 font-mono">
                            Vue 3.5 + Inertia v2
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail Explorer -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <FileText class="w-4 h-4 text-[#00D084]" />
                            <span>Audit Trail Forensik Mutasi Data Sistem</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Rekam jejak seluruh penambahan, pengubahan, dan penghapusan data kritis oleh seluruh pengguna.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative w-48">
                            <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                            <input
                                v-model="auditSearch"
                                @keyup.enter="handleSearchAuditLogs(1)"
                                type="text"
                                placeholder="Cari aksi, IP..."
                                class="w-full h-8 pl-8 pr-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                            />
                        </div>

                        <select
                            v-model="auditModule"
                            @change="handleSearchAuditLogs(1)"
                            class="h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                        >
                            <option value="">Semua Modul</option>
                            <option value="thematic_intervals">thematic_intervals</option>
                            <option value="system_settings">system_settings</option>
                            <option value="ip_access_rules">ip_access_rules</option>
                            <option value="sessions">sessions</option>
                            <option value="cache">cache</option>
                        </select>

                        <button
                            type="button"
                            @click="handleSearchAuditLogs(1)"
                            class="h-8 px-3 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs flex items-center gap-1 cursor-pointer"
                        >
                            Cari
                        </button>
                        <button
                            type="button"
                            @click="resetAuditFilter"
                            class="h-8 px-2.5 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold cursor-pointer"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F4F5F2] dark:bg-[#181818] text-[#737B71] dark:text-[#858585] uppercase text-[10px] tracking-wider font-semibold border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <tr>
                                <th scope="col" class="px-4 py-3">Waktu</th>
                                <th scope="col" class="px-4 py-3">Pengguna / Aktor</th>
                                <th scope="col" class="px-4 py-3">Aksi</th>
                                <th scope="col" class="px-4 py-3">Tabel / Modul</th>
                                <th scope="col" class="px-4 py-3">Alamat IP</th>
                                <th scope="col" class="px-4 py-3 text-center">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5]">
                            <tr
                                v-for="log in auditLogs"
                                :key="log.id"
                                class="hover:bg-[#F9FAF8] dark:hover:bg-[#181818]/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-mono text-[11px] text-[#737B71] dark:text-[#858585] whitespace-nowrap">
                                    {{ log.created_at }}
                                </td>
                                <td class="px-4 py-3 font-semibold whitespace-nowrap">
                                    {{ log.user?.name || `User #${log.user_id || 'System'}` }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-500/10 border border-slate-500/20">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-[11px]">
                                    {{ log.table_name }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-blue-500 whitespace-nowrap">
                                    {{ log.ip_address || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button
                                        v-if="log.old_values || log.new_values"
                                        type="button"
                                        @click="openAuditDetail(log)"
                                        class="h-6 px-2.5 rounded bg-[#00D084]/10 text-[#00D084] hover:bg-[#00D084]/20 font-semibold text-[10px] cursor-pointer"
                                    >
                                        Lihat Diff
                                    </button>
                                    <span v-else class="text-[10px] text-slate-400">-</span>
                                </td>
                            </tr>
                            <tr v-if="auditLogs.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-[#737B71] dark:text-[#858585]">
                                    Tidak ada catatan audit log yang cocok dengan kriteria filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between text-xs text-[#737B71] dark:text-[#858585] pt-2">
                    <div>
                        Menampilkan halaman {{ auditLogMeta.current_page }} dari {{ auditLogMeta.last_page }} (Total: {{ auditLogMeta.total }} entri)
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="auditLogMeta.current_page <= 1"
                            @click="handleSearchAuditLogs(auditLogMeta.current_page - 1)"
                            class="h-7 px-2 rounded border border-[#E0E4DD] dark:border-[#242424] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] disabled:opacity-40 cursor-pointer"
                        >
                            <ChevronLeft class="w-3.5 h-3.5" />
                        </button>
                        <button
                            type="button"
                            :disabled="auditLogMeta.current_page >= auditLogMeta.last_page"
                            @click="handleSearchAuditLogs(auditLogMeta.current_page + 1)"
                            class="h-7 px-2 rounded border border-[#E0E4DD] dark:border-[#242424] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] disabled:opacity-40 cursor-pointer"
                        >
                            <ChevronRight class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Logs Viewer (storage/logs/laravel.log) -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div>
                        <h2 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                            <Terminal class="w-4 h-4 text-rose-500" />
                            <span>Log Kesalahan Runtime Server (storage/logs/laravel.log)</span>
                        </h2>
                        <p class="text-xs text-[#737B71] dark:text-[#858585] mt-0.5">
                            Memeriksa jejak exception, error query, dan peringatan sistem backend secara langsung.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="logLevel"
                            @change="handleFetchSystemLogs"
                            class="h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                        >
                            <option value="">Semua Level</option>
                            <option value="ERROR">ERROR</option>
                            <option value="WARNING">WARNING</option>
                            <option value="INFO">INFO</option>
                        </select>

                        <button
                            type="button"
                            @click="handleFetchSystemLogs"
                            class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center gap-1.5 cursor-pointer"
                        >
                            <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': actionLoading['fetch_system_logs'] }" />
                            <span>Segarkan Log</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div
                        v-for="(item, idx) in systemLogs"
                        :key="idx"
                        @click="openLogTrace(item)"
                        class="p-3 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] hover:border-[#00D084]/40 cursor-pointer transition-all space-y-1"
                    >
                        <div class="flex items-center justify-between text-[10px] font-mono">
                            <span class="text-[#737B71] dark:text-[#858585]">{{ item.timestamp }}</span>
                            <span
                                class="px-2 py-0.5 rounded font-bold uppercase"
                                :class="item.level === 'ERROR' ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20'"
                            >
                                {{ item.level }}
                            </span>
                        </div>
                        <div class="text-xs font-mono text-[#171B17] dark:text-[#F5F5F5] truncate">
                            {{ item.message }}
                        </div>
                    </div>
                    <div v-if="systemLogs.length === 0" class="py-8 text-center text-xs text-[#737B71] dark:text-[#858585]">
                        Tidak ada catatan log sistem yang ditemukan.
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 6. SUBTAB: INTEGRASI & GATEWAY                                      -->
        <!-- =================================================================== -->
        <div v-else-if="activeSubTab === 'integrasi'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. WhatsApp Gateway -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <div class="flex items-center gap-2">
                                <MessageSquare class="w-4 h-4 text-emerald-500" />
                                <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                    WhatsApp Notification Gateway
                                </h3>
                            </div>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                :class="integrations.whatsapp.enabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-[#00D084]' : 'bg-slate-500/10 text-slate-400'"
                            >
                                {{ integrations.whatsapp.enabled ? 'AKTIF' : 'NONAKTIF' }}
                            </span>
                        </div>

                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Endpoint URL:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5] truncate max-w-xs">{{ integrations.whatsapp.endpoint_url }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Nomor Pengirim:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.whatsapp.sender_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">API Key:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.whatsapp.api_key || '••••••••' }}</span>
                            </div>
                        </div>

                        <!-- Test result display -->
                        <div v-if="testResults['whatsapp']" class="p-2.5 rounded-lg text-[11px] bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex items-center justify-between">
                            <span class="text-[#00D084] font-semibold">{{ testResults['whatsapp'].message }}</span>
                            <span class="font-mono text-[10px] text-slate-400">~{{ testResults['whatsapp'].latency_ms }}ms</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <button
                            type="button"
                            @click="handleTestIntegration('whatsapp')"
                            :disabled="actionLoading['test_whatsapp']"
                            class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center gap-1.5 cursor-pointer"
                        >
                            <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': actionLoading['test_whatsapp'] }" />
                            <span>Uji Koneksi (Probe)</span>
                        </button>
                        <button
                            type="button"
                            @click="openEditIntegration('whatsapp')"
                            class="h-8 px-3.5 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs hover:bg-[#00B98B] cursor-pointer"
                        >
                            Konfigurasi
                        </button>
                    </div>
                </div>

                <!-- 2. SMTP Mailer Server -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <div class="flex items-center gap-2">
                                <Mail class="w-4 h-4 text-purple-500" />
                                <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                    SMTP Mailer &amp; EWS Dispatcher
                                </h3>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-[#00D084]">
                                SIAP
                            </span>
                        </div>

                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Host / Port:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.smtp.host }}:{{ integrations.smtp.port }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Username:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.smtp.username || 'Terproteksi' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Pengirim Resmi:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.smtp.from_address }}</span>
                            </div>
                        </div>

                        <div v-if="testResults['smtp']" class="p-2.5 rounded-lg text-[11px] bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex items-center justify-between">
                            <span class="text-[#00D084] font-semibold">{{ testResults['smtp'].message }}</span>
                            <span class="font-mono text-[10px] text-slate-400">~{{ testResults['smtp'].latency_ms }}ms</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <button
                            type="button"
                            @click="handleTestIntegration('smtp')"
                            :disabled="actionLoading['test_smtp']"
                            class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center gap-1.5 cursor-pointer"
                        >
                            <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': actionLoading['test_smtp'] }" />
                            <span>Uji Koneksi (Probe)</span>
                        </button>
                        <button
                            type="button"
                            @click="openEditIntegration('smtp')"
                            class="h-8 px-3.5 rounded-lg bg-purple-500 text-white font-bold text-xs hover:bg-purple-600 cursor-pointer"
                        >
                            Konfigurasi
                        </button>
                    </div>
                </div>

                <!-- 3. GeoPortal Banyumas (WFS/GeoServer) -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <div class="flex items-center gap-2">
                                <Globe class="w-4 h-4 text-blue-500" />
                                <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                    GeoPortal Pemkab Banyumas (WFS)
                                </h3>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-500">
                                WFS READY
                            </span>
                        </div>

                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">WFS Endpoint:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5] truncate max-w-xs">{{ integrations.geoportal.endpoint_url }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Layer Batas:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.geoportal.layer_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Token Akses:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.geoportal.api_token || '••••••••' }}</span>
                            </div>
                        </div>

                        <div v-if="testResults['geoportal']" class="p-2.5 rounded-lg text-[11px] bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex items-center justify-between">
                            <span class="text-[#00D084] font-semibold">{{ testResults['geoportal'].message }}</span>
                            <span class="font-mono text-[10px] text-slate-400">~{{ testResults['geoportal'].latency_ms }}ms</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <button
                            type="button"
                            @click="handleTestIntegration('geoportal')"
                            :disabled="actionLoading['test_geoportal']"
                            class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center gap-1.5 cursor-pointer"
                        >
                            <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': actionLoading['test_geoportal'] }" />
                            <span>Uji Koneksi (Probe)</span>
                        </button>
                        <button
                            type="button"
                            @click="openEditIntegration('geoportal')"
                            class="h-8 px-3.5 rounded-lg bg-blue-500 text-white font-bold text-xs hover:bg-blue-600 cursor-pointer"
                        >
                            Konfigurasi
                        </button>
                    </div>
                </div>

                <!-- 4. BPS Satu Data Banyumas -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                            <div class="flex items-center gap-2">
                                <Database class="w-4 h-4 text-amber-500" />
                                <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                                    BPS Satu Data Banyumas API
                                </h3>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500">
                                SINKRONISASI
                            </span>
                        </div>

                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">API Endpoint:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5] truncate max-w-xs">{{ integrations.bps.api_url }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">API Key:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">{{ integrations.bps.api_key || '••••••••' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#737B71] dark:text-[#858585]">Target Komoditas:</span>
                                <span class="font-mono text-[#171B17] dark:text-[#F5F5F5]">Sapi, Kambing, Ayam</span>
                            </div>
                        </div>

                        <div v-if="testResults['bps']" class="p-2.5 rounded-lg text-[11px] bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] flex items-center justify-between">
                            <span class="text-[#00D084] font-semibold">{{ testResults['bps'].message }}</span>
                            <span class="font-mono text-[10px] text-slate-400">~{{ testResults['bps'].latency_ms }}ms</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <button
                            type="button"
                            @click="handleTestIntegration('bps')"
                            :disabled="actionLoading['test_bps']"
                            class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center gap-1.5 cursor-pointer"
                        >
                            <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': actionLoading['test_bps'] }" />
                            <span>Uji Koneksi (Probe)</span>
                        </button>
                        <button
                            type="button"
                            @click="openEditIntegration('bps')"
                            class="h-8 px-3.5 rounded-lg bg-amber-500 text-slate-900 font-bold text-xs hover:bg-amber-600 cursor-pointer"
                        >
                            Konfigurasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL: RECALCULATE THEMATIC INTERVALS                               -->
        <!-- =================================================================== -->
        <div
            v-if="isRecalculateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2">
                        <Activity class="w-5 h-5 text-[#00D084]" />
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Hitung Ulang Ambang Batas Zonasi WebGIS
                        </h3>
                    </div>
                    <button type="button" @click="isRecalculateModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="font-bold text-[#171B17] dark:text-[#F5F5F5]">Kategori Data</label>
                            <select
                                v-model="selectedKategori"
                                class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                            >
                                <option v-for="kat in KATEGORI_OPTIONS" :key="kat.id" :value="kat.id">{{ kat.label }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-[#171B17] dark:text-[#F5F5F5]">Metode Algoritma</label>
                            <select
                                v-model="recalculateMethod"
                                class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                            >
                                <option value="jenks">Jenks Natural Breaks (Optimal Variance)</option>
                                <option value="quantile">Equal Quantile (Distribusi Seimbang)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pratinjau Tiers Hasil Kalkulasi -->
                    <div v-if="recalculatePreview" class="p-3.5 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] space-y-2">
                        <div class="font-bold text-[11px] text-[#00D084]">
                            Pratinjau Hasil Perhitungan (Sampel: {{ recalculatePreview.sample_size }} record):
                        </div>
                        <div class="space-y-1 font-mono text-[11px]">
                            <div
                                v-for="t in recalculatePreview.tiers"
                                :key="t.tier"
                                class="flex items-center justify-between py-0.5 border-b border-[#242424] last:border-none"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: t.color_hex }"></span>
                                    <span>Tier {{ t.tier }}: {{ t.label }}</span>
                                </div>
                                <span class="text-slate-400">
                                    {{ t.min_value }} - {{ t.max_value !== null ? t.max_value : 'Max' }} {{ t.satuan }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="handleRecalculate(false)"
                        :disabled="actionLoading['recalculate_intervals']"
                        class="h-8 px-3.5 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold hover:bg-[#EDEFEB] dark:hover:bg-[#181818] cursor-pointer"
                    >
                        Pratinjau Saja
                    </button>
                    <button
                        type="button"
                        @click="handleRecalculate(true)"
                        :disabled="actionLoading['recalculate_intervals']"
                        class="h-8 px-4 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs hover:bg-[#00B98B] cursor-pointer"
                    >
                        Terapkan ke Sistem
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL: RESTORE CONFIRMATION DATABASE                                -->
        <!-- =================================================================== -->
        <div
            v-if="isRestoreModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-[#111111] border border-rose-500/40 shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3 text-rose-500">
                    <AlertTriangle class="w-6 h-6 shrink-0" />
                    <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5]">
                        Konfirmasi Pemulihan Database Kritis
                    </h3>
                </div>

                <p class="text-xs text-[#737B71] dark:text-[#858585] leading-relaxed">
                    Tindakan ini akan <span class="text-rose-500 font-bold">menimpa seluruh database aktif saat ini</span> dengan snapshot cadangan <span class="font-mono font-bold text-white">{{ restoreTarget?.filename }}</span>.
                </p>

                <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1">
                    <div class="font-bold">Ketik teks berikut untuk mengonfirmasi:</div>
                    <div class="font-mono font-extrabold text-sm select-all">RESTORE_DATABASE</div>
                </div>

                <input
                    v-model="restoreConfirmCode"
                    type="text"
                    placeholder="Ketik RESTORE_DATABASE di sini..."
                    class="w-full h-9 px-3 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-rose-500/40 text-xs font-mono font-bold text-rose-500 focus:outline-none focus:border-rose-500"
                />

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isRestoreModalOpen = false"
                        class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleConfirmRestore"
                        :disabled="restoreConfirmCode !== 'RESTORE_DATABASE' || actionLoading[`restore_backup_${restoreTarget?.id}`]"
                        class="h-8 px-4 rounded-lg bg-rose-500 text-white font-bold text-xs hover:bg-rose-600 disabled:opacity-40 cursor-pointer shadow-xs"
                    >
                        {{ actionLoading[`restore_backup_${restoreTarget?.id}`] ? 'Memulihkan Basis Data...' : 'Konfirmasi & Pulihkan Database' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL: TAMBAH ATURAN IP FIREWALL                                    -->
        <!-- =================================================================== -->
        <div
            v-if="isAddIpModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                        <Plus class="w-4 h-4 text-[#00D084]" />
                        <span>Tambah Aturan Firewall IP</span>
                    </h3>
                    <button type="button" @click="isAddIpModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="font-bold text-[#171B17] dark:text-[#F5F5F5]">Alamat IP atau Rentang CIDR</label>
                        <input
                            v-model="ipRuleForm.ip_address"
                            type="text"
                            placeholder="Contoh: 192.168.1.100 atau 10.0.0.0/24"
                            class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono text-[#171B17] dark:text-[#F5F5F5]"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-[#171B17] dark:text-[#F5F5F5]">Tipe Aturan</label>
                        <select
                            v-model="ipRuleForm.rule_type"
                            class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                        >
                            <option value="whitelist">Whitelist (Izinkan Akses Khusus)</option>
                            <option value="blacklist">Blacklist (Blokir Akses Total)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-[#171B17] dark:text-[#F5F5F5]">Keterangan / Lokasi</label>
                        <input
                            v-model="ipRuleForm.description"
                            type="text"
                            placeholder="Contoh: Kantor Dinas Peternakan Banyumas"
                            class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs text-[#171B17] dark:text-[#F5F5F5]"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isAddIpModalOpen = false"
                        class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleCreateIpRule"
                        :disabled="actionLoading['create_ip_rule']"
                        class="h-8 px-4 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs hover:bg-[#00B98B] cursor-pointer"
                    >
                        Simpan Aturan IP
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL: DETAIL AUDIT DIFF                                            -->
        <!-- =================================================================== -->
        <div
            v-if="isAuditDetailModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2">
                        <FileText class="w-4 h-4 text-[#00D084]" />
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Detail Mutasi Data Audit Trail #{{ selectedAuditLog?.id }}
                        </h3>
                    </div>
                    <button type="button" @click="isAuditDetailModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs font-mono max-h-96 overflow-y-auto">
                    <div v-if="selectedAuditLog?.old_values">
                        <div class="text-[11px] font-bold text-rose-400 mb-1">Nilai Lama (Old Values):</div>
                        <pre class="p-3 rounded-xl bg-[#181818] text-[#F5F5F5] border border-[#242424] text-[11px] overflow-x-auto">{{ JSON.stringify(selectedAuditLog.old_values, null, 2) }}</pre>
                    </div>

                    <div v-if="selectedAuditLog?.new_values">
                        <div class="text-[11px] font-bold text-emerald-400 mb-1">Nilai Baru (New Values):</div>
                        <pre class="p-3 rounded-xl bg-[#181818] text-[#00D084] border border-[#242424] text-[11px] overflow-x-auto">{{ JSON.stringify(selectedAuditLog.new_values, null, 2) }}</pre>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isAuditDetailModalOpen = false"
                        class="h-8 px-4 rounded-lg bg-slate-700 text-white font-semibold text-xs cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL: DETAIL SYSTEM LOG TRACE                                      -->
        <!-- =================================================================== -->
        <div
            v-if="isLogTraceModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-3xl rounded-2xl bg-white dark:bg-[#111111] border border-rose-500/30 shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center gap-2">
                        <Terminal class="w-4 h-4 text-rose-500" />
                        <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">
                            Jejak Eksepsi Runtime (Stack Trace)
                        </h3>
                    </div>
                    <button type="button" @click="isLogTraceModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-2 text-xs font-mono">
                    <div class="text-[11px] text-[#858585]">{{ selectedSystemLog?.timestamp }} [{{ selectedSystemLog?.level }}]</div>
                    <div class="p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-bold leading-relaxed">
                        {{ selectedSystemLog?.message }}
                    </div>

                    <div v-if="selectedSystemLog?.stack_trace" class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] text-[#858585]">
                            <span>Call Stack Trace:</span>
                            <button
                                type="button"
                                @click="copyToClipboard(selectedSystemLog.stack_trace)"
                                class="text-[#00D084] hover:underline cursor-pointer flex items-center gap-1"
                            >
                                <Copy class="w-3 h-3" />
                                <span>Salin Trace</span>
                            </button>
                        </div>
                        <pre class="p-3 rounded-xl bg-[#181818] text-[#858585] border border-[#242424] text-[10px] max-h-64 overflow-y-auto whitespace-pre-wrap leading-relaxed">{{ selectedSystemLog.stack_trace }}</pre>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isLogTraceModalOpen = false"
                        class="h-8 px-4 rounded-lg bg-slate-700 text-white font-semibold text-xs cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODAL: EDIT GATEWAY CREDENTIALS                                     -->
        <!-- =================================================================== -->
        <div
            v-if="isEditIntegrationModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] uppercase">
                        Konfigurasi Gateway: {{ editingGateway }}
                    </h3>
                    <button type="button" @click="isEditIntegrationModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Form fields depending on gateway -->
                <div class="space-y-3 text-xs">
                    <div
                        v-for="(val, field) in integrationForm"
                        :key="field"
                        class="space-y-1"
                    >
                        <label class="font-bold text-[#171B17] dark:text-[#F5F5F5] capitalize">{{ String(field).replace(/_/g, ' ') }}</label>
                        <div v-if="typeof val === 'boolean'" class="pt-1">
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="Boolean(integrationForm[field])"
                                :aria-label="`Sakelar ${String(field).replace(/_/g, ' ')}`"
                                class="relative inline-flex h-6 w-14 flex-shrink-0 cursor-pointer items-center rounded-full p-0.5 border transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                                :class="Boolean(integrationForm[field]) ? 'bg-[#171B17] border-[#171B17] dark:bg-[#00D084] dark:border-[#00D084]' : 'bg-slate-200 border-[#CBD2C8] dark:bg-[#181818] dark:border-[#282828]'"
                                @click="integrationForm[field] = !Boolean(integrationForm[field])"
                            >
                                <span
                                    class="absolute text-[9px] font-black tracking-wider transition-all duration-200"
                                    :class="Boolean(integrationForm[field]) ? 'left-1.5 text-white dark:text-[#062117]' : 'right-1.5 text-[#737B71] dark:text-[#858585]'"
                                >
                                    {{ Boolean(integrationForm[field]) ? 'ON' : 'OFF' }}
                                </span>
                                <span
                                    aria-hidden="true"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                    :class="Boolean(integrationForm[field]) ? 'translate-x-8' : 'translate-x-0'"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="Boolean(integrationForm[field]) ? 'bg-[#00D084]' : 'bg-slate-400'"
                                    ></span>
                                </span>
                            </button>
                        </div>
                        <input
                            v-else
                            v-model="integrationForm[field]"
                            :type="String(field).includes('password') || String(field).includes('key') || String(field).includes('token') ? 'password' : 'text'"
                            class="w-full h-8 px-2.5 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#242424] text-xs font-mono text-[#171B17] dark:text-[#F5F5F5]"
                        />
                    </div>
                    <p class="text-[10px] text-[#737B71] dark:text-[#858585]">
                        Kunci token/sandi tersimpan terenkripsi. Biarkan kosong atau bertanda titik jika tidak ingin mengganti.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="isEditIntegrationModalOpen = false"
                        class="h-8 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#242424] text-xs font-semibold cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleSaveIntegration"
                        :disabled="actionLoading['update_integrations']"
                        class="h-8 px-4 rounded-lg bg-[#00D084] text-[#062117] font-bold text-xs hover:bg-[#00B98B] cursor-pointer"
                    >
                        Simpan Konfigurasi
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
