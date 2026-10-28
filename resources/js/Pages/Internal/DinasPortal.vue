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

    <div class="min-h-screen bg-[#0B0F17] text-slate-100 flex flex-col font-ui antialiased selection:bg-blue-600 selection:text-white">
        <!-- NAVBAR PORTAL DINAS (BBPTUHPT Style) -->
        <header class="h-16 w-full border-b border-[#1E293B] bg-[#0B0F17] px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 select-none">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
                    T
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-black text-base tracking-tight text-white">TERNAKVERSE</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-600/15 text-blue-400 border border-blue-500/30">
                            PORTAL DINAS
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 hidden sm:block leading-none mt-0.5">Analisis Kuantitatif &amp; Kewaspadaan Wabah Peternakan</p>
                </div>
            </div>

            <!-- Tengah: Badge Status Peran Petugas -->
            <div class="hidden md:flex items-center gap-3">
                <div class="px-3 py-1 rounded-lg border border-blue-500/40 bg-blue-950/30 text-blue-300 text-xs font-semibold flex items-center gap-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    <span>Petugas Dinas: drh. Sulistyo (Kabid Peternakan)</span>
                </div>
            </div>

            <!-- Kanan: Pilihan Mesin Peta & Tombol Logout -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center bg-[#111827] p-1 rounded-lg border border-[#1E293B] text-xs">
                    <button
                        @click="mapEngine = 'real'"
                        class="px-3 py-1 rounded-md font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                        :class="mapEngine === 'real' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-white'"
                    >
                        <MapPin class="w-3.5 h-3.5" />
                        <span>Peta Asli Banyumas</span>
                    </button>
                    <button
                        @click="mapEngine = 'vector'"
                        class="px-3 py-1 rounded-md font-semibold transition-all flex items-center gap-1.5 cursor-pointer"
                        :class="mapEngine === 'vector' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-white'"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>Vektor Stiker</span>
                    </button>
                </div>

                <!-- Tombol Keluar (Logout Sesi) -->
                <button
                    @click="handleLogout"
                    class="h-9 px-3.5 rounded-lg border border-[#1E293B] bg-[#111827] text-xs gap-1.5 text-rose-400 hover:text-rose-300 hover:border-rose-500/50 flex items-center transition-all cursor-pointer"
                    title="Keluar dari sesi Dinas"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>Keluar</span>
                </button>
            </div>
        </header>

        <!-- EXECUTIVE SITUATIONAL KPI CHIPS (Quick Executive Strip - BBPTUHPT Style) -->
        <div class="border-b border-[#1E293B] bg-[#111827] px-4 md:px-8 py-2.5">
            <div class="max-w-[1600px] mx-auto flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Ringkasan Eksekutif:</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#0B0F17] border border-[#1E293B] text-slate-200 font-mono">
                        <span class="w-2 h-2 rounded-sm bg-blue-500"></span>
                        Populasi Banyumas: <strong class="text-white">38.450 ekor</strong>
                    </span>
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#0B0F17] border border-[#1E293B] text-slate-200 font-mono">
                        <span class="w-2 h-2 rounded-sm bg-cyan-400"></span>
                        Poskeswan: <strong class="text-white">12 Unit</strong>
                    </span>
                    <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#0B0F17] border border-[#1E293B] text-slate-200 font-mono">
                        <span class="w-2 h-2 rounded-sm bg-amber-400"></span>
                        RPH Aktif: <strong class="text-white">5 Fasilitas</strong>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[11px] font-semibold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Surveilans: Bebas Antraks
                    </span>
                </div>
            </div>
        </div>

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
