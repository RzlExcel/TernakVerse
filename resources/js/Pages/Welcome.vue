<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { KECAMATAN_LIST, METRIC_LIST, KecamatanData, MetricDefinition, calculateQuantiles } from '../data/banyumasData';
import Navbar from '../components/ternakverse/Navbar.vue';
import MapSidebar from '../components/ternakverse/MapSidebar.vue';
import LeafletWebGisMap from '../components/ternakverse/LeafletWebGisMap.vue';
import KecamatanPopup from '../components/ternakverse/KecamatanPopup.vue';
import AdminDashboard from '../components/ternakverse/AdminDashboard.vue';
import DesignRuleInspector from '../components/ternakverse/DesignRuleInspector.vue';
import ChatbotDrawer from '../components/ternakverse/ChatbotDrawer.vue';
import DiagnosticBox from '../components/ternakverse/DiagnosticBox.vue';
import { HelpCircle, X, Compass, BarChart3, ShieldCheck, BookOpen } from 'lucide-vue-next';

// State Utama
const currentRole = ref<'publik' | 'dinas' | 'admin'>('publik');
const activeTab = ref<'map' | 'admin' | 'design-rule'>('map');
const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>('wangon');

// Layer States
const layerStates = ref<Record<string, boolean>>({
    batasKabupaten: true,
    batasKecamatan: true,
    batasDesa: false,
    heatmapPopulasi: false,
    fasilitasPeternakan: false,
    jalurTransportasi: false,
});

// Modals State
const isChatOpen = ref(false);
const isHelpOpen = ref(false);

// Perhitungan Kuantil Dinamis 5 Kelas (Bab 5.1 & Bab 2)
const quantiles = computed(() => {
    return calculateQuantiles(kecamatanList.value, selectedMetric.value.id as keyof KecamatanData);
});

// Kecamatan yang sedang dipilih untuk Popup Card
const selectedKecamatan = computed(() => {
    if (!selectedKecamatanId.value) return null;
    return kecamatanList.value.find(k => k.id === selectedKecamatanId.value) || null;
});

function handleSelectKecamatan(id: string) {
    if (selectedKecamatanId.value === id) {
        selectedKecamatanId.value = undefined; // toggle tutup jika diklik ulang
    } else {
        selectedKecamatanId.value = id;
    }
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
    <Head title="Ternakverse - WebGIS Peternakan Kabupaten Banyumas" />

    <div class="min-h-screen bg-[#090909] text-slate-100 flex flex-col font-ui antialiased selection:bg-emerald-500 selection:text-slate-950">
        <!-- 1. NAVBAR (AgriTech Command Center Header) -->
        <Navbar
            :current-role="currentRole"
            :active-tab="activeTab"
            @update:current-role="currentRole = $event"
            @update:active-tab="activeTab = $event"
            @open-help="isHelpOpen = true"
        />

        <!-- 2. QUICK LAUNCHER STRIP (Direct Links to Dedicated Standalone Portals) -->
        <div class="border-b border-[#1E1E1E] bg-[#111111] px-4 md:px-6 py-2 shrink-0">
            <div class="max-w-[1600px] mx-auto flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="font-semibold text-white">Pusat Navigasi Portals:</span>
                    <span class="text-slate-400 hidden sm:inline">Akses langsung antarmuka mandiri:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        href="/portal"
                        class="px-3 py-1 rounded-xl bg-[#103F3A] hover:bg-[#159B78] border border-emerald-500/40 text-emerald-300 hover:text-white font-semibold transition-all inline-flex items-center gap-1.5"
                    >
                        <Compass class="w-3.5 h-3.5" />
                        <span>Portal Publik (100vh)</span>
                    </Link>
                    <Link
                        href="/internal/dinas"
                        class="px-3 py-1 rounded-xl bg-[#181818] hover:bg-[#1E1E1E] border border-[#1E1E1E] hover:border-emerald-500/40 text-slate-200 hover:text-emerald-400 font-semibold transition-all inline-flex items-center gap-1.5"
                    >
                        <BarChart3 class="w-3.5 h-3.5 text-emerald-400" />
                        <span>Portal Dinas</span>
                    </Link>
                    <Link
                        href="/internal/admin"
                        class="px-3 py-1 rounded-xl bg-[#181818] hover:bg-[#1E1E1E] border border-[#1E1E1E] hover:border-emerald-500/40 text-slate-200 hover:text-emerald-400 font-semibold transition-all inline-flex items-center gap-1.5"
                    >
                        <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
                        <span>Admin Balai</span>
                    </Link>
                    <Link
                        href="/design-rule"
                        class="px-3 py-1 rounded-xl bg-[#181818] hover:bg-[#1E1E1E] border border-[#1E1E1E] hover:border-emerald-500/40 text-slate-300 hover:text-white font-semibold transition-all inline-flex items-center gap-1.5"
                    >
                        <BookOpen class="w-3.5 h-3.5 text-slate-400" />
                        <span>Design Rule</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 3. KONTEN UTAMA BERDASARKAN TAB AKTIF -->
        <main class="flex-1 w-full flex flex-col">
            
            <!-- TAB 1: WEBGIS PETA (Publik & Dinas) -->
            <div v-show="activeTab === 'map'" class="p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto flex-1 flex flex-col lg:flex-row gap-6">
                <!-- Sidebar Kiri (360px) -->
                <div class="w-full lg:w-[360px] shrink-0 overflow-x-hidden">
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

                <!-- Kolom Peta Tengah (Flex-1) -->
                <div class="flex-1 relative flex flex-col min-h-[500px]">
                    <LeafletWebGisMap
                        :selected-metric="selectedMetric"
                        :quantiles="quantiles"
                        :current-role="currentRole"
                        :selected-kecamatan-id="selectedKecamatanId"
                        :layer-states="layerStates"
                        @select-kecamatan="handleSelectKecamatan"
                    />

                    <!-- Popup Card Kecamatan (Klik Poligon, Bab 4.3) -->
                    <KecamatanPopup
                        v-if="selectedKecamatan"
                        :kecamatan="selectedKecamatan"
                        :selected-metric="selectedMetric"
                        :current-role="currentRole"
                        @close="selectedKecamatanId = undefined"
                        @drill-down="activeTab = 'admin'"
                    />
                </div>
            </div>

            <!-- TAB 2: PANEL ADMIN DATA (Satu Kolom 1160px) -->
            <div v-show="activeTab === 'admin'" class="w-full">
                <AdminDashboard
                    :kecamatan-list="kecamatanList"
                    @update-data="kecamatanList = $event"
                    @open-map="activeTab = 'map'"
                />
            </div>

            <!-- TAB 3: INSPEKTOR ATURAN DESAIN (Living Design Rule) -->
            <div v-show="activeTab === 'design-rule'" class="w-full">
                <DesignRuleInspector />
            </div>
        </main>

        <!-- 4. MODAL DRAWER CHATBOT ASISTEN AI (Bab 9) -->
        <ChatbotDrawer
            :kecamatan-list="kecamatanList"
            :is-open="isChatOpen"
            @open="isChatOpen = true"
            @close="isChatOpen = false"
        />

        <!-- 5. MODAL BANTUAN & PANDUAN PENGGUNA (Bab 11.1) -->
        <div
            v-if="isHelpOpen"
            class="fixed inset-0 z-50 bg-[#090909]/80 backdrop-blur-sm flex items-center justify-center p-4 select-none"
            @click.self="isHelpOpen = false"
        >
            <div class="bg-[#111111] border border-[#1E1E1E] rounded-2xl p-6 w-full max-w-lg shadow-2xl animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-[#1E1E1E] mb-4">
                    <h3 class="font-display font-bold text-lg text-slate-100 flex items-center gap-2">
                        <HelpCircle class="w-5 h-5 text-emerald-400" />
                        Panduan Penggunaan WebGIS
                    </h3>
                    <button
                        type="button"
                        @click="isHelpOpen = false"
                        class="w-9 h-9 rounded-xl bg-[#181818] border border-[#1E1E1E] text-slate-400 hover:text-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Tutup Panduan"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs text-slate-300 leading-relaxed">
                    <p>
                        <strong class="text-slate-100">1. Membaca Peta:</strong> Warna emerald terang menunjukkan kuantil populasi tertinggi. Pada mode Penyakit, ukuran lingkaran merah menunjukkan banyaknya kasus PMK atau LSD aktif.
                    </p>
                    <p>
                        <strong class="text-slate-100">2. Interaksi Poligon:</strong> Klik pada salah satu dari 27 kecamatan untuk memunculkan Kartu Popup ringkasan 5 metrik utama.
                    </p>
                    <p>
                        <strong class="text-slate-100">3. Simulasi Peran:</strong> Gunakan tombol <code class="text-emerald-400 font-mono">[Publik | Dinas | Admin]</code> di navbar untuk menguji perbedaan akses kualitatif publik versus kuantitatif pimpinan dinas.
                    </p>
                    <p>
                        <strong class="text-slate-100">4. Tab Aturan Desain:</strong> Buka tab "Aturan Desain" untuk meninjau kepatuhan sistem desain Forest Intelligence GIS.
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-[#1E1E1E] flex justify-end">
                    <button
                        type="button"
                        @click="isHelpOpen = false"
                        class="h-10 px-5 rounded-xl bg-[#159B78] hover:bg-[#1BB18A] text-white font-semibold text-xs transition-colors shadow-sm cursor-pointer"
                    >
                        Mengerti
                    </button>
                </div>
            </div>
        </div>

        <!-- 6. KOTAK DIAGNOSTIK KIRI BAWAH -->
        <DiagnosticBox />
    </div>
</template>

<!-- BERKAS-UTUH: Welcome.vue Ternakverse Banyumas -->
