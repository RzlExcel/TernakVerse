<script setup lang="ts">
import { ref, computed } from 'vue';
import { METRIC_LIST, MetricDefinition, KecamatanData, formatAngka, COLOR_CLASSES } from '../../data/banyumasData';
import { getDesaByKecamatan } from '../../data/banyumasDesaData';
import { Search, ChevronDown, MessageSquareText, Award, HelpCircle, Layers } from 'lucide-vue-next';

const props = defineProps<{
    selectedMetric: MetricDefinition;
    activeKategori: 'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas';
    kecamatanList: KecamatanData[];
    quantiles: number[];
    currentRole: 'publik' | 'dinas' | 'admin';
    selectedKecamatanId?: string;
}>();

const emit = defineEmits<{
    (e: 'update:activeKategori', val: 'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas'): void;
    (e: 'update:selectedMetric', val: MetricDefinition): void;
    (e: 'selectKecamatan', id: string): void;
    (e: 'openChat'): void;
}>();

// State Combobox Tier 2
const isComboboxOpen = ref(false);
const commoditySearch = ref('');

// Filter daftar komoditas berdasarkan Kategori Tier 1 dan Search Query
const filteredCommodities = computed(() => {
    return METRIC_LIST.filter(m => {
        const matchesCategory = m.kategori === props.activeKategori;
        const matchesQuery = m.label.toLowerCase().includes(commoditySearch.value.toLowerCase());
        return matchesCategory && matchesQuery;
    });
});

// Top 5 Peringkat Kecamatan berdasarkan Metrik Aktif
const top5List = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    return [...props.kecamatanList]
        .sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0))
        .slice(0, 5);
});

// Total dan Juara 1 untuk Ringkasan Naratif Santai (Bab 9)
const summaryText = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const sorted = [...props.kecamatanList].sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0));
    const total = sorted.reduce((acc, curr) => acc + Number(curr[key] || 0), 0);
    const top = sorted[0];

    if (!top) return '';

    if (props.selectedMetric.kategori === 'Penyakit') {
        const totalBahaya = props.kecamatanList.filter(k => k.zona === 'bahaya').length;
        return `${totalBahaya} kecamatan masuk zona merah ${props.selectedMetric.label}. Paling tinggi di ${top.nama} (${top[key]} ${props.selectedMetric.satuan}).`;
    }

    if (props.currentRole === 'publik') {
        return `Ada ${formatAngka(total)} ${props.selectedMetric.satuan} ${props.selectedMetric.label.toLowerCase()} di 27 kecamatan. Paling banyak di ${top.nama}.`;
    } else {
        return `Total ${formatAngka(total)} ${props.selectedMetric.satuan} ${props.selectedMetric.label}. Terpusat di ${top.nama} sejumlah ${formatAngka(Number(top[key]))} ${props.selectedMetric.satuan}.`;
    }
});

function changeCategory(kat: 'Populasi' | 'Produksi' | 'Penyakit' | 'Fasilitas') {
    emit('update:activeKategori', kat);
    isComboboxOpen.value = false;
    commoditySearch.value = '';
    const firstMetric = METRIC_LIST.find(m => m.kategori === kat);
    if (firstMetric) {
        emit('update:selectedMetric', firstMetric);
    }
}

function selectMetric(metric: MetricDefinition) {
    emit('update:selectedMetric', metric);
    isComboboxOpen.value = false;
    commoditySearch.value = '';
}
</script>

<template>
    <aside class="w-full lg:w-[320px] flex-shrink-0 flex flex-col gap-4 select-none">
        <!-- PANEL 1: DATA PETA (Two-Tier Segmented + Combobox) -->
        <!-- PANEL 1: DATA PETA (Two-Tier Segmented + Combobox) -->
        <section class="glass-card rounded-2xl p-4 border border-[#1E293B] shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-display font-bold text-sm text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Data Peta
                </h3>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider bg-[#0B0F17] px-2 py-0.5 rounded border border-[#1E293B]">Two-Tier</span>
            </div>

            <!-- TIER 1: Segmented Controls (Populasi, Penyakit, Produksi, Fasilitas) - Grid 2x2 Presisi & Simetris -->
            <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#0B0F17] rounded-xl border border-[#1E293B] mb-3">
                <button
                    v-for="kat in (['Populasi', 'Penyakit', 'Produksi', 'Fasilitas'] as const)"
                    :key="kat"
                    @click="changeCategory(kat)"
                    class="h-8 px-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center text-center select-none cursor-pointer"
                    :class="activeKategori === kat 
                        ? 'bg-blue-600 text-white font-bold shadow-sm' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-[#1E293B]/50'"
                >
                    {{ kat }}
                </button>
            </div>

            <!-- TIER 2: Custom Searchable Combobox -->
            <div class="relative">
                <button
                    type="button"
                    @click="isComboboxOpen = !isComboboxOpen"
                    class="w-full h-11 px-3.5 bg-[#0B0F17] border border-[#1E293B] hover:border-slate-600 focus:border-blue-500 rounded-xl flex items-center justify-between text-slate-100 font-semibold text-xs transition-all shadow-sm focus:outline-none cursor-pointer"
                    aria-haspopup="listbox"
                    :aria-expanded="isComboboxOpen"
                >
                    <div class="flex items-center gap-2 truncate">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span class="font-bold text-slate-100 truncate">{{ selectedMetric.label }}</span>
                        <span class="text-xs text-slate-400">({{ selectedMetric.satuan }})</span>
                    </div>
                    <ChevronDown class="w-4 h-4 text-slate-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': isComboboxOpen }" />
                </button>

                <!-- Popover Menu Dropdown Tier 2 -->
                <div
                    v-if="isComboboxOpen"
                    class="absolute top-12 left-0 right-0 z-50 bg-[#111827] border border-blue-500/40 rounded-xl p-2 shadow-2xl backdrop-blur-xl animate-in fade-in zoom-in-95 duration-150"
                >
                    <div class="relative mb-2">
                        <Search class="w-3.5 h-3.5 absolute left-3 top-3 text-slate-400" />
                        <input
                            v-model="commoditySearch"
                            type="text"
                            placeholder="Cari komoditas..."
                            class="w-full h-9 pl-8 pr-3 bg-[#0B0F17] border border-[#1E293B] rounded-lg text-xs text-slate-100 placeholder:text-slate-500 focus:outline-none focus:border-blue-500"
                            autoFocus
                        />
                    </div>
                    <ul class="max-h-48 overflow-y-auto space-y-1 pr-1">
                        <li
                            v-for="item in filteredCommodities"
                            :key="item.id"
                            @click="selectMetric(item)"
                            class="px-3 py-2 rounded-lg text-xs font-medium flex items-center justify-between cursor-pointer transition-colors"
                            :class="selectedMetric.id === item.id 
                                ? 'bg-blue-600/15 text-blue-300 font-bold border border-blue-500/30' 
                                : 'text-slate-300 hover:bg-[#1E293B]/60'"
                        >
                            <span>{{ item.label }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ item.satuan }}</span>
                        </li>
                        <li v-if="filteredCommodities.length === 0" class="px-3 py-3 text-center text-xs text-slate-400">
                            Tidak ada komoditas di kategori ini.
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- PANEL 2: LEGENDA (Hanya Metrik Aktif) -->
        <section class="glass-card rounded-2xl p-4 border border-[#1E293B] shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-display font-bold text-sm text-slate-100">Legenda</h3>
                <span class="text-[10px] font-semibold text-blue-400 px-2 py-0.5 rounded bg-[#0B0F17] border border-blue-500/30">
                    {{ selectedMetric.kategori === 'Penyakit' ? 'Kasus & Zona' : '5 Kelas Kuantil' }}
                </span>
            </div>

            <!-- Legenda Populasi / Kuantitatif (5 Kelas Diskrit) -->
            <div v-if="selectedMetric.kategori !== 'Penyakit'" class="space-y-2 mt-2">
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded border border-white/20 flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[5] }"></span>
                    <span class="text-slate-200 font-semibold">
                        <template v-if="currentRole === 'publik'">Sangat Tinggi</template>
                        <template v-else>{{ formatAngka(quantiles[3] || 0) }} atau lebih {{ selectedMetric.satuan }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded border border-white/20 flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[4] }"></span>
                    <span class="text-slate-300">
                        <template v-if="currentRole === 'publik'">Tinggi</template>
                        <template v-else>{{ formatAngka(quantiles[2] || 0) }} - {{ formatAngka((quantiles[3] || 0) - 1) }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded border border-white/20 flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[3] }"></span>
                    <span class="text-slate-300">
                        <template v-if="currentRole === 'publik'">Sedang</template>
                        <template v-else>{{ formatAngka(quantiles[1] || 0) }} - {{ formatAngka((quantiles[2] || 0) - 1) }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded border border-white/20 flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[2] }"></span>
                    <span class="text-slate-400">
                        <template v-if="currentRole === 'publik'">Rendah</template>
                        <template v-else>{{ formatAngka(quantiles[0] || 0) }} - {{ formatAngka((quantiles[1] || 0) - 1) }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded border border-white/20 flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[1] }"></span>
                    <span class="text-slate-400">
                        <template v-if="currentRole === 'publik'">Sangat Rendah</template>
                        <template v-else>Di bawah {{ formatAngka(quantiles[0] || 0) }}</template>
                    </span>
                </div>
            </div>

            <!-- Legenda Kasus Penyakit (Simbol Proporsional & Pola Zona) -->
            <div v-else class="space-y-2 mt-2">
                <div class="text-[11px] font-semibold text-slate-400 mb-1">Status Zona Wilayah:</div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 h-4 rounded border border-red-500 pattern-bahaya flex-shrink-0"></span>
                    <span class="text-red-400 font-semibold">Zona Bahaya (Merah Garis)</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 h-4 rounded border border-amber-500 pattern-waspada flex-shrink-0"></span>
                    <span class="text-amber-400 font-semibold">Zona Waspada (Kuning Titik)</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 h-4 rounded border border-emerald-500/40 bg-emerald-950/30 flex-shrink-0"></span>
                    <span class="text-emerald-400">Zona Aman (Bebas Kasus)</span>
                </div>

                <div class="pt-2 border-t border-[#1E293B]">
                    <div class="text-[11px] font-semibold text-slate-400 mb-1">Ukuran Simbol Kasus:</div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-red-500/60 border border-red-500"></span>
                            <span>1-3</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-red-500/60 border border-red-500"></span>
                            <span>4-8</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-red-500/60 border border-red-500"></span>
                            <span class="font-bold">≥ 9 kasus</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PANEL 3: PERINGKAT (Kalimat Ringkasan + 5 Teratas) -->
        <section class="glass-card rounded-2xl p-4 border border-[#1E293B] shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-display font-bold text-sm text-slate-100 flex items-center gap-1.5">
                    <Award class="w-4 h-4 text-blue-400" />
                    Peringkat Wilayah
                </h3>
                <span class="text-[10px] font-semibold text-slate-400 bg-[#0B0F17] px-2 py-0.5 rounded border border-[#1E293B]">Top 5</span>
            </div>

            <!-- Kalimat Ringkasan Ramah (Bab 9) -->
            <p class="text-xs text-slate-300 leading-relaxed mb-3 bg-[#0B0F17] p-2.5 rounded-xl border border-[#1E293B]">
                {{ summaryText }}
            </p>

            <!-- 5 Teratas -->
            <ul class="space-y-1.5">
                <li
                    v-for="(kec, idx) in top5List"
                    :key="kec.id"
                    @click="$emit('selectKecamatan', kec.id)"
                    class="p-2 rounded-xl border flex items-center justify-between cursor-pointer transition-all"
                    :class="selectedKecamatanId === kec.id 
                        ? 'bg-blue-950/40 border-blue-500/50 text-blue-300 font-semibold shadow-sm' 
                        : 'bg-[#0B0F17] border-[#1E293B] text-slate-200 hover:bg-[#1E293B]/60'"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="w-5 h-5 rounded-full text-[11px] font-bold flex items-center justify-center"
                            :class="idx === 0 ? 'bg-blue-600 text-white shadow-sm font-black' : 'bg-[#1E293B] text-slate-400'"
                        >
                            {{ idx + 1 }}
                        </span>
                        <div class="flex flex-col text-left">
                            <span class="text-xs font-bold leading-tight">{{ kec.nama }}</span>
                            <span class="text-[9px] text-slate-400 font-mono leading-tight">{{ getDesaByKecamatan(kec.id).length }} Desa</span>
                        </div>
                    </div>

                    <div class="text-xs">
                        <span v-if="currentRole === 'publik'" class="text-[10px] font-medium px-2 py-0.5 rounded bg-blue-600/15 text-blue-400 border border-blue-500/30">
                            {{ idx === 0 ? 'Terbanyak' : 'Tinggi' }}
                        </span>
                        <span v-else class="font-bold text-blue-400 font-mono">
                            {{ formatAngka(Number(kec[selectedMetric.id as keyof KecamatanData])) }} <span class="text-[10px] text-slate-400 font-normal">{{ selectedMetric.satuan }}</span>
                        </span>
                    </div>
                </li>
            </ul>
        </section>

        <!-- TOMBOL TANYA DATA (Chatbot AI Publik) -->
        <button
            @click="$emit('openChat')"
            class="btn-primary-tactile w-full gap-2 text-xs"
        >
            <MessageSquareText class="w-4 h-4 stroke-[2.2]" />
            <span>Tanya Data Peternakan</span>
        </button>
    </aside>
</template>
