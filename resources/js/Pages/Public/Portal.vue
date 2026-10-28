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
import { Compass, HelpCircle, RotateCcw, X, MapPin, Layers, ShieldCheck } from 'lucide-vue-next';

// State Metrik Aktif
const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>('wangon');

// Pilihan Mesin Peta: Peta Asli Leaflet (Real Tiles) vs Peta Vektor SVG
const mapEngine = ref<'real' | 'vector'>('real');
const leafletMapRef = ref<any>(null);

function switchMapEngine(engine: 'real' | 'vector') {
    mapEngine.value = engine;
    if (engine === 'real') {
        setTimeout(() => {
            leafletMapRef.value?.invalidateSize();
        }, 80);
    }
}

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

    <div class="min-h-screen bg-[#0B0F17] text-slate-100 flex flex-col font-ui antialiased selection:bg-blue-600 selection:text-white">
        
        <!-- NAVBAR PORTAL PUBLIK (BBPTUHPT High-Contrast Style) -->
        <header class="h-16 w-full border-b border-[#1E293B] bg-[#0B0F17] px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 select-none">
            <!-- Brand Kiri (Logo Balai + Nama WebGIS) -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
                    T
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-black text-base tracking-tight text-white">TERNAKVERSE</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-600/15 text-blue-400 border border-blue-500/30">PUBLIK</span>
                    </div>
                    <p class="text-[11px] text-slate-400 hidden sm:block leading-none mt-0.5">Peta Persebaran Populasi Ternak Kabupaten Banyumas</p>
                </div>
            </div>

            <!-- Bagian Kanan Navbar Publik: Toggle Mode Peta & Bantuan -->
            <div class="flex items-center gap-2.5">
                <!-- Toggle Mode Mesin Peta (Peta Asli GIS vs Vektor) -->
                <div class="hidden sm:flex items-center bg-[#111827] p-1 rounded-lg border border-[#1E293B] text-xs">
                    <button
                        @click="switchMapEngine('real')"
                        class="px-3 py-1 rounded-md font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                        :class="mapEngine === 'real' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-white'"
                        title="Peta Asli Banyumas dengan Basemap Real Satelit/Jalan"
                    >
                        <MapPin class="w-3.5 h-3.5" />
                        <span>Peta Asli Banyumas</span>
                    </button>
                    <button
                        @click="switchMapEngine('vector')"
                        class="px-3 py-1 rounded-md font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                        :class="mapEngine === 'vector' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-white'"
                        title="Peta Vektor Stiker Bergaya Gelap"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>Vektor Stiker</span>
                    </button>
                </div>

                <!-- Tombol Reset Filter Peta -->
                <button
                    @click="resetFilter"
                    class="h-9 px-3 rounded-lg border border-[#1E293B] bg-[#111827] text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    title="Reset Semua Pilihan Metrik Peta"
                >
                    <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
                    <span class="hidden sm:inline">Reset Filter</span>
                </button>

                <!-- Tombol Bantuan Pengguna -->
                <button
                    @click="isHelpOpen = true"
                    class="h-9 px-3 rounded-lg border border-[#1E293B] bg-[#111827] text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    title="Panduan Penggunaan Peta Publik"
                >
                    <HelpCircle class="w-3.5 h-3.5 text-blue-400" />
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
                <div v-show="mapEngine === 'real'" class="w-full h-full flex flex-col">
                    <LeafletWebGisMap
                        ref="leafletMapRef"
                        :selected-metric="selectedMetric"
                        :quantiles="quantiles"
                        current-role="publik"
                        :selected-kecamatan-id="selectedKecamatanId"
                        @select-kecamatan="handleSelectKecamatan"
                    />
                </div>

                <!-- Alternatif Peta Vektor Bersih -->
                <div v-show="mapEngine === 'vector'" class="w-full h-full flex flex-col">
                    <WebGisMap
                        :kecamatan-list="kecamatanList"
                        :selected-metric="selectedMetric"
                        :quantiles="quantiles"
                        current-role="publik"
                        :selected-kecamatan-id="selectedKecamatanId"
                        @select-kecamatan="handleSelectKecamatan"
                    />
                </div>

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

        <!-- FOOTER RESMI PUBLIK -->
        <footer class="glass-panel border-t border-slate-800/80 bg-[#080C0E]/90 py-4 px-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 select-none gap-2">
            <p>
                © 2026 Pemerintah Kabupaten Banyumas • Dinas Perikanan dan Peternakan. Dikembangkan dengan arsitektur Zero Data Leakage.
            </p>
            <div class="flex items-center gap-4">
                <Link href="/internal/login" class="text-slate-400 hover:text-emerald-400 font-medium transition-colors flex items-center gap-1.5">
                    <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
                    <span>Portal Petugas / Dinas</span>
                </Link>
            </div>
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
            class="fixed inset-0 z-50 bg-[#080C0E]/80 backdrop-blur-sm flex items-center justify-center p-4 select-none"
            @click.self="isHelpOpen = false"
        >
            <div class="glass-card border border-slate-700/80 rounded-2xl p-6 w-full max-w-lg shadow-2xl shadow-emerald-950/30 animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                    <h3 class="font-display font-bold text-lg text-slate-100 flex items-center gap-2">
                        <HelpCircle class="w-5 h-5 text-emerald-400" />
                        Panduan Peta Publik
                    </h3>
                    <button @click="isHelpOpen = false" class="w-8 h-8 rounded-full bg-slate-900 border border-slate-800 text-slate-400 hover:text-slate-100 hover:border-slate-700 flex items-center justify-center transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs text-slate-400 leading-relaxed">
                    <p>
                        <strong class="text-slate-200">1. Peta Asli Banyumas:</strong> Peta menampilkan batas geografis riil 27 kecamatan di Kabupaten Banyumas dengan basemap resmi Esri Dark Canvas atau Citra Satelit Google/Esri (bebas watermark).
                    </p>
                    <p>
                        <strong class="text-slate-200">2. Kategori Kualitatif:</strong> Portal Publik menyajikan informasi dalam kategori visual (Sangat Tinggi, Tinggi, Sedang, Rendah, Sangat Rendah) untuk kenyamanan masyarakat umum.
                    </p>
                    <p>
                        <strong class="text-slate-200">3. Interaktivitas:</strong> Klik pada poligon kecamatan manapun untuk memunculkan ringkasan status wilayah dan kesehatan hewan.
                    </p>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-800 flex justify-end">
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
