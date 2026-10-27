<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { KECAMATAN_LIST, METRIC_LIST, KecamatanData, MetricDefinition, calculateQuantiles } from '../../data/banyumasData';
import { REAL_BANYUMAS_KECAMATAN, calculateRealQuantiles } from '../../data/banyumasGeoData';
import MapSidebar from '../../components/ternakverse/MapSidebar.vue';
import LeafletWebGisMap from '../../components/ternakverse/LeafletWebGisMap.vue';
import WebGisMap from '../../components/ternakverse/WebGisMap.vue';
import KecamatanPopup from '../../components/ternakverse/KecamatanPopup.vue';
import ChatbotDrawer from '../../components/ternakverse/ChatbotDrawer.vue';
import DiagnosticBox from '../../components/ternakverse/DiagnosticBox.vue';
import { Compass, HelpCircle, RotateCcw, X, MapPin, Layers } from 'lucide-vue-next';

// State Metrik Aktif
const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>('wangon');

// Pilihan Mesin Peta: Peta Asli Leaflet (Real Tiles) vs Peta Vektor SVG
const mapEngine = ref<'real' | 'vector'>('real');
const leafletMapRef = ref<any>(null);

// Modals State
const isChatOpen = ref(false);
const isHelpOpen = ref(false);

// Perhitungan Kuantil 5 Kelas
const quantiles = computed(() => {
    return calculateQuantiles(kecamatanList.value, selectedMetric.value.id as keyof KecamatanData);
});

// Kecamatan yang dipilih untuk popup
const selectedKecamatan = computed(() => {
    if (!selectedKecamatanId.value) return null;
    return kecamatanList.value.find(k => k.id === selectedKecamatanId.value) || null;
});

function handleSelectKecamatan(id: string) {
    if (selectedKecamatanId.value === id) {
        selectedKecamatanId.value = undefined;
    } else {
        selectedKecamatanId.value = id;
    }
}

function resetFilter() {
    selectedMetric.value = METRIC_LIST[0];
    activeKategori.value = 'Populasi';
    selectedKecamatanId.value = undefined;
}

function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        selectedKecamatanId.value = undefined;
        isChatOpen.value = false;
        isHelpOpen.value = false;
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

    <div class="min-h-screen bg-[#0A0D0E] text-[#F4F7F5] flex flex-col font-ui antialiased selection:bg-[#39FF14] selection:text-[#04110A]">
        
        <!-- NAVBAR PORTAL PUBLIK (Bab 11.1: Bebas tombol peran, bebas login, bebas ikon gembok) -->
        <header class="h-16 w-full bg-[#10171A] border-b-2 border-[#243034] px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 select-none">
            <!-- Brand Kiri (Logo Pemkab + Nama WebGIS) -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-12 bg-[#0A0D0E] border-2 border-[#19F5D4] flex items-center justify-center text-[#19F5D4] shadow-[3px_3px_0_#19F5D4]">
                    <Compass class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-extrabold text-xl tracking-tight text-[#F4F7F5]">TERNAKVERSE</span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#243034] text-[#19F5D4] border border-[#3A4A4F]">PUBLIK</span>
                    </div>
                    <p class="text-[12px] text-[#8FA1A0] hidden sm:block leading-none mt-0.5">Peta Persebaran Populasi Ternak Kabupaten Banyumas</p>
                </div>
            </div>

            <!-- Bagian Kanan Navbar Publik: Tombol Bantuan & Reset Filter (Sesuai Bab 11.1) -->
            <div class="flex items-center gap-2.5">
                <!-- Toggle Mode Mesin Peta (Peta Asli GIS vs Vektor) -->
                <div class="hidden sm:flex items-center bg-[#0A0D0E] p-1 rounded-full border border-[#243034] text-xs">
                    <button
                        @click="mapEngine = 'real'"
                        class="px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5"
                        :class="mapEngine === 'real' ? 'bg-[#19F5D4] text-[#04110A] shadow-[2px_2px_0_#243034]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                        title="Peta Asli Banyumas dengan Basemap Real Satelit/Jalan"
                    >
                        <MapPin class="w-3.5 h-3.5" />
                        <span>Peta Asli Banyumas</span>
                    </button>
                    <button
                        @click="mapEngine = 'vector'"
                        class="px-3 py-1 rounded-full font-bold transition-all flex items-center gap-1.5"
                        :class="mapEngine === 'vector' ? 'bg-[#39FF14] text-[#04110A] shadow-[2px_2px_0_#243034]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                        title="Peta Vektor Stiker Bergaya Gelap"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>Vektor Stiker</span>
                    </button>
                </div>

                <!-- Tombol Reset Filter Peta -->
                <button
                    @click="resetFilter"
                    class="btn-secondary-tactile h-9 px-3 text-xs gap-1.5"
                    title="Reset Semua Pilihan Metrik Peta"
                >
                    <RotateCcw class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Reset Filter</span>
                </button>

                <!-- Tombol Bantuan Pengguna -->
                <button
                    @click="isHelpOpen = true"
                    class="btn-secondary-tactile h-9 px-3 text-xs gap-1.5"
                    title="Panduan Penggunaan Peta Publik"
                >
                    <HelpCircle class="w-3.5 h-3.5 text-[#19F5D4]" />
                    <span>Panduan</span>
                </button>
            </div>
        </header>

        <!-- KONTEN WEBGIS PUBLIK -->
        <main class="p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto flex-1 flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Kiri (Two-Tier + Legenda Kualitatif Bebas Angka + Top 5) -->
            <MapSidebar
                :selected-metric="selectedMetric"
                :active-kategori="activeKategori"
                :kecamatan-list="kecamatanList"
                :quantiles="quantiles"
                current-role="publik"
                :selected-kecamatan-id="selectedKecamatanId"
                @update:active-kategori="activeKategori = $event"
                @update:selected-metric="selectedMetric = $event"
                @select-kecamatan="handleSelectKecamatan"
                @open-chat="isChatOpen = true"
            />

            <!-- Area Peta Tengah (Peta Asli Leaflet atau Vektor Sesuai Pilihan) -->
            <div class="flex-1 relative flex flex-col">
                <!-- Peta Asli Kabupaten Banyumas (Leaflet GIS) -->
                <LeafletWebGisMap
                    ref="leafletMapRef"
                    v-if="mapEngine === 'real'"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    current-role="publik"
                    :selected-kecamatan-id="selectedKecamatanId"
                    @select-kecamatan="handleSelectKecamatan"
                />

                <!-- Alternatif Peta Vektor Bersih -->
                <WebGisMap
                    v-else
                    :kecamatan-list="kecamatanList"
                    :selected-metric="selectedMetric"
                    :quantiles="quantiles"
                    current-role="publik"
                    :selected-kecamatan-id="selectedKecamatanId"
                    @select-kecamatan="handleSelectKecamatan"
                />

                <!-- Popup Card Kecamatan (Kualitatif Publik) -->
                <KecamatanPopup
                    v-if="selectedKecamatan"
                    :kecamatan="selectedKecamatan"
                    :selected-metric="selectedMetric"
                    current-role="publik"
                    @close="selectedKecamatanId = undefined"
                    @drill-down="isHelpOpen = true"
                    @focus-desa="leafletMapRef?.focusDesa($event)"
                />
            </div>
        </main>

        <!-- FOOTER RESMI PUBLIK (Bebas Login Link, Sesuai Bab 11.1) -->
        <footer class="bg-[#10171A] border-t-2 border-[#243034] py-4 px-6 text-center text-xs text-[#8FA1A0] select-none">
            <p>
                © 2026 Pemerintah Kabupaten Banyumas • Dinas Perikanan dan Peternakan. Dikembangkan dengan arsitektur Zero Data Leakage.
            </p>
        </footer>

        <!-- MODAL CHATBOT ASISTEN AI PUBLIK (Bab 9) -->
        <ChatbotDrawer
            v-if="isChatOpen"
            :kecamatan-list="kecamatanList"
            @close="isChatOpen = false"
        />

        <!-- MODAL PANDUAN PENGGUNA -->
        <div
            v-if="isHelpOpen"
            class="fixed inset-0 z-50 bg-[#0A0D0E]/80 flex items-center justify-center p-4 select-none"
            @click.self="isHelpOpen = false"
        >
            <div class="bg-[#10171A] border-2 border-[#19F5D4] rounded-24 p-6 w-full max-w-lg shadow-[4px_4px_0_#19F5D4] animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-[#243034] mb-3">
                    <h3 class="font-display font-extrabold text-xl text-[#F4F7F5] flex items-center gap-2">
                        <HelpCircle class="w-5 h-5 text-[#19F5D4]" />
                        Panduan Peta Publik
                    </h3>
                    <button @click="isHelpOpen = false" class="w-8 h-8 rounded-full bg-[#0A0D0E] border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] flex items-center justify-center">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs text-[#8FA1A0] leading-relaxed">
                    <p>
                        <strong class="text-[#F4F7F5]">1. Peta Asli Banyumas:</strong> Peta menampilkan batas geografis riil 27 kecamatan di Kabupaten Banyumas dengan basemap resmi Esri Dark Canvas atau Citra Satelit Google/Esri (bebas watermark).
                    </p>
                    <p>
                        <strong class="text-[#F4F7F5]">2. Kategori Kualitatif:</strong> Portal Publik menyajikan informasi dalam kategori visual (Sangat Tinggi, Tinggi, Sedang, Rendah, Sangat Rendah) untuk kenyamanan masyarakat umum.
                    </p>
                    <p>
                        <strong class="text-[#F4F7F5]">3. Interaktivitas:</strong> Klik pada poligon kecamatan manapun untuk memunculkan ringkasan status wilayah dan kesehatan hewan.
                    </p>
                </div>

                <div class="mt-5 pt-3 border-t border-[#243034] flex justify-end">
                    <button @click="isHelpOpen = false" class="btn-primary-tactile text-xs">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>

        <!-- DIAGNOSTIK KIRI BAWAH -->
        <DiagnosticBox />
    </div>
</template>

<!-- BERKAS-UTUH: Portal.vue Publik Ternakverse Banyumas -->
