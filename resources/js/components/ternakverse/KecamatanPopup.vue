<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    KecamatanData,
    MetricDefinition,
    formatAngka,
    calculateQuantiles,
    getClassLevel,
    KECAMATAN_LIST
} from '../../data/banyumasData';
import { getDesaByKecamatan, DesaData } from '../../data/banyumasDesaData';
import { X, ExternalLink, Activity, ShieldCheck, AlertTriangle, MapPin, Search } from 'lucide-vue-next';

const props = defineProps<{
    kecamatan: KecamatanData;
    selectedMetric: MetricDefinition;
    currentRole: 'publik' | 'dinas' | 'admin';
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'drillDown', kec: KecamatanData): void;
    (e: 'focusDesa', desa: DesaData): void;
}>();

// Tab popup: 'ringkasan' | 'desa'
const activeSubTab = ref<'ringkasan' | 'desa'>('ringkasan');
const searchDesaQuery = ref('');

// Data Desa & Kelurahan di Kecamatan ini
const desaList = computed(() => {
    return getDesaByKecamatan(props.kecamatan.id);
});

const filteredDesaList = computed(() => {
    if (!searchDesaQuery.value.trim()) return desaList.value;
    const q = searchDesaQuery.value.toLowerCase().trim();
    return desaList.value.filter(d => 
        d.nama.toLowerCase().includes(q) || d.kode_kd.includes(q)
    );
});

// Status zona wilayah
const zonaConfig = computed(() => {
    switch (props.kecamatan.zona) {
        case 'bahaya':
            return {
                label: 'Zona Merah (Bahaya)',
                bg: 'bg-red-950/40',
                border: 'border-red-500/50',
                text: 'text-red-400'
            };
        case 'waspada':
            return {
                label: 'Zona Waspada (Kuning)',
                bg: 'bg-amber-950/40',
                border: 'border-amber-500/50',
                text: 'text-amber-400'
            };
        default:
            return {
                label: 'Zona Hijau (Aman)',
                bg: 'bg-emerald-950/40',
                border: 'border-emerald-500/50',
                text: 'text-emerald-400'
            };
    }
});

// Nilai metrik aktif
const activeValue = computed(() => {
    return Number(props.kecamatan[props.selectedMetric.id]) || 0;
});

// Kualitatif level & label untuk Publik (Bab 11.1)
const qualitativeStatus = computed(() => {
    const quantiles = calculateQuantiles(KECAMATAN_LIST, props.selectedMetric.id);
    const lvl = getClassLevel(activeValue.value, quantiles);
    const labels = [
        'Kategori Sangat Rendah',
        'Kategori Rendah',
        'Kategori Sedang',
        'Kategori Tinggi',
        'Sangat Tinggi (Sentra Unggulan)'
    ];
    return {
        level: lvl,
        label: labels[lvl - 1] || 'Kategori Sedang'
    };
});
</script>

<template>
    <div
        class="fixed md:absolute bottom-4 left-4 right-4 md:left-auto md:right-8 md:bottom-8 z-40 w-auto md:w-96 glass-card border border-slate-700/80 rounded-2xl p-4 shadow-2xl shadow-emerald-950/30 backdrop-blur-xl select-none animate-in fade-in slide-in-from-bottom-4 duration-200"
    >
        <!-- Header Popup -->
        <div class="flex items-start justify-between pb-2.5 border-b border-slate-800">
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kecamatan</span>
                    <span class="text-[10px] font-mono text-emerald-400">({{ kecamatan.kode_kec }})</span>
                </div>
                <h3 class="font-display font-bold text-lg text-slate-100 leading-tight flex items-center gap-2">
                    <span>{{ kecamatan.nama }}</span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-900/90 border border-emerald-500/30 text-emerald-400">
                        {{ desaList.length }} Desa
                    </span>
                </h3>
            </div>
            <button
                @click="$emit('close')"
                class="w-7 h-7 rounded-full bg-slate-900 border border-slate-800 text-slate-400 hover:text-slate-100 hover:border-slate-700 flex items-center justify-center transition-colors cursor-pointer"
                title="Tutup Popup (Esc)"
            >
                <X class="w-4 h-4" />
            </button>
        </div>

        <!-- Status Zona Badge (Bab 4.3) -->
        <div class="my-2.5">
            <div
                class="px-3 py-1.5 rounded-full border text-xs font-semibold flex items-center justify-between"
                :class="[zonaConfig.bg, zonaConfig.border, zonaConfig.text]"
            >
                <span class="text-[10px] uppercase tracking-wide opacity-80">Status Wilayah:</span>
                <span>{{ zonaConfig.label }}</span>
            </div>
        </div>

        <!-- HERO BOX: METRIK AKTIF YANG SEDANG DIPILIH -->
        <div class="p-3 rounded-xl bg-slate-900/90 border border-emerald-500/30 mb-2.5 shadow-sm">
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-400">
                <span class="uppercase tracking-wider text-[10px]">{{ selectedMetric.kategori }}</span>
                <span class="text-emerald-400 font-semibold">{{ selectedMetric.label }}</span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xs text-slate-400">Total Kecamatan:</span>
                <template v-if="currentRole === 'publik'">
                    <span class="font-display font-bold text-xs text-emerald-400">
                        {{ qualitativeStatus.label }}
                    </span>
                </template>
                <template v-else>
                    <span class="font-display font-bold text-base text-emerald-400 font-mono">
                        {{ formatAngka(activeValue) }} <span class="text-xs font-normal text-slate-400">{{ selectedMetric.satuan }}</span>
                    </span>
                </template>
            </div>
        </div>

        <!-- TAB NAVIGASI: RINGKASAN vs RINCIAN DESA -->
        <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-900/90 rounded-xl border border-slate-800 mb-2.5">
            <button
                @click="activeSubTab = 'ringkasan'"
                class="py-1 px-2 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                :class="activeSubTab === 'ringkasan' 
                    ? 'bg-emerald-500 text-slate-950 font-bold shadow-sm' 
                    : 'text-slate-400 hover:text-slate-200'"
            >
                <Activity class="w-3.5 h-3.5" />
                <span>Ringkasan</span>
            </button>
            <button
                @click="activeSubTab = 'desa'"
                class="py-1 px-2 rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                :class="activeSubTab === 'desa' 
                    ? 'bg-emerald-500 text-slate-950 font-bold shadow-sm' 
                    : 'text-slate-400 hover:text-slate-200'"
            >
                <MapPin class="w-3.5 h-3.5" />
                <span>Data Desa ({{ desaList.length }})</span>
            </button>
        </div>

        <!-- KONTEN SUBTAB 1: 5 Ringkasan Komoditas & Fasilitas Penting -->
        <div v-if="activeSubTab === 'ringkasan'" class="space-y-1.5 text-xs">
            <!-- 1. Sapi Potong -->
            <div
                class="p-2 rounded-xl flex items-center justify-between transition-colors border"
                :class="selectedMetric.id === 'sapi_potong' 
                    ? 'bg-emerald-950/40 text-slate-100 font-bold border-emerald-500/50 shadow-sm shadow-emerald-500/10' 
                    : 'bg-slate-900/50 text-slate-300 border-slate-800/60'"
            >
                <span>Sapi Potong</span>
                <span class="font-display font-bold text-xs" :class="selectedMetric.id === 'sapi_potong' ? 'text-emerald-400' : 'text-slate-100'">
                    <template v-if="currentRole === 'publik'">
                        {{ kecamatan.sapi_potong > 2000 ? 'Sentra Unggulan' : (kecamatan.sapi_potong > 1000 ? 'Kategori Tinggi' : 'Tersedia') }}
                    </template>
                    <template v-else>{{ formatAngka(kecamatan.sapi_potong) }} ekor</template>
                </span>
            </div>

            <!-- 2. Sapi Perah -->
            <div
                class="p-2 rounded-xl flex items-center justify-between transition-colors border"
                :class="selectedMetric.id === 'sapi_perah' 
                    ? 'bg-emerald-950/40 text-slate-100 font-bold border-emerald-500/50 shadow-sm shadow-emerald-500/10' 
                    : 'bg-slate-900/50 text-slate-300 border-slate-800/60'"
            >
                <span>Sapi Perah</span>
                <span class="font-display font-bold text-xs" :class="selectedMetric.id === 'sapi_perah' ? 'text-emerald-400' : 'text-slate-100'">
                    <template v-if="currentRole === 'publik'">
                        {{ kecamatan.sapi_perah > 500 ? 'Sentra Susu' : (kecamatan.sapi_perah > 0 ? 'Tersedia' : 'Non-Sentra') }}
                    </template>
                    <template v-else>{{ formatAngka(kecamatan.sapi_perah) }} ekor</template>
                </span>
            </div>

            <!-- 3. Kambing & Domba -->
            <div
                class="p-2 rounded-xl flex items-center justify-between transition-colors border"
                :class="(selectedMetric.id === 'kambing' || selectedMetric.id === 'domba')
                    ? 'bg-emerald-950/40 text-slate-100 font-bold border-emerald-500/50 shadow-sm shadow-emerald-500/10' 
                    : 'bg-slate-900/50 text-slate-300 border-slate-800/60'"
            >
                <span>Kambing & Domba</span>
                <span class="font-display font-bold text-xs text-slate-100">
                    <template v-if="currentRole === 'publik'">Tersedia</template>
                    <template v-else>{{ formatAngka(kecamatan.kambing + kecamatan.domba) }} ekor</template>
                </span>
            </div>

            <!-- 4. Kasus Aktif PMK -->
            <div
                class="p-2 rounded-xl flex items-center justify-between transition-colors border"
                :class="selectedMetric.id === 'pmk' 
                    ? 'bg-red-950/40 text-slate-100 font-bold border-red-500/50 shadow-sm shadow-red-500/10' 
                    : 'bg-slate-900/50 text-slate-300 border-slate-800/60'"
            >
                <span>Kasus Aktif PMK</span>
                <span class="font-display font-bold text-xs" :class="kecamatan.pmk > 0 ? 'text-red-400' : 'text-emerald-400'">
                    {{ kecamatan.pmk > 0 ? `${kecamatan.pmk} kasus` : 'Bebas PMK' }}
                </span>
            </div>

            <!-- 5. Poskeswan & RPH -->
            <div
                class="p-2 rounded-xl flex items-center justify-between transition-colors border"
                :class="selectedMetric.id === 'poskeswan' 
                    ? 'bg-emerald-950/40 text-slate-100 font-bold border-emerald-500/50 shadow-sm shadow-emerald-500/10' 
                    : 'bg-slate-900/50 text-slate-300 border-slate-800/60'"
            >
                <span>Poskeswan / RPH</span>
                <span class="font-display font-bold text-xs text-emerald-400">
                    {{ kecamatan.poskeswan > 0 ? `${kecamatan.poskeswan} Unit Layanan` : 'Wilayah Satelit' }}
                </span>
            </div>
        </div>

        <!-- KONTEN SUBTAB 2: DAFTAR SEMUA DESA & KELURAHAN -->
        <div v-else-if="activeSubTab === 'desa'" class="space-y-2">
            <!-- Pencarian Cepat Desa -->
            <div class="relative">
                <input
                    v-model="searchDesaQuery"
                    type="text"
                    :placeholder="`Cari desa di ${kecamatan.nama}...`"
                    class="w-full bg-slate-900/90 border border-slate-800 focus:border-emerald-500/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-100 outline-none placeholder:text-slate-500"
                />
                <Search class="w-3.5 h-3.5 text-slate-500 absolute right-2.5 top-2 pointer-events-none" />
            </div>

            <!-- List Scrollable Semua Desa -->
            <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-popup-scroll">
                <div
                    v-for="d in filteredDesaList"
                    :key="d.id"
                    @click="$emit('focusDesa', d)"
                    class="p-2 rounded-xl bg-slate-900/50 border border-slate-800/70 hover:border-emerald-500/40 flex items-center justify-between transition-all cursor-pointer group"
                    :title="`Klik untuk fokus ke ${d.jenis} ${d.nama}`"
                >
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="text-xs font-semibold text-slate-200 group-hover:text-emerald-400 transition-colors truncate">
                                {{ d.nama }}
                            </span>
                            <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 font-mono shrink-0">
                                {{ d.jenis }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono truncate">
                            {{ d.kode_kd }}
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <div class="font-display font-bold text-xs text-emerald-400 font-mono">
                            {{ formatAngka(Number(d[selectedMetric.id as keyof DesaData] || 0)) }}
                            <span class="text-[10px] font-normal text-slate-400">{{ selectedMetric.satuan }}</span>
                        </div>
                        <span class="text-[9px] text-slate-500">
                            {{ selectedMetric.label }}
                        </span>
                    </div>
                </div>

                <div v-if="filteredDesaList.length === 0" class="py-4 text-center text-xs text-slate-400">
                    Tidak ada desa dengan nama tersebut
                </div>
            </div>
        </div>

        <!-- Footer Popup -->
        <div class="mt-3 pt-2 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
            <span>Sinkron: {{ kecamatan.last_updated }}</span>
            <button
                @click="$emit('drillDown', kecamatan)"
                class="text-emerald-400 hover:text-emerald-300 font-bold flex items-center gap-1 cursor-pointer transition-colors"
            >
                <span>Rincian</span>
                <ExternalLink class="w-3 h-3" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.custom-popup-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-popup-scroll::-webkit-scrollbar-track {
    background: #080C0E;
    border-radius: 4px;
}
.custom-popup-scroll::-webkit-scrollbar-thumb {
    background: #1E293B;
    border-radius: 4px;
}
.custom-popup-scroll::-webkit-scrollbar-thumb:hover {
    background: #10B981;
}
</style>
