<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { KECAMATAN_LIST, METRIC_LIST, KecamatanData, MetricDefinition, calculateQuantiles, formatAngka } from '../../data/banyumasData';
import MapSidebar from '../../components/ternakverse/MapSidebar.vue';
import LeafletWebGisMap from '../../components/ternakverse/LeafletWebGisMap.vue';
import WebGisMap from '../../components/ternakverse/WebGisMap.vue';
import KecamatanPopup from '../../components/ternakverse/KecamatanPopup.vue';
import DiagnosticBox from '../../components/ternakverse/DiagnosticBox.vue';
import { Compass, LogOut, Download, MapPin, Layers, FileSpreadsheet } from 'lucide-vue-next';

const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>('wangon');
const mapEngine = ref<'real' | 'vector'>('real');
const leafletMapRef = ref<any>(null);

const quantiles = computed(() => {
    return calculateQuantiles(kecamatanList.value, selectedMetric.value.id as keyof KecamatanData);
});

const selectedKecamatan = computed(() => {
    if (!selectedKecamatanId.value) return null;
    return kecamatanList.value.find(k => k.id === selectedKecamatanId.value) || null;
});

function handleLogout() {
    router.visit('/internal/login');
}
</script>

<template>
    <Head title="Portal Dinas - WebGIS Kuantitatif Peternakan Banyumas" />

    <div class="min-h-screen bg-[#0A0D0E] text-[#F4F7F5] flex flex-col font-ui antialiased selection:bg-[#39FF14] selection:text-[#04110A]">
        <!-- NAVBAR PORTAL DINAS (Badge Statis Tunggal: [📋 Petugas Dinas], Bab 11.1) -->
        <header class="h-16 w-full bg-[#10171A] border-b-2 border-[#243034] px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 select-none">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-12 bg-[#0A0D0E] border-2 border-[#19F5D4] flex items-center justify-center text-[#19F5D4] shadow-[3px_3px_0_#19F5D4]">
                    <Compass class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-extrabold text-xl tracking-tight text-[#F4F7F5]">TERNAKVERSE</span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#19F5D4]/20 text-[#19F5D4] border border-[#19F5D4]">
                            PORTAL DINAS
                        </span>
                    </div>
                    <p class="text-[12px] text-[#8FA1A0] hidden sm:block leading-none mt-0.5">Analisis Kuantitatif & Kewaspadaan Wabah Peternakan</p>
                </div>
            </div>

            <!-- Tengah: Badge Status Peran Tunggal Statis (Bab 6 & 11.1) -->
            <div class="hidden md:flex items-center gap-3">
                <div class="px-3.5 py-1.5 rounded-full border-2 border-[#19F5D4] bg-[#0A0D0E] text-[#19F5D4] text-xs font-extrabold flex items-center gap-2 shadow-[2px_2px_0_#19F5D4]">
                    <span class="w-2 h-2 rounded-full bg-[#19F5D4] animate-pulse"></span>
                    <span>📋 Petugas Dinas: drh. Sulistyo (Kabid Peternakan)</span>
                </div>
            </div>

            <!-- Kanan: Pilihan Mesin Peta & Tombol Logout -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center bg-[#0A0D0E] p-1 rounded-full border border-[#243034] text-xs">
                    <button
                        @click="mapEngine = 'real'"
                        class="px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5"
                        :class="mapEngine === 'real' ? 'bg-[#19F5D4] text-[#04110A] shadow-[2px_2px_0_#243034]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                    >
                        <MapPin class="w-3.5 h-3.5" />
                        <span>Peta Asli Banyumas</span>
                    </button>
                    <button
                        @click="mapEngine = 'vector'"
                        class="px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5"
                        :class="mapEngine === 'vector' ? 'bg-[#39FF14] text-[#04110A] shadow-[2px_2px_0_#243034]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>Vektor Stiker</span>
                    </button>
                </div>

                <!-- Tombol Keluar (Logout Sesi, Bab 11.1) -->
                <button
                    @click="handleLogout"
                    class="btn-secondary-tactile h-9 px-3.5 text-xs gap-1.5 text-[#FF5C5C] hover:border-[#FF5C5C]"
                    title="Keluar dari sesi Dinas"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>Keluar</span>
                </button>
            </div>
        </header>

        <!-- KONTEN WEBGIS KUANTITATIF DINAS -->
        <main class="p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto flex-1 flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Kiri (Mode Dinas: Menampilkan Angka & Rentang Kuantitatif) -->
            <MapSidebar
                :selected-metric="selectedMetric"
                :active-kategori="activeKategori"
                :kecamatan-list="kecamatanList"
                :quantiles="quantiles"
                current-role="dinas"
                :selected-kecamatan-id="selectedKecamatanId"
                @update:active-kategori="activeKategori = $event"
                @update:selected-metric="selectedMetric = $event"
                @select-kecamatan="selectedKecamatanId = $event"
                @open-chat="selectedKecamatanId = 'ajibarang'"
            />

            <!-- Area Peta Tengah (Peta Asli Leaflet dengan Angka Kuantitatif) -->
            <div class="flex-1 relative flex flex-col">
                <LeafletWebGisMap
                    ref="leafletMapRef"
                    v-if="mapEngine === 'real'"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    current-role="dinas"
                    :selected-kecamatan-id="selectedKecamatanId"
                    @select-kecamatan="selectedKecamatanId = $event"
                />

                <WebGisMap
                    v-else
                    :kecamatan-list="kecamatanList"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    current-role="dinas"
                    :selected-kecamatan-id="selectedKecamatanId"
                    @select-kecamatan="selectedKecamatanId = $event"
                />

                <!-- Popup Card Rincian Kuantitatif Lengkap -->
                <KecamatanPopup
                    v-if="selectedKecamatan"
                    :kecamatan="selectedKecamatan"
                    :selected-metric="selectedMetric"
                    current-role="dinas"
                    @close="selectedKecamatanId = undefined"
                    @drill-down="router.visit('/internal/admin')"
                    @focus-desa="leafletMapRef?.focusDesa($event)"
                />
            </div>
        </main>

        <DiagnosticBox />
    </div>
</template>
