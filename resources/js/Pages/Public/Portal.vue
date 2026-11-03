<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { SharedData, User } from '../../types';
import { KECAMATAN_LIST, METRIC_LIST, KecamatanData, MetricDefinition, calculateQuantiles } from '../../data/banyumasData';
import WebGisHeader from '../../components/ternakverse/WebGisHeader.vue';
import MapSidebar from '../../components/ternakverse/MapSidebar.vue';
import LeafletWebGisMap from '../../components/ternakverse/LeafletWebGisMap.vue';
import SelectedRegionPanel from '../../components/ternakverse/SelectedRegionPanel.vue';
import RegionDetailAnalyticsModal from '../../components/ternakverse/RegionDetailAnalyticsModal.vue';
import ChatbotDrawer from '../../components/ternakverse/ChatbotDrawer.vue';
import DiagnosticBox from '../../components/ternakverse/DiagnosticBox.vue';
import { HelpCircle, X, SlidersHorizontal } from 'lucide-vue-next';

// Autentikasi & Role Pengguna Dinamis
const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user as (User & { role?: string }) | undefined);

const currentRole = computed<'publik' | 'dinas' | 'admin'>(() => {
    const role = authUser.value?.role;
    if (role === 'admin') return 'admin';
    if (role === 'stakeholder') return 'dinas';
    return 'publik';
});

const userName = computed(() => authUser.value?.name || 'Warga Banyumas');

const userRoleTitle = computed(() => {
    if (currentRole.value === 'admin') return 'Super Admin';
    if (currentRole.value === 'dinas') return 'Petugas Dinas';
    return 'Publik';
});

// State Metrik & Wilayah Aktif
const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>(undefined);
const selectedDesaId = ref<string | null>(null);

// Pilihan Mesin Peta: Peta Asli Leaflet vs Peta Vektor SVG
const mapEngine = ref<'real' | 'vector'>('real');
const leafletMapRef = ref<any>(null);

// Layer States
const layerStates = ref<Record<string, boolean>>({
    batasKabupaten: true,
    batasKecamatan: true,
    batasDesa: false,
    heatmapPopulasi: false,
    fasilitasPeternakan: false,
    jalurTransportasi: false,
});

// Modals & Drawer State
const isChatOpen = ref(false);
const isHelpOpen = ref(false);
const isMobileFilterOpen = ref(false);
const isRegionDetailOpen = ref(false);
const selectedKecamatanForDetail = ref<KecamatanData | null>(null);

function handleOpenRegionDetail(kec?: KecamatanData | null) {
    selectedKecamatanForDetail.value = kec ?? selectedKecamatan.value;
    isRegionDetailOpen.value = true;
}

// Perhitungan Kuantil 5 Kelas
const quantiles = computed(() => {
    return calculateQuantiles(kecamatanList.value, selectedMetric.value.id as keyof KecamatanData);
});

// Kecamatan yang dipilih untuk popup & panel kontekstual
const selectedKecamatan = computed(() => {
    if (!selectedKecamatanId.value) return null;
    return kecamatanList.value.find(k => k.id === selectedKecamatanId.value) || null;
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

function handleSelectDesa(desa: any) {
    selectedDesaId.value = desa.id;
    if (selectedKecamatanId.value !== desa.kecamatan_id) {
        selectedKecamatanId.value = desa.kecamatan_id;
    }
    leafletMapRef.value?.focusDesa(desa);
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

function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        selectedKecamatanId.value = undefined;
        selectedDesaId.value = null;
        isChatOpen.value = false;
        isHelpOpen.value = false;
        isMobileFilterOpen.value = false;
        isRegionDetailOpen.value = false;
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Head title="Ternakverse - Portal Publik WebGIS Peternakan Kab. Banyumas" />

    <!-- 100vh FIXED GEOSPATIAL COMMAND CENTER APPLICATION SHELL -->
    <div class="h-screen w-screen overflow-hidden dark:bg-[#090909] bg-[#FFFFFF] dark:text-[#F5F5F5] text-[#171B17] flex flex-col font-ui antialiased selection:bg-emerald-500 selection:text-slate-950 transition-colors">
        
        <!-- TOP COMMAND BAR (WebGisHeader) -->
        <WebGisHeader
            v-model:mapEngine="mapEngine"
            :current-role="currentRole"
            :user-name="userName"
            :user-role-title="userRoleTitle"
            @resetFilter="resetFilter"
            @openHelp="isHelpOpen = true"
        />

        <!-- MAIN CONTAINER (Fixed 100vh minus Header 56px) -->
        <main class="flex-1 flex overflow-hidden relative w-full h-[calc(100vh-56px)]">
            <!-- Left Sidebar Desktop (360px) -->
            <div class="hidden lg:flex w-[360px] h-full shrink-0 flex-col overflow-y-auto overflow-x-hidden dark:bg-[#090909] bg-[#FFFFFF] border-r dark:border-[#1E1E1E] border-[#E0E4DD] p-3 custom-sidebar-scroll transition-colors">
                <MapSidebar
                    :selected-metric="selectedMetric"
                    :active-kategori="activeKategori"
                    :kecamatan-list="kecamatanList"
                    :quantiles="quantiles"
                    :current-role="currentRole"
                    :selected-kecamatan-id="selectedKecamatanId"
                    v-model:layerStates="layerStates"
                    @update:active-kategori="activeKategori = $event"
                    @update:selected-metric="selectedMetric = $event"
                    @select-kecamatan="handleSelectKecamatan"
                />
            </div>

            <!-- Map Area (Fills Remaining Space) -->
            <div class="flex-1 h-full relative overflow-hidden">
                <!-- Peta Asli Leaflet Banyumas -->
                <LeafletWebGisMap
                    ref="leafletMapRef"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    :current-role="currentRole"
                    :selected-kecamatan-id="selectedKecamatanId"
                    :layer-states="layerStates"
                    @select-kecamatan="handleSelectKecamatan"
                    @select-desa="handleSelectDesa"
                />

                <!-- Floating Contextual Panels (Top-Right) -->
                <SelectedRegionPanel
                    v-if="selectedKecamatan"
                    :kecamatan="selectedKecamatan"
                    :selected-metric="selectedMetric"
                    :current-role="currentRole"
                    :selected-desa-id="selectedDesaId"
                    @close="selectedKecamatanId = undefined; selectedDesaId = null"
                    @drill-down="handleOpenRegionDetail"
                    @open-rules="handleOpenRegionDetail"
                    @focus-desa="handleSelectDesa"
                />

                <!-- Floating Mobile Sidebar Trigger Button (< 1024px) -->
                <button
                    type="button"
                    @click="isMobileFilterOpen = true"
                    class="lg:hidden absolute bottom-6 left-6 z-30 h-11 px-3.5 rounded-xl dark:bg-[#111111]/95 bg-white/95 hover:dark:bg-[#181818] hover:bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#F5F5F5] text-[#171B17] text-xs font-semibold flex items-center gap-2 shadow-xl backdrop-blur-md cursor-pointer transition-transform active:scale-95"
                    aria-label="Buka Filter Data & Layer Peta"
                >
                    <SlidersHorizontal class="w-4 h-4 dark:text-[#00D084] text-[#00A86B]" />
                    <span>Filter &amp; Layer</span>
                    <span class="px-1.5 py-0.5 rounded-md dark:bg-[#063D2D] bg-[#E8F8F1] dark:text-[#00D084] text-[#00A86B] border dark:border-[#00D084]/30 border-[#00A86B]/30 text-[10px] font-bold truncate max-w-[100px]">
                        {{ selectedMetric.label }}
                    </span>
                </button>
            </div>

            <!-- Floating Chatbot (Bottom-Right) -->
            <ChatbotDrawer
                :kecamatan-list="kecamatanList"
                :is-open="isChatOpen"
                @open="isChatOpen = true"
                @close="isChatOpen = false"
            />
        </main>

        <!-- Mobile Filter Modal (< 1024px Centered Modal Dialog) -->
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
                        <h2 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17]">Kontrol WebGIS</h2>
                    </div>
                    <button
                        type="button"
                        @click="isMobileFilterOpen = false"
                        class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup Kontrol WebGIS"
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
                        :current-role="currentRole"
                        :selected-kecamatan-id="selectedKecamatanId"
                        v-model:layerStates="layerStates"
                        @update:active-kategori="activeKategori = $event"
                        @update:selected-metric="selectedMetric = $event; isMobileFilterOpen = false"
                        @select-kecamatan="handleSelectKecamatan($event); isMobileFilterOpen = false"
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
                        Panduan Peta Publik
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
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">1. Peta Asli Banyumas:</strong> Peta menampilkan batas geografis riil 27 kecamatan dan 331 desa/kelurahan di Kabupaten Banyumas dengan basemap resmi Esri Dark Canvas atau Citra Satelit.
                    </p>
                    <p>
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">2. Kategori Kualitatif:</strong> Portal Publik menyajikan informasi dalam kategori visual (Sangat Tinggi, Tinggi, Sedang, Rendah, Sangat Rendah) untuk kenyamanan masyarakat umum.
                    </p>
                    <p>
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">3. Interaktivitas:</strong> Klik pada poligon kecamatan manapun untuk memunculkan ringkasan status wilayah dan kesehatan hewan di panel kanan atas.
                    </p>
                    <p>
                        <strong class="dark:text-[#F5F5F5] text-[#171B17]">4. Layer Geospasial:</strong> Gunakan menu switch layer pada sidebar kiri untuk mengaktifkan batas desa, heatmap populasi, atau titik fasilitas peternakan.
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

        <!-- Region Detail Analytics Modal -->
        <RegionDetailAnalyticsModal
            :is-open="isRegionDetailOpen"
            :kecamatan="selectedKecamatanForDetail"
            :selected-metric="selectedMetric"
            current-role="publik"
            @close="isRegionDetailOpen = false"
            @focus-desa="handleSelectDesa"
        />

        <!-- Diagnostic Box -->
        <DiagnosticBox />
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
