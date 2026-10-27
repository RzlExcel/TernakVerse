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
                bg: 'bg-[#FF5C5C]/20',
                border: 'border-[#FF5C5C]',
                text: 'text-[#FF5C5C]'
            };
        case 'waspada':
            return {
                label: 'Zona Waspada (Kuning)',
                bg: 'bg-[#FFC53D]/20',
                border: 'border-[#FFC53D]',
                text: 'text-[#FFC53D]'
            };
        default:
            return {
                label: 'Zona Hijau (Aman)',
                bg: 'bg-[#39FF14]/20',
                border: 'border-[#39FF14]',
                text: 'text-[#39FF14]'
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
        class="fixed md:absolute bottom-4 left-4 right-4 md:left-auto md:right-8 md:bottom-8 z-40 w-auto md:w-96 bg-[#10171A] border-2 border-[#19F5D4] rounded-20 p-4 shadow-[4px_4px_0_#19F5D4] select-none animate-in fade-in slide-in-from-bottom-4 duration-200"
    >
        <!-- Header Popup -->
        <div class="flex items-start justify-between pb-2.5 border-b border-[#243034]">
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8FA1A0]">Kecamatan</span>
                    <span class="text-[10px] font-mono text-[#19F5D4]">({{ kecamatan.kode_kec }})</span>
                </div>
                <h3 class="font-display font-extrabold text-xl text-[#F4F7F5] leading-tight flex items-center gap-2">
                    <span>{{ kecamatan.nama }}</span>
                    <span class="text-[11px] font-mono px-2 py-0.5 rounded-full bg-[#141C1E] border border-[#243034] text-[#39FF14]">
                        {{ desaList.length }} Desa
                    </span>
                </h3>
            </div>
            <button
                @click="$emit('close')"
                class="w-8 h-8 rounded-full bg-[#0A0D0E] border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:border-[#19F5D4] flex items-center justify-center transition-all cursor-pointer"
                title="Tutup Popup (Esc)"
            >
                <X class="w-4 h-4" />
            </button>
        </div>

        <!-- Status Zona Badge (Bab 4.3) -->
        <div class="my-2">
            <div
                class="px-3 py-1.5 rounded-full border text-xs font-bold flex items-center justify-between"
                :class="[zonaConfig.bg, zonaConfig.border, zonaConfig.text]"
            >
                <span class="text-[11px]">Status Wilayah:</span>
                <span>{{ zonaConfig.label }}</span>
            </div>
        </div>

        <!-- HERO BOX: METRIK AKTIF YANG SEDANG DIPILIH -->
        <div class="p-3 rounded-16 bg-[#0A0D0E] border-2 border-[#19F5D4] mb-2.5 shadow-[2px_2px_0_#243034]">
            <div class="flex items-center justify-between text-[11px] font-bold text-[#8FA1A0]">
                <span class="uppercase tracking-wider">{{ selectedMetric.kategori }}</span>
                <span class="text-[#19F5D4] font-semibold">{{ selectedMetric.label }}</span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xs text-[#8FA1A0]">Total Kecamatan:</span>
                <template v-if="currentRole === 'publik'">
                    <span class="font-display font-extrabold text-sm text-[#39FF14]">
                        {{ qualitativeStatus.label }}
                    </span>
                </template>
                <template v-else>
                    <span class="font-display font-extrabold text-lg text-[#19F5D4]">
                        {{ formatAngka(activeValue) }} <span class="text-xs font-normal text-[#8FA1A0]">{{ selectedMetric.satuan }}</span>
                    </span>
                </template>
            </div>
        </div>

        <!-- TAB NAVIGASI: RINGKASAN vs RINCIAN DESA -->
        <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#0A0D0E] rounded-12 border border-[#243034] mb-2.5">
            <button
                @click="activeSubTab = 'ringkasan'"
                class="py-1 px-2 rounded-8 text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                :class="activeSubTab === 'ringkasan' 
                    ? 'bg-[#19F5D4] text-[#04110A] shadow-[1px_1px_0_#243034]' 
                    : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
            >
                <Activity class="w-3.5 h-3.5" />
                <span>Ringkasan</span>
            </button>
            <button
                @click="activeSubTab = 'desa'"
                class="py-1 px-2 rounded-8 text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                :class="activeSubTab === 'desa' 
                    ? 'bg-[#39FF14] text-[#04110A] shadow-[1px_1px_0_#243034]' 
                    : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
            >
                <MapPin class="w-3.5 h-3.5" />
                <span>Data Desa ({{ desaList.length }})</span>
            </button>
        </div>

        <!-- KONTEN SUBTAB 1: 5 Ringkasan Komoditas & Fasilitas Penting -->
        <div v-if="activeSubTab === 'ringkasan'" class="space-y-1.5 text-xs">
            <!-- 1. Sapi Potong -->
            <div
                class="p-2 rounded-12 flex items-center justify-between transition-colors"
                :class="selectedMetric.id === 'sapi_potong' 
                    ? 'bg-[#243034] text-[#F4F7F5] font-bold border border-[#19F5D4]' 
                    : 'bg-[#0A0D0E]/60 text-[#8FA1A0]'"
            >
                <span :class="{ 'text-[#F4F7F5] font-bold': selectedMetric.id === 'sapi_potong' }">Sapi Potong</span>
                <span class="font-display font-extrabold text-xs" :class="selectedMetric.id === 'sapi_potong' ? 'text-[#19F5D4]' : 'text-[#F4F7F5]'">
                    <template v-if="currentRole === 'publik'">
                        {{ kecamatan.sapi_potong > 2000 ? 'Sentra Unggulan' : (kecamatan.sapi_potong > 1000 ? 'Kategori Tinggi' : 'Tersedia') }}
                    </template>
                    <template v-else>{{ formatAngka(kecamatan.sapi_potong) }} ekor</template>
                </span>
            </div>

            <!-- 2. Sapi Perah -->
            <div
                class="p-2 rounded-12 flex items-center justify-between transition-colors"
                :class="selectedMetric.id === 'sapi_perah' 
                    ? 'bg-[#243034] text-[#F4F7F5] font-bold border border-[#19F5D4]' 
                    : 'bg-[#0A0D0E]/60 text-[#8FA1A0]'"
            >
                <span :class="{ 'text-[#F4F7F5] font-bold': selectedMetric.id === 'sapi_perah' }">Sapi Perah</span>
                <span class="font-display font-extrabold text-xs" :class="selectedMetric.id === 'sapi_perah' ? 'text-[#19F5D4]' : 'text-[#F4F7F5]'">
                    <template v-if="currentRole === 'publik'">
                        {{ kecamatan.sapi_perah > 500 ? 'Sentra Susu' : (kecamatan.sapi_perah > 0 ? 'Tersedia' : 'Non-Sentra') }}
                    </template>
                    <template v-else>{{ formatAngka(kecamatan.sapi_perah) }} ekor</template>
                </span>
            </div>

            <!-- 3. Kambing & Domba -->
            <div
                class="p-2 rounded-12 flex items-center justify-between transition-colors"
                :class="(selectedMetric.id === 'kambing' || selectedMetric.id === 'domba')
                    ? 'bg-[#243034] text-[#F4F7F5] font-bold border border-[#19F5D4]' 
                    : 'bg-[#0A0D0E]/60 text-[#8FA1A0]'"
            >
                <span>Kambing & Domba</span>
                <span class="font-display font-extrabold text-xs text-[#F4F7F5]">
                    <template v-if="currentRole === 'publik'">Tersedia</template>
                    <template v-else>{{ formatAngka(kecamatan.kambing + kecamatan.domba) }} ekor</template>
                </span>
            </div>

            <!-- 4. Kasus Aktif PMK -->
            <div
                class="p-2 rounded-12 flex items-center justify-between transition-colors"
                :class="selectedMetric.id === 'pmk' 
                    ? 'bg-[#243034] text-[#F4F7F5] font-bold border border-[#FF5C5C]' 
                    : 'bg-[#0A0D0E]/60 text-[#8FA1A0]'"
            >
                <span>Kasus Aktif PMK</span>
                <span class="font-display font-extrabold text-xs" :class="kecamatan.pmk > 0 ? 'text-[#FF5C5C]' : 'text-[#39FF14]'">
                    {{ kecamatan.pmk > 0 ? `${kecamatan.pmk} kasus` : 'Bebas PMK' }}
                </span>
            </div>

            <!-- 5. Poskeswan & RPH -->
            <div
                class="p-2 rounded-12 flex items-center justify-between transition-colors"
                :class="selectedMetric.id === 'poskeswan' 
                    ? 'bg-[#243034] text-[#F4F7F5] font-bold border border-[#19F5D4]' 
                    : 'bg-[#0A0D0E]/60 text-[#8FA1A0]'"
            >
                <span>Poskeswan / RPH</span>
                <span class="font-display font-extrabold text-xs text-[#19F5D4]">
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
                    class="w-full bg-[#0A0D0E] border border-[#243034] focus:border-[#19F5D4] rounded-10 px-2.5 py-1.5 text-xs text-[#F4F7F5] outline-none placeholder:text-[#8FA1A0]"
                />
                <Search class="w-3.5 h-3.5 text-[#8FA1A0] absolute right-2.5 top-2 pointer-events-none" />
            </div>

            <!-- List Scrollable Semua Desa -->
            <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-popup-scroll">
                <div
                    v-for="d in filteredDesaList"
                    :key="d.id"
                    @click="$emit('focusDesa', d)"
                    class="p-2 rounded-10 bg-[#0A0D0E]/80 border border-[#243034] hover:border-[#39FF14] flex items-center justify-between transition-all cursor-pointer group"
                    :title="`Klik untuk fokus ke ${d.jenis} ${d.nama}`"
                >
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="text-xs font-bold text-[#F4F7F5] group-hover:text-[#39FF14] transition-colors truncate">
                                {{ d.nama }}
                            </span>
                            <span class="text-[9px] px-1.5 py-0.2 rounded bg-[#141C1E] text-[#8FA1A0] font-mono shrink-0">
                                {{ d.jenis }}
                            </span>
                        </div>
                        <div class="text-[10px] text-[#8FA1A0] font-mono truncate">
                            {{ d.kode_kd }}
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <div class="font-display font-extrabold text-xs text-[#19F5D4]">
                            {{ formatAngka(Number(d[selectedMetric.id as keyof DesaData] || 0)) }}
                            <span class="text-[10px] font-normal text-[#8FA1A0]">{{ selectedMetric.satuan }}</span>
                        </div>
                        <span class="text-[9px] text-[#8FA1A0]">
                            {{ selectedMetric.label }}
                        </span>
                    </div>
                </div>

                <div v-if="filteredDesaList.length === 0" class="py-4 text-center text-xs text-[#8FA1A0]">
                    Tidak ada desa dengan nama tersebut
                </div>
            </div>
        </div>

        <!-- Footer Popup -->
        <div class="mt-3 pt-2 border-t border-[#243034] flex items-center justify-between text-[11px] text-[#8FA1A0]">
            <span>Sinkron: {{ kecamatan.last_updated }}</span>
            <button
                @click="$emit('drillDown', kecamatan)"
                class="text-[#19F5D4] hover:underline font-bold flex items-center gap-1 cursor-pointer"
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
    background: #0A0D0E;
    border-radius: 4px;
}
.custom-popup-scroll::-webkit-scrollbar-thumb {
    background: #243034;
    border-radius: 4px;
}
.custom-popup-scroll::-webkit-scrollbar-thumb:hover {
    background: #19F5D4;
}
</style>
