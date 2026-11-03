<script setup lang="ts">
import SelectedRegionPanel from './SelectedRegionPanel.vue';
import type { KecamatanData, MetricDefinition } from '../../data/banyumasData';
import type { DesaData } from '../../data/banyumasDesaData';

const props = defineProps<{
    kecamatan: KecamatanData;
    selectedMetric: MetricDefinition;
    currentRole: 'publik' | 'dinas' | 'admin';
    selectedDesaId?: string | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'drillDown', kec: KecamatanData): void;
    (e: 'focusDesa', desa: DesaData): void;
}>();
</script>

<template>
    <SelectedRegionPanel
        :kecamatan="props.kecamatan"
        :selected-metric="props.selectedMetric"
        :current-role="props.currentRole"
        :selected-desa-id="props.selectedDesaId"
        @close="emit('close')"
        @drill-down="emit('drillDown', $event)"
        @focus-desa="emit('focusDesa', $event)"
        @open-rules="emit('drillDown', props.kecamatan)"
    />
</template>

