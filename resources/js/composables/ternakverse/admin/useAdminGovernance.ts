import { ref } from 'vue';

// =============================================================================
// TYPES & INTERFACES FOR ALL 6 GOVERNANCE DOMAINS
// =============================================================================

// 1. Kebijakan & Interval Tematik
export interface ThematicInterval {
    id?: number;
    kategori: 'populasi' | 'produksi' | 'penyakit' | 'fasilitas';
    tier: number;
    label: string;
    min_value: number;
    max_value: number | null;
    color_hex: string;
    satuan: string;
    calculation_mode: string;
    is_active: boolean;
    effective_date?: string;
    updated_by?: number | null;
}

export interface ConversionFactors {
    conversion_st_sapi: number;
    conversion_st_kambing: number;
    conversion_st_unggas: number;
    hmt_multiplier: number;
}

export interface RecalculateResult {
    kategori: string;
    method: string;
    sample_size: number;
    tiers_count: number;
    breaks: number[];
    tiers: ThematicInterval[];
    applied: boolean;
}

// 2. Ketersediaan & Pemeliharaan
export interface MaintenanceConfig {
    active: boolean;
    enabled?: boolean;
    message: string;
    scheduled_end: string | null;
}

export interface EmergencyBannerConfig {
    enabled: boolean;
    message: string;
    level: 'info' | 'warning' | 'danger' | 'alert';
}

export interface SystemState {
    maintenance: MaintenanceConfig;
    emergency_banner: EmergencyBannerConfig;
    features: Record<string, boolean>;
    server_time: string;
    environment: string;
}

// 3. Ketahanan & Backup
export interface BackupRecord {
    id: number;
    filename: string;
    size_bytes: number;
    size_formatted: string;
    checksum: string;
    status: string;
    notes: string | null;
    created_at: string;
    created_by_name?: string | null;
}

export interface QueueFailedJob {
    id: number;
    connection: string;
    queue: string;
    failed_at: string;
    exception_summary: string;
}

export interface QueueStatus {
    driver: string;
    pending_jobs: number;
    failed_jobs: number;
    status: 'attention' | 'healthy';
    recent_failed: QueueFailedJob[];
}

// 4. Keamanan & Akses
export interface SessionRecord {
    id: string;
    user_id: number;
    user_name: string;
    ip_address: string;
    user_agent: string;
    browser?: string;
    device?: string;
    platform?: string;
    last_activity: number;
    last_activity_formatted: string;
    is_current: boolean;
}

export interface IpRuleRecord {
    id: number;
    ip_address: string;
    rule_type: 'whitelist' | 'blacklist' | 'allow' | 'block' | 'deny';
    description: string | null;
    is_active: boolean;
    created_at?: string;
}

export interface SecurityPolicies {
    max_login_attempts: number;
    session_lifetime_minutes: number;
    two_factor_required: boolean;
    password_min_length: number;
    ip_whitelist_enforced: boolean;
}

// 5. Observability & Auditing
export interface AuditLogUser {
    id: number;
    name: string;
    email: string;
}

export interface AuditLogItem {
    id: number;
    action: string;
    table_name: string;
    record_id: number | null;
    ip_address: string | null;
    user_id: number | null;
    user?: AuditLogUser | null;
    old_values?: Record<string, unknown> | null;
    new_values?: Record<string, unknown> | null;
    created_at: string;
}

export interface AuditLogMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}

export interface SystemLogItem {
    id?: number;
    timestamp: string;
    env: string;
    level: string;
    message: string;
    context?: Record<string, unknown> | null;
    stack_trace?: string | null;
}

export interface SystemHealth {
    status: 'healthy' | 'warning' | 'critical' | string;
    db_connection: boolean;
    db_latency_ms: number;
    cache_alive: boolean;
    storage_free_bytes: number;
    storage_free_formatted: string;
    memory_usage_mb: number;
    memory_peak_mb: number;
    load_average?: number[];
    php_version: string;
    laravel_version: string;
    timestamp: string;
}

// 6. Integrasi & Gateway
export interface WhatsAppGatewayConfig {
    enabled: boolean;
    endpoint_url: string;
    api_key: string;
    sender_number: string;
}

export interface SmtpGatewayConfig {
    host: string;
    port: number;
    username: string;
    password: string;
    encryption: string;
    from_address: string;
}

export interface GeoportalGatewayConfig {
    endpoint_url: string;
    api_token: string;
    layer_id: string;
}

export interface BpsGatewayConfig {
    api_url: string;
    api_key: string;
}

export interface IntegrationsConfig {
    whatsapp: WhatsAppGatewayConfig;
    smtp: SmtpGatewayConfig;
    geoportal: GeoportalGatewayConfig;
    bps: BpsGatewayConfig;
}

export interface TestConnectionResult {
    status: string;
    gateway: string;
    connected: boolean;
    latency_ms: number;
    message: string;
}

export interface AuditLogFilters {
    search?: string;
    module?: string;
    action?: string;
    user_id?: number;
    start_date?: string;
    end_date?: string;
    per_page?: number;
    page?: number;
}

export interface SystemLogFilters {
    limit?: number;
    level?: string;
    search?: string;
}

// Helper to extract CSRF token safely
export function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (cookieMatch && cookieMatch[1]) return decodeURIComponent(cookieMatch[1]);
    return '';
}

// =============================================================================
// MAIN COMPOSABLE: useAdminGovernance
// =============================================================================
export function useAdminGovernance() {
    // Global and domain-level loading trackers
    const isLoading = ref<boolean>(false);
    const actionLoading = ref<Record<string, boolean>>({});
    const error = ref<string | null>(null);

    // 1. Kebijakan & Interval Tematik state
    const thematicIntervals = ref<ThematicInterval[]>([]);
    const conversionFactors = ref<ConversionFactors>({
        conversion_st_sapi: 1.0,
        conversion_st_kambing: 0.14,
        conversion_st_unggas: 0.01,
        hmt_multiplier: 10.0,
    });
    const recalculatePreview = ref<RecalculateResult | null>(null);

    // 2. Ketersediaan & Pemeliharaan state
    const systemState = ref<SystemState>({
        maintenance: {
            active: false,
            enabled: false,
            message: 'Sistem sedang dalam masa pemeliharaan berkala.',
            scheduled_end: null,
        },
        emergency_banner: {
            enabled: false,
            message: '',
            level: 'info',
        },
        features: {},
        server_time: new Date().toISOString(),
        environment: 'production',
    });

    // 3. Ketahanan & Backup state
    const backups = ref<BackupRecord[]>([]);
    const queueStatus = ref<QueueStatus>({
        driver: 'sync',
        pending_jobs: 0,
        failed_jobs: 0,
        status: 'healthy',
        recent_failed: [],
    });

    // 4. Keamanan & Akses state
    const sessions = ref<SessionRecord[]>([]);
    const ipRules = ref<IpRuleRecord[]>([]);
    const securityPolicies = ref<SecurityPolicies>({
        max_login_attempts: 5,
        session_lifetime_minutes: 120,
        two_factor_required: false,
        password_min_length: 8,
        ip_whitelist_enforced: false,
    });

    // 5. Observabilitas & Auditing state
    const auditLogs = ref<AuditLogItem[]>([]);
    const auditLogMeta = ref<AuditLogMeta>({
        current_page: 1,
        per_page: 25,
        total: 0,
        last_page: 1,
    });
    const systemLogs = ref<SystemLogItem[]>([]);
    const systemHealth = ref<SystemHealth | null>(null);

    // 6. Integrasi & Gateway state
    const integrations = ref<IntegrationsConfig>({
        whatsapp: {
            enabled: false,
            endpoint_url: 'https://api.fonnte.com/send',
            api_key: '',
            sender_number: '',
        },
        smtp: {
            host: 'smtp.mailtrap.io',
            port: 2525,
            username: '',
            password: '',
            encryption: 'tls',
            from_address: 'diskanak@banyumaskab.go.id',
        },
        geoportal: {
            endpoint_url: 'https://geoportal.banyumaskab.go.id/geoserver/wfs',
            api_token: '',
            layer_id: 'banyumas:batas_kecamatan',
        },
        bps: {
            api_url: 'https://webapi.bps.go.id/v1/api/data',
            api_key: '',
        },
    });

    // Internal fetch wrapper with CSRF injection and JSON validation
    async function apiRequest<T>(
        url: string,
        method: 'GET' | 'POST' | 'PUT' | 'DELETE' = 'GET',
        payload?: unknown
    ): Promise<T> {
        error.value = null;
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        const csrf = getCsrfToken();
        if (csrf) {
            headers['X-CSRF-TOKEN'] = csrf;
        }

        const options: RequestInit = {
            method,
            headers,
        };

        if (payload !== undefined && method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }

        const res = await fetch(url, options);
        let resJson: Record<string, unknown> = {};

        try {
            resJson = (await res.json()) as Record<string, unknown>;
        } catch {
            if (!res.ok) {
                throw new Error(`Permintaan gagal dengan status HTTP ${res.status}`);
            }
        }

        if (!res.ok) {
            const errorMsg =
                typeof resJson.message === 'string'
                    ? resJson.message
                    : `Permintaan ${method} ${url} gagal (HTTP ${res.status})`;
            error.value = errorMsg;
            throw new Error(errorMsg);
        }

        return resJson as T;
    }

    function setActionLoading(key: string, state: boolean) {
        actionLoading.value = {
            ...actionLoading.value,
            [key]: state,
        };
    }

    // =========================================================================
    // 1. KEBIJAKAN & INTERVAL TEMATIK METHODS
    // =========================================================================
    async function fetchThematicIntervals(): Promise<void> {
        setActionLoading('fetch_intervals', true);
        try {
            const res = await apiRequest<{ status: string; data: ThematicInterval[] }>(
                '/admin/api/governance/thematic-intervals'
            );
            if (res.data && Array.isArray(res.data)) {
                thematicIntervals.value = res.data;
            }
        } finally {
            setActionLoading('fetch_intervals', false);
        }
    }

    async function saveThematicIntervals(intervals: ThematicInterval[]): Promise<string> {
        setActionLoading('save_intervals', true);
        try {
            const res = await apiRequest<{ status: string; message?: string }>(
                '/admin/api/governance/thematic-intervals',
                'PUT',
                { intervals }
            );
            await fetchThematicIntervals();
            return res.message || 'Interval tematik berhasil diperbarui.';
        } finally {
            setActionLoading('save_intervals', false);
        }
    }

    async function recalculateThematicIntervals(params: {
        kategori?: 'populasi' | 'produksi' | 'penyakit' | 'fasilitas';
        method?: 'jenks' | 'quantile';
        classes?: number;
        apply?: boolean;
    }): Promise<RecalculateResult> {
        setActionLoading('recalculate_intervals', true);
        try {
            const classesCount = params.classes ?? 5;
            const res = await apiRequest<{
                status: string;
                message?: string;
                data: RecalculateResult;
            }>('/admin/api/governance/thematic-intervals/recalculate', 'POST', {
                ...params,
                kategori: params.kategori ?? 'populasi',
                method: params.method ?? 'jenks',
                classes: classesCount,
                tiers_count: classesCount,
                apply: params.apply ?? false,
            });

            recalculatePreview.value = res.data;
            if (params.apply) {
                await fetchThematicIntervals();
            }
            return res.data;
        } finally {
            setActionLoading('recalculate_intervals', false);
        }
    }

    async function saveConversionFactors(factors: Partial<ConversionFactors>): Promise<string> {
        setActionLoading('save_conversion_factors', true);
        try {
            const res = await apiRequest<{
                status: string;
                message?: string;
                data: ConversionFactors;
            }>('/admin/api/governance/conversion-factors', 'PUT', factors);

            if (res.data) {
                conversionFactors.value = {
                    ...conversionFactors.value,
                    ...res.data,
                };
            }
            return res.message || 'Faktor konversi kebijakan ternak berhasil disimpan.';
        } finally {
            setActionLoading('save_conversion_factors', false);
        }
    }

    // =========================================================================
    // 2. KETERSEDIAAN & PEMELIHARAAN METHODS
    // =========================================================================
    async function fetchSystemState(): Promise<void> {
        setActionLoading('fetch_state', true);
        try {
            const res = await apiRequest<{ status: string; data: SystemState }>(
                '/admin/api/governance/state'
            );
            if (res.data) {
                systemState.value = {
                    ...res.data,
                    maintenance: {
                        ...res.data.maintenance,
                        active: Boolean(res.data.maintenance.active ?? res.data.maintenance.enabled),
                    },
                };
            }
        } finally {
            setActionLoading('fetch_state', false);
        }
    }

    async function updateMaintenanceMode(payload: {
        enabled: boolean;
        message?: string;
        scheduled_end?: string | null;
    }): Promise<string> {
        setActionLoading('update_maintenance', true);
        try {
            const res = await apiRequest<{
                status: string;
                message?: string;
                data: MaintenanceConfig;
            }>('/admin/api/governance/maintenance', 'PUT', payload);

            if (res.data) {
                systemState.value.maintenance = {
                    active: res.data.active,
                    enabled: res.data.active,
                    message: res.data.message,
                    scheduled_end: res.data.scheduled_end,
                };
            }
            return res.message || 'Status mode pemeliharaan berhasil diperbarui.';
        } finally {
            setActionLoading('update_maintenance', false);
        }
    }

    async function updateEmergencyBanner(payload: {
        enabled: boolean;
        message?: string;
        level?: 'info' | 'warning' | 'danger' | 'alert';
    }): Promise<string> {
        setActionLoading('update_banner', true);
        try {
            const res = await apiRequest<{
                status: string;
                message?: string;
                data: EmergencyBannerConfig;
            }>('/admin/api/governance/emergency-banner', 'PUT', payload);

            if (res.data) {
                systemState.value.emergency_banner = res.data;
            }
            return res.message || 'Banner pengumuman darurat berhasil diperbarui.';
        } finally {
            setActionLoading('update_banner', false);
        }
    }

    async function updateFeatureFlags(features: Record<string, boolean>): Promise<string> {
        setActionLoading('update_features', true);
        try {
            const res = await apiRequest<{
                status: string;
                message?: string;
                data: Record<string, boolean>;
            }>('/admin/api/governance/features', 'PUT', { features });

            if (res.data) {
                systemState.value.features = res.data;
            }
            return res.message || 'Feature flags berhasil disimpan.';
        } finally {
            setActionLoading('update_features', false);
        }
    }

    async function clearCache(
        target: 'all' | 'app' | 'config' | 'route' | 'query' | 'gis' = 'all'
    ): Promise<{ message: string; output: string }> {
        const loadingKey = `clear_cache_${target}`;
        setActionLoading(loadingKey, true);
        try {
            const res = await apiRequest<{
                status: string;
                message: string;
                output: string;
            }>('/admin/api/governance/cache/clear', 'POST', { target });

            return {
                message: res.message || `Cache [${target}] berhasil dibersihkan.`,
                output: res.output || '',
            };
        } finally {
            setActionLoading(loadingKey, false);
        }
    }

    // =========================================================================
    // 3. KETAHANAN & BACKUP METHODS
    // =========================================================================
    async function fetchBackups(): Promise<void> {
        setActionLoading('fetch_backups', true);
        try {
            const res = await apiRequest<{ status: string; data: BackupRecord[] }>(
                '/admin/api/governance/backups'
            );
            if (res.data && Array.isArray(res.data)) {
                backups.value = res.data;
            }
        } finally {
            setActionLoading('fetch_backups', false);
        }
    }

    async function createBackup(notes?: string): Promise<{ message: string; backup: BackupRecord }> {
        setActionLoading('create_backup', true);
        try {
            const res = await apiRequest<{
                status: string;
                message: string;
                data: BackupRecord;
            }>('/admin/api/governance/backups', 'POST', { notes: notes ?? '' });

            await fetchBackups();
            return {
                message: res.message || 'Snapshot database berhasil dibuat.',
                backup: res.data,
            };
        } finally {
            setActionLoading('create_backup', false);
        }
    }

    async function deleteBackup(id: number): Promise<string> {
        setActionLoading(`delete_backup_${id}`, true);
        try {
            const res = await apiRequest<{ status: string; message: string }>(
                `/admin/api/governance/backups/${id}`,
                'DELETE'
            );
            await fetchBackups();
            return res.message || `Cadangan #${id} berhasil dihapus.`;
        } finally {
            setActionLoading(`delete_backup_${id}`, false);
        }
    }

    async function restoreBackup(id: number, confirmCode: string): Promise<string> {
        setActionLoading(`restore_backup_${id}`, true);
        try {
            const res = await apiRequest<{ status: string; message: string }>(
                `/admin/api/governance/backups/${id}/restore`,
                'POST',
                { confirm_code: confirmCode }
            );
            return res.message || `Database berhasil dipulihkan dari cadangan #${id}.`;
        } finally {
            setActionLoading(`restore_backup_${id}`, false);
        }
    }

    function getBackupDownloadUrl(id: number): string {
        return `/admin/api/governance/backups/${id}/download`;
    }

    async function fetchQueueStatus(): Promise<void> {
        setActionLoading('fetch_queue', true);
        try {
            const res = await apiRequest<{ status: string; data: QueueStatus }>(
                '/admin/api/governance/queue-status'
            );
            if (res.data) {
                queueStatus.value = res.data;
            }
        } finally {
            setActionLoading('fetch_queue', false);
        }
    }

    // =========================================================================
    // 4. KEAMANAN & AKSES METHODS
    // =========================================================================
    async function fetchSessions(): Promise<void> {
        setActionLoading('fetch_sessions', true);
        try {
            const res = await apiRequest<{ status: string; data: SessionRecord[] }>(
                '/admin/api/governance/sessions'
            );
            if (res.data && Array.isArray(res.data)) {
                sessions.value = res.data;
            }
        } finally {
            setActionLoading('fetch_sessions', false);
        }
    }

    async function terminateSession(sessionId: string): Promise<string> {
        setActionLoading(`terminate_session_${sessionId}`, true);
        try {
            const res = await apiRequest<{ status: string; message: string }>(
                `/admin/api/governance/sessions/${encodeURIComponent(sessionId)}`,
                'DELETE'
            );
            await fetchSessions();
            return res.message || 'Sesi pengguna berhasil dihentikan.';
        } finally {
            setActionLoading(`terminate_session_${sessionId}`, false);
        }
    }

    async function terminateAllOtherSessions(): Promise<{ message: string; count: number }> {
        setActionLoading('terminate_other_sessions', true);
        try {
            const res = await apiRequest<{ status: string; message: string; count: number }>(
                '/admin/api/governance/sessions/terminate-others',
                'POST'
            );
            await fetchSessions();
            return {
                message: res.message || 'Sesi aktif lainnya berhasil dihentikan.',
                count: res.count ?? 0,
            };
        } finally {
            setActionLoading('terminate_other_sessions', false);
        }
    }

    async function fetchIpRules(): Promise<void> {
        setActionLoading('fetch_ip_rules', true);
        try {
            const res = await apiRequest<{ status: string; data: IpRuleRecord[] }>(
                '/admin/api/governance/ip-rules'
            );
            if (res.data && Array.isArray(res.data)) {
                ipRules.value = res.data;
            }
        } finally {
            setActionLoading('fetch_ip_rules', false);
        }
    }

    async function createIpRule(rule: {
        ip_address: string;
        rule_type: string;
        description?: string;
        is_active?: boolean;
    }): Promise<{ message: string; data: IpRuleRecord }> {
        setActionLoading('create_ip_rule', true);
        try {
            const res = await apiRequest<{
                status: string;
                message: string;
                data: IpRuleRecord;
            }>('/admin/api/governance/ip-rules', 'POST', rule);

            await fetchIpRules();
            return {
                message: res.message || 'Aturan akses IP berhasil disimpan.',
                data: res.data,
            };
        } finally {
            setActionLoading('create_ip_rule', false);
        }
    }

    async function deleteIpRule(id: number): Promise<string> {
        setActionLoading(`delete_ip_rule_${id}`, true);
        try {
            const res = await apiRequest<{ status: string; message: string }>(
                `/admin/api/governance/ip-rules/${id}`,
                'DELETE'
            );
            await fetchIpRules();
            return res.message || 'Aturan akses IP berhasil dihapus.';
        } finally {
            setActionLoading(`delete_ip_rule_${id}`, false);
        }
    }

    async function updateSecurityPolicies(policies: Partial<SecurityPolicies>): Promise<string> {
        setActionLoading('update_security_policies', true);
        try {
            const res = await apiRequest<{
                status: string;
                message?: string;
                data: Record<string, unknown>;
            }>('/admin/api/governance/security-policies', 'PUT', policies);

            securityPolicies.value = {
                ...securityPolicies.value,
                ...policies,
            };
            return res.message || 'Kebijakan keamanan sistem berhasil diperbarui.';
        } finally {
            setActionLoading('update_security_policies', false);
        }
    }

    // =========================================================================
    // 5. OBSERVABILITAS & AUDITING METHODS
    // =========================================================================
    async function fetchAuditLogs(filters?: AuditLogFilters): Promise<void> {
        setActionLoading('fetch_audit_logs', true);
        try {
            const params = new URLSearchParams();
            if (filters?.search) params.append('search', filters.search);
            if (filters?.module) params.append('module', filters.module);
            if (filters?.action) params.append('action', filters.action);
            if (filters?.user_id) params.append('user_id', String(filters.user_id));
            if (filters?.start_date) params.append('start_date', filters.start_date);
            if (filters?.end_date) params.append('end_date', filters.end_date);
            if (filters?.per_page) params.append('per_page', String(filters.per_page));
            if (filters?.page) params.append('page', String(filters.page));

            const queryStr = params.toString() ? `?${params.toString()}` : '';
            const res = await apiRequest<{
                status: string;
                data: AuditLogItem[];
                meta: AuditLogMeta;
            }>(`/admin/api/governance/audit-logs${queryStr}`);

            if (res.data && Array.isArray(res.data)) {
                auditLogs.value = res.data;
            }
            if (res.meta) {
                auditLogMeta.value = res.meta;
            }
        } finally {
            setActionLoading('fetch_audit_logs', false);
        }
    }

    async function fetchSystemLogs(filters?: SystemLogFilters): Promise<void> {
        setActionLoading('fetch_system_logs', true);
        try {
            const params = new URLSearchParams();
            if (filters?.limit) params.append('limit', String(filters.limit));
            if (filters?.level) params.append('level', filters.level);
            if (filters?.search) params.append('search', filters.search);

            const queryStr = params.toString() ? `?${params.toString()}` : '';
            const res = await apiRequest<{
                status: string;
                data: SystemLogItem[];
                count: number;
            }>(`/admin/api/governance/system-logs${queryStr}`);

            if (res.data && Array.isArray(res.data)) {
                systemLogs.value = res.data;
            }
        } finally {
            setActionLoading('fetch_system_logs', false);
        }
    }

    async function fetchSystemHealth(): Promise<void> {
        setActionLoading('fetch_health', true);
        try {
            const res = await apiRequest<{
                status: string;
                data: {
                    status?: string;
                    database?: {
                        status?: string;
                        latency_ms?: number;
                        database_name?: string | null;
                        driver?: string;
                    };
                    cache?: {
                        status?: string;
                        ping_ms?: number;
                        driver?: string;
                    };
                    storage?: {
                        free_bytes?: number;
                        total_bytes?: number;
                        free_percentage?: number;
                    };
                    server?: {
                        php_version?: string;
                        laravel_version?: string;
                        server_time?: string;
                        memory_usage?: number;
                        memory_usage_formatted?: string;
                    };
                    db_connection?: boolean;
                    db_latency_ms?: number;
                    cache_alive?: boolean;
                    storage_free_bytes?: number;
                    storage_free_formatted?: string;
                    memory_usage_mb?: number;
                    memory_peak_mb?: number;
                    load_average?: number[];
                    php_version?: string;
                    laravel_version?: string;
                    timestamp?: string;
                };
            }>('/admin/api/governance/health');
            if (res.data) {
                const raw = res.data;
                const dbOk = raw.db_connection ?? (raw.database?.status === 'ok');
                const cacheOk = raw.cache_alive ?? (raw.cache?.status === 'ok');
                const latency = raw.database?.latency_ms ?? raw.db_latency_ms ?? 1.8;
                const memoryUsage = raw.server?.memory_usage ?? 0;
                const memoryMb = raw.memory_usage_mb ?? (memoryUsage > 0 ? Math.round(memoryUsage / (1024 * 1024)) : 32);
                const storageFormatted = raw.storage_free_formatted ?? (raw.storage?.free_percentage !== undefined ? `${raw.storage.free_percentage}% Tersedia` : 'Tersedia');

                systemHealth.value = {
                    status: raw.status ?? (dbOk && cacheOk ? 'healthy' : 'warning'),
                    db_connection: dbOk,
                    db_latency_ms: latency,
                    cache_alive: cacheOk,
                    storage_free_bytes: raw.storage?.free_bytes ?? raw.storage_free_bytes ?? 0,
                    storage_free_formatted: storageFormatted,
                    memory_usage_mb: memoryMb,
                    memory_peak_mb: raw.memory_peak_mb ?? memoryMb,
                    load_average: raw.load_average,
                    php_version: raw.server?.php_version ?? raw.php_version ?? '',
                    laravel_version: raw.server?.laravel_version ?? raw.laravel_version ?? '',
                    timestamp: raw.server?.server_time ?? raw.timestamp ?? new Date().toISOString(),
                };
            }
        } finally {
            setActionLoading('fetch_health', false);
        }
    }

    // =========================================================================
    // 6. INTEGRASI & GATEWAY METHODS
    // =========================================================================
    async function fetchIntegrations(): Promise<void> {
        setActionLoading('fetch_integrations', true);
        try {
            const res = await apiRequest<{ status: string; data: IntegrationsConfig }>(
                '/admin/api/governance/integrations'
            );
            if (res.data) {
                integrations.value = res.data;
            }
        } finally {
            setActionLoading('fetch_integrations', false);
        }
    }

    async function updateIntegrations(config: Partial<IntegrationsConfig>): Promise<string> {
        setActionLoading('update_integrations', true);
        try {
            const res = await apiRequest<{ status: string; message?: string }>(
                '/admin/api/governance/integrations',
                'PUT',
                config
            );
            await fetchIntegrations();
            return res.message || 'Pengaturan integrasi gateway berhasil disimpan.';
        } finally {
            setActionLoading('update_integrations', false);
        }
    }

    async function testIntegration(
        gateway: 'whatsapp' | 'smtp' | 'geoportal' | 'bps'
    ): Promise<TestConnectionResult> {
        setActionLoading(`test_${gateway}`, true);
        try {
            const res = await apiRequest<TestConnectionResult>(
                `/admin/api/governance/integrations/${gateway}/test`,
                'POST'
            );
            return res;
        } finally {
            setActionLoading(`test_${gateway}`, false);
        }
    }

    // High level initializer to load all essential baseline governance data
    async function initializeAll(): Promise<void> {
        isLoading.value = true;
        try {
            await Promise.allSettled([
                fetchThematicIntervals(),
                fetchSystemState(),
                fetchBackups(),
                fetchQueueStatus(),
                fetchSessions(),
                fetchIpRules(),
                fetchAuditLogs(),
                fetchSystemLogs({ limit: 100 }),
                fetchSystemHealth(),
                fetchIntegrations(),
            ]);
        } finally {
            isLoading.value = false;
        }
    }

    return {
        // State
        isLoading,
        actionLoading,
        error,
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

        // Kebijakan & Interval
        fetchThematicIntervals,
        saveThematicIntervals,
        recalculateThematicIntervals,
        saveConversionFactors,

        // Ketersediaan & Pemeliharaan
        fetchSystemState,
        updateMaintenanceMode,
        updateEmergencyBanner,
        updateFeatureFlags,
        clearCache,

        // Ketahanan & Backup
        fetchBackups,
        createBackup,
        deleteBackup,
        restoreBackup,
        getBackupDownloadUrl,
        fetchQueueStatus,

        // Keamanan & Akses
        fetchSessions,
        terminateSession,
        terminateAllOtherSessions,
        fetchIpRules,
        createIpRule,
        deleteIpRule,
        updateSecurityPolicies,

        // Observabilitas & Audit
        fetchAuditLogs,
        fetchSystemLogs,
        fetchSystemHealth,

        // Integrasi & Gateway
        fetchIntegrations,
        updateIntegrations,
        testIntegration,

        // Batch Init
        initializeAll,
    };
}
