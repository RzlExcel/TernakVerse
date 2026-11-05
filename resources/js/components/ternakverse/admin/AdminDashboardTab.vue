<script setup lang="ts">
import { ref } from 'vue';
import type { AdminTab, AuditLogItem, RphFacility as RphItem } from '../../../composables/ternakverse/admin/useAdminPortal';
import type { KecamatanData } from '../../../data/banyumasData';
import AdminWelcomeBanner from './dashboard/AdminWelcomeBanner.vue';
import AdminKpiCards from './dashboard/AdminKpiCards.vue';
import AdminAnalyticsRow from './dashboard/AdminAnalyticsRow.vue';
import AdminOperationalRow from './dashboard/AdminOperationalRow.vue';

interface Props {
    searchQueryDashboard?: string;
    auditLogs?: AuditLogItem[];
    kecamatanList?: KecamatanData[];
    rphList?: RphItem[];
    selectedYear?: number | 'all';
    dateRangeLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    searchQueryDashboard: '',
    auditLogs: () => [],
    kecamatanList: () => [],
    rphList: () => [],
    selectedYear: 2026,
    dateRangeLabel: 'Tahun 2026 (Terbaru)',
});

const selectedCardId = ref<'card-populasi' | 'card-produksi' | 'card-penyakit' | 'card-fasilitas'>('card-populasi');

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

        <!-- Row 2: 4 Top KPI Cards (Interactive, Live Data) -->
        <AdminKpiCards
            :selectedCardId="selectedCardId"
            :kecamatanList="props.kecamatanList"
            :rphList="props.rphList"
            :selectedYear="props.selectedYear"
            :dateRangeLabel="props.dateRangeLabel"
            @selectCard="(id) => selectedCardId = id as any"
        />

        <!-- Row 3: 3 Columns Analytics (Bar Chart, Donut Chart, Mini Map) -->
        <AdminAnalyticsRow
            :selectedCardId="selectedCardId"
            :kecamatanList="props.kecamatanList"
            :rphList="props.rphList"
            :selectedYear="props.selectedYear"
            :dateRangeLabel="props.dateRangeLabel"
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
