<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { KECAMATAN_LIST, METRIC_LIST, KecamatanData, MetricDefinition, calculateQuantiles, formatAngka } from '../../data/banyumasData';
import WebGisHeader from '../../components/ternakverse/WebGisHeader.vue';
import MapSidebar from '../../components/ternakverse/MapSidebar.vue';
import LeafletWebGisMap from '../../components/ternakverse/LeafletWebGisMap.vue';
import SelectedRegionPanel from '../../components/ternakverse/SelectedRegionPanel.vue';
import RegionDetailAnalyticsModal from '../../components/ternakverse/RegionDetailAnalyticsModal.vue';
import DiagnosticBox from '../../components/ternakverse/DiagnosticBox.vue';
import ChatbotDrawer from '../../components/ternakverse/ChatbotDrawer.vue';
import { LogOut, SlidersHorizontal, X, BarChart3, HelpCircle } from 'lucide-vue-next';

const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>('wangon');
const selectedDesaId = ref<string | null>(null);
const mapEngine = ref<'real' | 'vector'>('real');
const leafletMapRef = ref<any>(null);
const isMobileFilterOpen = ref(false);
const isHelpOpen = ref(false);
const isRegionDetailOpen = ref(false);
const selectedKecamatanForDetail = ref<KecamatanData | null>(null);

function handleOpenRegionDetail(kec?: KecamatanData | null) {
    selectedKecamatanForDetail.value = kec ?? selectedKecamatan.value;
    isRegionDetailOpen.value = true;
}

const layerStates = ref<Record<string, boolean>>({
    batasKabupaten: true,
    batasKecamatan: true,
    batasDesa: false,
    heatmapPopulasi: false,
    fasilitasPeternakan: false,
    jalurTransportasi: false,
});

const quantiles = computed(() => {
    return calculateQuantiles(kecamatanList.value, selectedMetric.value.id as keyof KecamatanData);
});

const selectedKecamatan = computed(() => {
    if (!selectedKecamatanId.value) return null;
    return kecamatanList.value.find(k => k.id === selectedKecamatanId.value) || null;
});

function handleLogout() {
    router.post('/internal/logout');
}

function resetFilter() {
    selectedMetric.value = METRIC_LIST[0];
    activeKategori.value = 'Populasi';
    selectedKecamatanId.value = undefined;
    selectedDesaId.value = null;
    layerStates.value = {
        batasKabupaten: true,
        batasKecamatan: true,
        batasDesa: false,
        heatmapPopulasi: false,
        fasilitasPeternakan: false,
        jalurTransportasi: false,
    };
}

function handleSelectDesa(desa: any) {
    selectedDesaId.value = desa.id;
    if (selectedKecamatanId.value !== desa.kecamatan_id) {
        selectedKecamatanId.value = desa.kecamatan_id;
    }
    leafletMapRef.value?.focusDesa(desa);
}
</script>

<template>
    <Head title="Portal Dinas - WebGIS Kuantitatif Peternakan Banyumas" />

    <!-- 100vh FIXED GEOSPATIAL COMMAND CENTER APPLICATION SHELL (DINAS) -->
    <div class="h-screen w-screen overflow-hidden dark:bg-[#090909] bg-[#FFFFFF] dark:text-[#F5F5F5] text-[#171B17] flex flex-col font-ui antialiased selection:bg-emerald-500 selection:text-slate-950 transition-colors">
        
        <!-- TOP COMMAND BAR (WebGisHeader with Dinas Role) -->
        <WebGisHeader
            v-model:mapEngine="mapEngine"
            current-role="dinas"
            user-name="drh. Sulistyo"
            user-role-title="Kabid Peternakan"
            @resetFilter="resetFilter"
            @openHelp="isHelpOpen = true"
        />

        <!-- EXECUTIVE SITUATIONAL KPI & ACTION STRIP -->
        <div class="border-b dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#F4F5F2] px-4 md:px-6 py-2 shrink-0 transition-colors">
            <div class="w-full flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[10px] uppercase font-bold tracking-wider dark:text-[#B3B3B3] text-[#6E776D]">Ringkasan Eksekutif:</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums">
                        <span class="w-2 h-2 rounded-sm bg-emerald-500"></span>
                        Populasi Banyumas: <strong class="dark:text-white text-[#171B17] font-mono tabular-nums">{{ formatAngka(38450) }} ekor</strong>
                    </span>
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums">
                        <span class="w-2 h-2 rounded-sm bg-teal-400"></span>
                        Poskeswan: <strong class="dark:text-white text-[#171B17] font-mono tabular-nums">12 Unit</strong>
                    </span>
                    <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] font-mono tabular-nums">
                        <span class="w-2 h-2 rounded-sm bg-amber-400"></span>
                        RPH Aktif: <strong class="dark:text-white text-[#171B17] font-mono tabular-nums">5 Fasilitas</strong>
                    </span>
                    <span class="hidden lg:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg dark:bg-[#063D2D] bg-[#E8F8F1] border dark:border-[#00D084]/30 border-[#00A86B]/30 dark:text-[#00D084] text-[#00A86B] text-[11px] font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Surveilans: Bebas Antraks
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        href="/dinas/dashboard"
                        class="h-8 px-3 rounded-xl dark:bg-[#181818] bg-white hover:dark:bg-[#1E1E1E] hover:bg-[#E8EBE5] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#00A86B]/40 dark:text-[#F5F5F5] text-[#171B17] hover:dark:text-[#00D084] hover:text-[#00A86B] text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                        title="Buka Dashboard Eksekutif Bento Dinas"
                    >
                        <BarChart3 class="w-3.5 h-3.5 dark:text-[#00D084] text-[#00A86B]" />
                        <span>Dashboard Analitik</span>
                    </Link>
                    <button
                        type="button"
                        @click="handleLogout"
                        class="h-8 px-3 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#181818] bg-white text-xs gap-1.5 text-rose-500 hover:text-rose-600 hover:border-rose-500/50 inline-flex items-center transition-all cursor-pointer"
                        title="Keluar dari sesi Dinas"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span>Keluar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MAIN CONTAINER (Fixed 100vh) -->
        <main class="flex-1 flex overflow-hidden relative w-full h-[calc(100vh-104px)]">
            <!-- Sidebar Kiri Desktop (Pinned pada lg:block, Mode Dinas: Kuantitatif) -->
            <div class="hidden lg:flex w-[360px] h-full shrink-0 flex-col overflow-y-auto overflow-x-hidden dark:bg-[#090909] bg-[#FFFFFF] border-r dark:border-[#1E1E1E] border-[#E0E4DD] p-3 custom-sidebar-scroll transition-colors">
                <MapSidebar
                    :selected-metric="selectedMetric"
                    :active-kategori="activeKategori"
                    :kecamatan-list="kecamatanList"
                    :quantiles="quantiles"
                    current-role="dinas"
                    :selected-kecamatan-id="selectedKecamatanId"
                    v-model:layerStates="layerStates"
                    @update:active-kategori="activeKategori = $event"
                    @update:selected-metric="selectedMetric = $event"
                    @select-kecamatan="selectedKecamatanId = $event"
                />
            </div>

            <!-- Area Peta Tengah (Peta Asli Leaflet dengan Angka Kuantitatif) -->
            <div class="flex-1 h-full relative overflow-hidden">
                <LeafletWebGisMap
                    ref="leafletMapRef"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    current-role="dinas"
                    :selected-kecamatan-id="selectedKecamatanId"
                    :layer-states="layerStates"
                    @select-kecamatan="selectedKecamatanId = $event"
                    @select-desa="handleSelectDesa"
                />

                <!-- Floating Contextual Panels (Top-Right) -->
                <SelectedRegionPanel
                    v-if="selectedKecamatan"
                    :kecamatan="selectedKecamatan"
                    :selected-metric="selectedMetric"
                    current-role="dinas"
                    :selected-desa-id="selectedDesaId"
                    @close="selectedKecamatanId = undefined; selectedDesaId = null"
                    @drill-down="handleOpenRegionDetail"
                    @focus-desa="handleSelectDesa"
                />

                <!-- Floating Mobile Sidebar Trigger Button (< 1024px) -->
                <button
                    type="button"
                    @click="isMobileFilterOpen = true"
                    class="lg:hidden absolute bottom-6 left-6 z-30 h-11 px-3.5 rounded-xl dark:bg-[#111111]/95 bg-white/95 hover:dark:bg-[#181818] hover:bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] text-xs font-semibold flex items-center gap-2 shadow-xl backdrop-blur-md cursor-pointer transition-transform active:scale-95"
                    aria-label="Buka Filter Data & Layer Peta Dinas"
                >
                    <SlidersHorizontal class="w-4 h-4 dark:text-[#00D084] text-[#00A86B]" />
                    <span>Filter &amp; Layer</span>
                    <span class="px-1.5 py-0.5 rounded-md dark:bg-[#063D2D] bg-[#E8F8F1] dark:text-[#00D084] text-[#00A86B] border dark:border-[#00D084]/30 border-[#00A86B]/30 text-[10px] font-bold truncate max-w-[100px]">
                        {{ selectedMetric.label }}
                    </span>
                </button>
            </div>
        </main>

        <!-- MOBILE FILTER MODAL (< 1024px Centered Modal Dialog) -->
        <div
            v-if="isMobileFilterOpen"
            class="fixed inset-0 z-50 lg:hidden bg-black/60 dark:bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 select-none"
            @click.self="isMobileFilterOpen = false"
        >
            <div class="w-full max-w-md max-h-[88vh] dark:bg-[#111111] bg-[#FFFFFF] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b dark:border-[#1E1E1E] border-[#E0E4DD] shrink-0">
                    <div class="flex items-center gap-2">
                        <SlidersHorizontal class="w-4 h-4 dark:text-[#00D084] text-[#00A86B]" />
                        <h2 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17]">Kontrol WebGIS Dinas</h2>
                    </div>
                    <button
                        type="button"
                        @click="isMobileFilterOpen = false"
                        class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup Panel Filter Dinas"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden p-3 custom-sidebar-scroll">
                    <MapSidebar
                        :selected-metric="selectedMetric"
                        :active-kategori="activeKategori"
                        :kecamatan-list="kecamatanList"
                        :quantiles="quantiles"
                        current-role="dinas"
                        :selected-kecamatan-id="selectedKecamatanId"
                        v-model:layerStates="layerStates"
                        @update:active-kategori="activeKategori = $event"
                        @update:selected-metric="selectedMetric = $event; isMobileFilterOpen = false"
                        @select-kecamatan="selectedKecamatanId = $event; isMobileFilterOpen = false"
                    />
                </div>
            </div>
        </div>

        <!-- Help Modal Dialog -->
        <div
            v-if="isHelpOpen"
            class="fixed inset-0 z-50 bg-black/60 dark:bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 select-none"
            @click.self="isHelpOpen = false"
        >
            <div class="dark:bg-[#111111] bg-[#FFFFFF] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-6 w-full max-w-lg shadow-2xl animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b dark:border-[#1E1E1E] border-[#E0E4DD] mb-4">
                    <h3 class="font-display font-bold text-lg dark:text-[#F5F5F5] text-[#171B17] flex items-center gap-2">
                        <HelpCircle class="w-5 h-5 dark:text-[#00D084] text-[#00A86B]" />
                        Panduan WebGIS Petugas Dinas
                    </h3>
                    <button
                        type="button"
                        @click="isHelpOpen = false"
                        class="w-9 h-9 rounded-xl dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup Panduan"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs dark:text-[#B3B3B3] text-[#444C43] leading-relaxed">
                    <p>
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">1. Analisis Kuantitatif:</strong> Mode dinas menampilkan data numerik riil dan rentang kuantil angka per kecamatan dan desa.
                    </p>
                    <p>
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">2. Dashboard Analitik:</strong> Akses ringkasan eksekutif dan bento analitik melalui tombol "Dashboard Analitik".
                    </p>
                    <p>
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">3. Tindakan Kewaspadaan:</strong> Klik pada poligon kecamatan manapun untuk membuka rincian wilayah dan tombol investigasi admin balai.
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t dark:border-[#1E1E1E] border-[#E0E4DD] flex justify-end">
                    <button
                        type="button"
                        @click="isHelpOpen = false"
                        class="h-10 px-5 rounded-xl dark:bg-[#00D084] dark:text-[#062117] hover:dark:bg-[#00F0A0] bg-[#171B17] text-white hover:bg-[#2A312A] font-semibold text-xs transition-colors cursor-pointer shadow-xs"
                    >
                        Mengerti
                    </button>
                </div>
            </div>
        </div>

        <!-- Region Detail Analytics Modal (Dinas Mode) -->
        <RegionDetailAnalyticsModal
            :is-open="isRegionDetailOpen"
            :kecamatan="selectedKecamatanForDetail"
            :selected-metric="selectedMetric"
            current-role="dinas"
            @close="isRegionDetailOpen = false"
            @focus-desa="handleSelectDesa"
        />

        <DiagnosticBox />

        <!-- Floating Chatbot FAB -->
        <ChatbotDrawer :kecamatan-list="kecamatanList" />
    </div>
</template>

<style scoped>
.custom-sidebar-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb {
    background: #1E1E1E;
    border-radius: 4px;
}
:root:not(.dark) .custom-sidebar-scroll::-webkit-scrollbar-thumb {
    background: #E0E4DD;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: #00D084;
}
</style>

