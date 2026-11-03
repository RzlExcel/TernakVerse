<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import type { AdminTab } from '@/composables/ternakverse/admin/useAdminPortal';
import {
    KECAMATAN_LIST,
    METRIC_LIST,
    type KecamatanData,
    type MetricDefinition,
    calculateQuantiles,
} from '@/data/banyumasData';
import type { DesaData } from '@/data/banyumasDesaData';
import MapSidebar from '../../MapSidebar.vue';
import LeafletWebGisMap from '../../LeafletWebGisMap.vue';
import SelectedRegionPanel from '../../SelectedRegionPanel.vue';
import RegionDetailAnalyticsModal from '../../RegionDetailAnalyticsModal.vue';
import {
    Compass,
    PlusSquare,
    ShieldAlert,
    FileDown,
    LayoutDashboard,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';

const emit = defineEmits<{
    (e: 'selectTab', tab: AdminTab): void;
    (e: 'startCreateNew'): void;
}>();

// State Metrik & Wilayah Aktif
const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>(undefined);
const selectedDesaId = ref<string | null>(null);

// Leaflet Map Ref
const leafletMapRef = ref<any>(null);
const isMobileFilterOpen = ref(false);
const isRegionDetailOpen = ref(false);
const selectedKecamatanForDetail = ref<KecamatanData | null>(null);

function handleOpenRegionDetail(kec?: KecamatanData | null) {
    selectedKecamatanForDetail.value = kec ?? selectedKecamatan.value;
    isRegionDetailOpen.value = true;
}

function handleEditRegion(kec: KecamatanData) {
    isRegionDetailOpen.value = false;
    emit('startCreateNew');
}

// Layer States
const layerStates = ref<Record<string, boolean>>({
    batasKabupaten: true,
    batasKecamatan: true,
    batasDesa: false,
    heatmapPopulasi: false,
    fasilitasPeternakan: false,
    jalurTransportasi: false,
});

// Perhitungan Kuantil 5 Kelas
const quantiles = computed(() => {
    return calculateQuantiles(kecamatanList.value, selectedMetric.value.id as keyof KecamatanData);
});

// Kecamatan yang dipilih untuk popup & panel kontekstual
const selectedKecamatan = computed(() => {
    if (!selectedKecamatanId.value) return null;
    return kecamatanList.value.find((k: KecamatanData) => k.id === selectedKecamatanId.value) || null;
});

function handleSelectKecamatan(id: string) {
    if (!id || selectedKecamatanId.value === id) {
        selectedKecamatanId.value = undefined;
        selectedDesaId.value = null;
    } else {
        selectedKecamatanId.value = id;
        selectedDesaId.value = null;
    }
}

function handleSelectDesa(desa: DesaData) {
    selectedDesaId.value = desa.id;
    if (selectedKecamatanId.value !== desa.kecamatan_id) {
        selectedKecamatanId.value = desa.kecamatan_id;
    }
    leafletMapRef.value?.focusDesa(desa);
}

onMounted(() => {
    nextTick(() => {
        setTimeout(() => {
            leafletMapRef.value?.invalidateSize?.();
        }, 200);
    });
});
</script>

<template>
    <div class="h-full w-full relative flex flex-col overflow-hidden rounded-2xl border border-[#19334A] bg-[#050B14] shadow-2xl">
        <!-- TOP ADMIN SPATIAL CONTROL BAR -->
        <header class="h-12 min-h-[48px] px-3.5 py-2 border-b border-[#19334A] bg-[#091525] flex items-center justify-between gap-3 shrink-0 select-none z-10">
            <!-- Left: Brand badge & Live Status Pill -->
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#103F3A] to-[#0D1D30] border border-emerald-500/40 flex items-center justify-center text-[#00B98B] shrink-0 shadow-sm">
                    <Compass class="w-4 h-4 text-[#00B98B] stroke-[2.2]" />
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <span class="font-display font-extrabold text-xs sm:text-sm text-slate-100 tracking-wide truncate">
                        SUPER ADMIN GEOSPATIAL COMMAND CENTER
                    </span>
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-[10px] font-mono font-semibold text-emerald-400 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Telemetri
                    </span>
                </div>
            </div>

            <!-- Right: Quick Admin Action Buttons -->
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <!-- Tambah Komoditas -->
                <button
                    type="button"
                    @click="emit('startCreateNew')"
                    class="h-8 px-2.5 sm:px-3 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs inline-flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm shadow-emerald-950/40"
                    title="Tambah Komoditas / Data Baru"
                >
                    <PlusSquare class="w-3.5 h-3.5 stroke-[2.2]" />
                    <span class="hidden md:inline">Tambah Komoditas</span>
                    <span class="md:hidden">Tambah</span>
                </button>

                <!-- Monitoring EWS -->
                <button
                    type="button"
                    @click="emit('selectTab', 'ews')"
                    class="h-8 px-2.5 sm:px-3 rounded-lg bg-[#0D1D30] hover:bg-[#132A40] border border-amber-500/30 text-amber-400 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                    title="Monitoring Early Warning System (EWS)"
                >
                    <ShieldAlert class="w-3.5 h-3.5 text-amber-400" />
                    <span class="hidden md:inline">Monitoring EWS</span>
                    <span class="md:hidden">EWS</span>
                </button>

                <!-- Ekspor Spasial -->
                <button
                    type="button"
                    @click="emit('selectTab', 'laporan')"
                    class="h-8 px-2.5 sm:px-3 rounded-lg bg-[#0D1D30] hover:bg-[#132A40] border border-[#19334A] text-slate-300 hover:text-white text-xs font-medium inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                    title="Buka Tab Ekspor Laporan Spasial"
                >
                    <FileDown class="w-3.5 h-3.5 text-slate-400" />
                    <span class="hidden lg:inline">Ekspor Spasial</span>
                    <span class="lg:hidden">Ekspor</span>
                </button>

                <!-- Kembali ke Dashboard -->
                <button
                    type="button"
                    @click="emit('selectTab', 'dashboard')"
                    class="h-8 px-2.5 sm:px-3 rounded-lg bg-[#0D1D30] hover:bg-[#132A40] border border-[#19334A] text-slate-300 hover:text-emerald-400 text-xs font-medium inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                    title="Kembali ke Dashboard Utama Admin"
                >
                    <LayoutDashboard class="w-3.5 h-3.5 text-emerald-400" />
                    <span class="hidden sm:inline">Dashboard</span>
                </button>
            </div>
        </header>

        <!-- MAIN BODY: MAP SIDEBAR + LEAFLET CANVAS + SELECTED REGION PANEL -->
        <div class="flex-1 flex overflow-hidden relative w-full h-[calc(100%-48px)]">
            <!-- Left Sidebar (Desktop >= 1024px) -->
            <aside class="hidden lg:flex w-[350px] xl:w-[380px] h-full shrink-0 flex-col overflow-y-auto overflow-x-hidden bg-[#091525] border-r border-[#19334A] p-3 custom-sidebar-scroll select-none z-10">
                <MapSidebar
                    :selected-metric="selectedMetric"
                    :active-kategori="activeKategori"
                    :kecamatan-list="kecamatanList"
                    :quantiles="quantiles"
                    current-role="admin"
                    :selected-kecamatan-id="selectedKecamatanId"
                    v-model:layerStates="layerStates"
                    @update:active-kategori="activeKategori = $event"
                    @update:selected-metric="selectedMetric = $event"
                    @select-kecamatan="handleSelectKecamatan"
                    @open-chat="emit('selectTab', 'data')"
                />
            </aside>

            <!-- Leaflet WebGIS Canvas -->
            <div class="flex-1 h-full relative overflow-hidden bg-[#050B14]">
                <LeafletWebGisMap
                    ref="leafletMapRef"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    current-role="admin"
                    :selected-kecamatan-id="selectedKecamatanId"
                    :layer-states="layerStates"
                    @select-kecamatan="handleSelectKecamatan"
                    @select-desa="handleSelectDesa"
                />

                <!-- Right Side Floating Panel: SelectedRegionPanel for Inspection -->
                <SelectedRegionPanel
                    v-if="selectedKecamatan"
                    :kecamatan="selectedKecamatan"
                    :selected-metric="selectedMetric"
                    current-role="admin"
                    :selected-desa-id="selectedDesaId"
                    @close="selectedKecamatanId = undefined; selectedDesaId = null"
                    @drill-down="handleOpenRegionDetail"
                    @focus-desa="handleSelectDesa"
                />

                <!-- Floating Mobile Sidebar Trigger Button (< 1024px) -->
                <button
                    type="button"
                    @click="isMobileFilterOpen = true"
                    class="lg:hidden absolute bottom-5 left-5 z-30 h-10 px-3.5 rounded-xl bg-[#091525]/95 hover:bg-[#0D1D30] border border-emerald-500/40 text-slate-100 text-xs font-semibold flex items-center gap-2 shadow-2xl backdrop-blur-md cursor-pointer transition-transform active:scale-95"
                    aria-label="Buka Filter Data & Layer Peta Admin"
                >
                    <SlidersHorizontal class="w-4 h-4 text-emerald-400" />
                    <span>Filter &amp; Layer</span>
                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold truncate max-w-[100px]">
                        {{ selectedMetric.label }}
                    </span>
                </button>
            </div>
        </div>

        <!-- MOBILE FILTER MODAL (< 1024px) -->
        <div
            v-if="isMobileFilterOpen"
            class="fixed inset-0 z-50 lg:hidden bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 select-none"
            @click.self="isMobileFilterOpen = false"
        >
            <div class="w-full max-w-md max-h-[88vh] bg-[#091525] border border-[#19334A] rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between p-4 border-b border-[#19334A] shrink-0">
                    <div class="flex items-center gap-2">
                        <SlidersHorizontal class="w-4 h-4 text-emerald-400" />
                        <h2 class="font-display font-bold text-sm text-slate-100">Kontrol WebGIS Admin</h2>
                    </div>
                    <button
                        type="button"
                        @click="isMobileFilterOpen = false"
                        class="w-8 h-8 rounded-lg bg-[#0D1D30] border border-[#19334A] text-slate-400 hover:text-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup Panel Filter Admin"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden p-3 custom-sidebar-scroll">
                    <MapSidebar
                        :selected-metric="selectedMetric"
                        :active-kategori="activeKategori"
                        :kecamatan-list="kecamatanList"
                        :quantiles="quantiles"
                        current-role="admin"
                        :selected-kecamatan-id="selectedKecamatanId"
                        v-model:layerStates="layerStates"
                        @update:active-kategori="activeKategori = $event"
                        @update:selected-metric="selectedMetric = $event; isMobileFilterOpen = false"
                        @select-kecamatan="(id) => { handleSelectKecamatan(id); isMobileFilterOpen = false; }"
                        @open-chat="emit('selectTab', 'data'); isMobileFilterOpen = false"
                    />
                </div>
            </div>
        </div>

        <!-- Region Detail Analytics Modal (Admin Spatial Mode) -->
        <RegionDetailAnalyticsModal
            :is-open="isRegionDetailOpen"
            :kecamatan="selectedKecamatanForDetail"
            :selected-metric="selectedMetric"
            current-role="admin"
            @close="isRegionDetailOpen = false"
            @edit-region="handleEditRegion"
            @focus-desa="handleSelectDesa"
        />
    </div>
</template>
