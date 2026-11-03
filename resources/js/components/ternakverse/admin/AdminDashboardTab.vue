<script setup lang="ts">
import type { AdminTab, AuditLogItem } from '../../../composables/ternakverse/admin/useAdminPortal';
import AdminWelcomeBanner from './dashboard/AdminWelcomeBanner.vue';
import AdminKpiCards from './dashboard/AdminKpiCards.vue';
import AdminAnalyticsRow from './dashboard/AdminAnalyticsRow.vue';
import AdminOperationalRow from './dashboard/AdminOperationalRow.vue';

interface Props {
    searchQueryDashboard?: string;
    auditLogs?: AuditLogItem[];
}

const props = withDefaults(defineProps<Props>(), {
    searchQueryDashboard: '',
    auditLogs: () => [],
});

const emit = defineEmits<{
    (e: 'update:searchQueryDashboard', val: string): void;
    (e: 'search'): void;
    (e: 'openImportModal'): void;
    (e: 'startCreateNew'): void;
    (e: 'selectTab', tab: AdminTab): void;
}>();
</script>

<template>
    <div class="w-full space-y-6 pb-12">
        <!-- Row 1: Welcome Banner -->
        <AdminWelcomeBanner
            @open-import-modal="emit('openImportModal')"
            @start-create-new="emit('startCreateNew')"
        />

        <!-- Row 2: 5 Top KPI Cards -->
        <AdminKpiCards />

        <!-- Row 3: 3 Columns Analytics (Bar Chart, Donut Chart, Mini Map) -->
        <AdminAnalyticsRow
            @open-web-gis="emit('selectTab', 'webgis')"
            @openWebGis="emit('selectTab', 'webgis')"
        />

        <!-- Row 4: 3 Columns Operational (Aktivitas Table, Riwayat Input, Status Sinkronisasi & EWS) -->
        <AdminOperationalRow
            :audit-logs="props.auditLogs"
            @select-tab="(tab) => emit('selectTab', tab as AdminTab)"
        />
    </div>
</template>
