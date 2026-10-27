<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { KECAMATAN_LIST, METRIC_LIST, KecamatanData, MetricDefinition, calculateQuantiles } from '../data/banyumasData';
import Navbar from '../components/ternakverse/Navbar.vue';
import MapSidebar from '../components/ternakverse/MapSidebar.vue';
import WebGisMap from '../components/ternakverse/WebGisMap.vue';
import KecamatanPopup from '../components/ternakverse/KecamatanPopup.vue';
import AdminDashboard from '../components/ternakverse/AdminDashboard.vue';
import DesignRuleInspector from '../components/ternakverse/DesignRuleInspector.vue';
import ChatbotDrawer from '../components/ternakverse/ChatbotDrawer.vue';
import DiagnosticBox from '../components/ternakverse/DiagnosticBox.vue';
import { HelpCircle, X, ExternalLink, ShieldCheck } from 'lucide-vue-next';

// State Utama
const currentRole = ref<'publik' | 'dinas' | 'admin'>('publik');
const activeTab = ref<'map' | 'admin' | 'design-rule'>('map');
const activeKategori = ref<'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'>('Populasi');
const selectedMetric = ref<MetricDefinition>(METRIC_LIST[0]);
const kecamatanList = ref<KecamatanData[]>([...KECAMATAN_LIST]);
const selectedKecamatanId = ref<string | undefined>('wangon');

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

    <div class="min-h-screen bg-[#0A0D0E] text-[#F4F7F5] flex flex-col font-ui antialiased selection:bg-[#39FF14] selection:text-[#04110A]">
        <!-- 1. NAVBAR (64px, Bab 4.1) -->
        <Navbar
            :current-role="currentRole"
            :active-tab="activeTab"
            @update:current-role="currentRole = $event"
            @update:active-tab="activeTab = $event"
            @open-help="isHelpOpen = true"
        />

        <!-- 2. KONTEN UTAMA BERDASARKAN TAB AKTIF -->
        <main class="flex-1 w-full flex flex-col">
            
            <!-- TAB 1: WEBGIS PETA (Publik & Dinas) -->
            <div v-show="activeTab === 'map'" class="p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto flex-1 flex flex-col lg:flex-row gap-6">
                <!-- Sidebar Kiri (300px) -->
                <MapSidebar
                    :selected-metric="selectedMetric"
                    :active-kategori="activeKategori"
                    :kecamatan-list="kecamatanList"
                    :quantiles="quantiles"
                    :current-role="currentRole"
                    :selected-kecamatan-id="selectedKecamatanId"
                    @update:active-kategori="activeKategori = $event"
                    @update:selected-metric="selectedMetric = $event"
                    @select-kecamatan="handleSelectKecamatan"
                    @open-chat="isChatOpen = true"
                />

                <!-- Kolom Peta Tengah (Flex-1) -->
                <div class="flex-1 relative flex flex-col">
                    <WebGisMap
                        :kecamatan-list="kecamatanList"
                        :selected-metric="selectedMetric"
                        :quantiles="quantiles"
                        :current-role="currentRole"
                        :selected-kecamatan-id="selectedKecamatanId"
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

        <!-- 3. MODAL DRAWER CHATBOT ASISTEN AI (Bab 9) -->
        <ChatbotDrawer
            v-if="isChatOpen"
            :kecamatan-list="kecamatanList"
            @close="isChatOpen = false"
        />

        <!-- 4. MODAL BANTUAN & PANDUAN PENGGUNA (Bab 11.1) -->
        <div
            v-if="isHelpOpen"
            class="fixed inset-0 z-50 bg-[#0A0D0E]/80 flex items-center justify-center p-4 select-none"
            @click.self="isHelpOpen = false"
        >
            <div class="bg-[#10171A] border-2 border-[#19F5D4] rounded-24 p-6 w-full max-w-lg shadow-[4px_4px_0_#19F5D4] animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-[#243034] mb-3">
                    <h3 class="font-display font-extrabold text-xl text-[#F4F7F5] flex items-center gap-2">
                        <HelpCircle class="w-5 h-5 text-[#19F5D4]" />
                        Panduan Penggunaan WebGIS
                    </h3>
                    <button @click="isHelpOpen = false" class="w-8 h-8 rounded-full bg-[#0A0D0E] border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] flex items-center justify-center">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs text-[#8FA1A0] leading-relaxed">
                    <p>
                        <strong class="text-[#F4F7F5]">1. Membaca Peta:</strong> Warna hijau neon menunjukkan kuantil populasi tertinggi. Pada mode Penyakit, ukuran lingkaran merah menunjukkan banyaknya kasus PMK atau LSD aktif.
                    </p>
                    <p>
                        <strong class="text-[#F4F7F5]">2. Interaksi Poligon:</strong> Klik pada salah satu dari 27 kecamatan untuk memunculkan Kartu Popup ringkasan 5 metrik utama.
                    </p>
                    <p>
                        <strong class="text-[#F4F7F5]">3. Simulasi Peran:</strong> Gunakan tombol <code class="text-[#19F5D4] font-mono">[Publik | Dinas | Admin]</code> di navbar untuk menguji perbedaan akses kualitatif publik versus kuantitatif pimpinan dinas.
                    </p>
                    <p>
                        <strong class="text-[#F4F7F5]">4. Tab Aturan Desain:</strong> Buka tab "Aturan Desain" untuk meninjau kepatuhan dokumen DESIGN_RULE.md, respons tombol tactile, dan palet token solid.
                    </p>
                </div>

                <div class="mt-5 pt-3 border-t border-[#243034] flex justify-end">
                    <button @click="isHelpOpen = false" class="btn-primary-tactile text-xs">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. KOTAK DIAGNOSTIK KIRI BAWAH (Bab 13) -->
        <DiagnosticBox />
    </div>
</template>

<!-- BERKAS-UTUH: Welcome.vue Ternakverse Banyumas -->
