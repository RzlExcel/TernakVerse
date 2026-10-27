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
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-none">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-display font-bold text-base text-[#F4F7F5] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#19F5D4]"></span>
                    Data Peta
                </h3>
                <span class="text-[11px] font-bold text-[#8FA1A0] uppercase tracking-wider">Two-Tier</span>
            </div>

            <!-- TIER 1: Segmented Controls (Populasi, Penyakit, Produksi, Fasilitas) - Grid 2x2 Presisi & Simetris -->
            <div class="grid grid-cols-2 gap-1.5 p-1.5 bg-[#0A0D0E] rounded-16 border-2 border-[#243034] mb-3">
                <button
                    v-for="kat in (['Populasi', 'Penyakit', 'Produksi', 'Fasilitas'] as const)"
                    :key="kat"
                    @click="changeCategory(kat)"
                    class="h-9 px-3 text-xs font-bold rounded-10 transition-all flex items-center justify-center text-center select-none"
                    :class="activeKategori === kat 
                        ? 'bg-[#19F5D4] text-[#04110A] font-extrabold shadow-[2px_2px_0_#243034] border border-[#19F5D4]' 
                        : 'bg-[#10171A] border border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5] hover:bg-[#243034] hover:border-[#3A4A4F]'"
                >
                    {{ kat }}
                </button>
            </div>

            <!-- TIER 2: Custom Searchable Combobox (44px, radius 12px, border 2px, hard shadow 3px) -->
            <div class="relative">
                <button
                    type="button"
                    @click="isComboboxOpen = !isComboboxOpen"
                    class="w-full h-11 px-3.5 bg-[#0A0D0E] border-2 border-[#243034] rounded-12 flex items-center justify-between text-[#F4F7F5] font-semibold text-sm transition-all focus:outline-none focus:border-[#19F5D4] shadow-[3px_3px_0_#243034] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none"
                    aria-haspopup="listbox"
                    :aria-expanded="isComboboxOpen"
                >
                    <div class="flex items-center gap-2 truncate">
                        <span class="w-2 h-2 rounded-full bg-[#39FF14]"></span>
                        <span class="font-bold text-[#F4F7F5]">{{ selectedMetric.label }}</span>
                        <span class="text-xs text-[#8FA1A0]">({{ selectedMetric.satuan }})</span>
                    </div>
                    <ChevronDown class="w-4 h-4 text-[#8FA1A0] transition-transform" :class="{ 'rotate-180': isComboboxOpen }" />
                </button>

                <!-- Popover Menu Dropdown Tier 2 -->
                <div
                    v-if="isComboboxOpen"
                    class="absolute top-12 left-0 right-0 z-50 bg-[#10171A] border-2 border-[#19F5D4] rounded-16 p-2 shadow-[3px_3px_0_#19F5D4] animate-in fade-in zoom-in-95 duration-150"
                >
                    <div class="relative mb-2">
                        <Search class="w-3.5 h-3.5 absolute left-3 top-3 text-[#8FA1A0]" />
                        <input
                            v-model="commoditySearch"
                            type="text"
                            placeholder="Cari komoditas..."
                            class="w-full h-9 pl-8 pr-3 bg-[#0A0D0E] border border-[#243034] rounded-10 text-xs text-[#F4F7F5] focus:outline-none focus:border-[#19F5D4]"
                            autoFocus
                        />
                    </div>
                    <ul class="max-h-48 overflow-y-auto space-y-1">
                        <li
                            v-for="item in filteredCommodities"
                            :key="item.id"
                            @click="selectMetric(item)"
                            class="px-3 py-2 rounded-10 text-xs font-semibold flex items-center justify-between cursor-pointer transition-colors"
                            :class="selectedMetric.id === item.id 
                                ? 'bg-[#243034] text-[#19F5D4] font-bold border border-[#19F5D4]/40' 
                                : 'text-[#F4F7F5] hover:bg-[#243034]'"
                        >
                            <span>{{ item.label }}</span>
                            <span class="text-[11px] text-[#8FA1A0]">{{ item.satuan }}</span>
                        </li>
                        <li v-if="filteredCommodities.length === 0" class="px-3 py-3 text-center text-xs text-[#8FA1A0]">
                            Tidak ada komoditas di kategori ini.
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- PANEL 2: LEGENDA (Hanya Metrik Aktif) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-none">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-display font-bold text-base text-[#F4F7F5]">Legenda</h3>
                <span class="text-[11px] font-bold text-[#19F5D4] px-2 py-0.5 rounded-full bg-[#0A0D0E] border border-[#243034]">
                    {{ selectedMetric.kategori === 'Penyakit' ? 'Kasus & Zona' : '5 Kelas Kuantil' }}
                </span>
            </div>

            <!-- Legenda Populasi / Kuantitatif (5 Kelas Diskrit) -->
            <div v-if="selectedMetric.kategori !== 'Penyakit'" class="space-y-2 mt-2">
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#F4F7F5] flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[5] }"></span>
                    <span class="text-[#F4F7F5] font-bold">
                        <template v-if="currentRole === 'publik'">Sangat Tinggi</template>
                        <template v-else>{{ formatAngka(quantiles[3] || 0) }} atau lebih {{ selectedMetric.satuan }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#F4F7F5] flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[4] }"></span>
                    <span class="text-[#F4F7F5]">
                        <template v-if="currentRole === 'publik'">Tinggi</template>
                        <template v-else>{{ formatAngka(quantiles[2] || 0) }} - {{ formatAngka((quantiles[3] || 0) - 1) }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#F4F7F5] flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[3] }"></span>
                    <span class="text-[#F4F7F5]">
                        <template v-if="currentRole === 'publik'">Sedang</template>
                        <template v-else>{{ formatAngka(quantiles[1] || 0) }} - {{ formatAngka((quantiles[2] || 0) - 1) }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#F4F7F5] flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[2] }"></span>
                    <span class="text-[#F4F7F5]">
                        <template v-if="currentRole === 'publik'">Rendah</template>
                        <template v-else>{{ formatAngka(quantiles[0] || 0) }} - {{ formatAngka((quantiles[1] || 0) - 1) }}</template>
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#F4F7F5] flex-shrink-0" :style="{ backgroundColor: COLOR_CLASSES[1] }"></span>
                    <span class="text-[#8FA1A0]">
                        <template v-if="currentRole === 'publik'">Sangat Rendah</template>
                        <template v-else>Di bawah {{ formatAngka(quantiles[0] || 0) }}</template>
                    </span>
                </div>
            </div>

            <!-- Legenda Kasus Penyakit (Simbol Proporsional & Pola Zona) -->
            <div v-else class="space-y-2 mt-2">
                <div class="text-[11px] font-bold text-[#8FA1A0] mb-1">Status Zona Wilayah:</div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#FF5C5C] pattern-bahaya flex-shrink-0"></span>
                    <span class="text-[#FF5C5C] font-bold">Zona Bahaya (Merah Garis)</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#FFC53D] pattern-waspada flex-shrink-0"></span>
                    <span class="text-[#FFC53D] font-bold">Zona Waspada (Kuning Titik)</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 h-4 rounded-4 border-2 border-[#3A4A4F] bg-[#10171A] flex-shrink-0"></span>
                    <span class="text-[#8FA1A0]">Zona Aman (Polos Bebas Kasus)</span>
                </div>

                <div class="pt-2 border-t border-[#243034]">
                    <div class="text-[11px] font-bold text-[#8FA1A0] mb-1">Ukuran Simbol Kasus:</div>
                    <div class="flex items-center gap-3 text-xs text-[#F4F7F5]">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-[#FF5C5C]/60 border border-[#FF5C5C]"></span>
                            <span>1-3</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-[#FF5C5C]/60 border border-[#FF5C5C]"></span>
                            <span>4-8</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-[#FF5C5C]/60 border border-[#FF5C5C]"></span>
                            <span class="font-bold">≥ 9 kasus</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PANEL 3: PERINGKAT (Kalimat Ringkasan + 5 Teratas) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-4 shadow-none">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-display font-bold text-base text-[#F4F7F5] flex items-center gap-1.5">
                    <Award class="w-4 h-4 text-[#39FF14]" />
                    Peringkat Wilayah
                </h3>
                <span class="text-[11px] font-bold text-[#8FA1A0]">Top 5</span>
            </div>

            <!-- Kalimat Ringkasan Ramah (Bab 9) -->
            <p class="text-xs text-[#8FA1A0] leading-relaxed mb-3 bg-[#0A0D0E] p-2.5 rounded-12 border border-[#243034]">
                {{ summaryText }}
            </p>

            <!-- 5 Teratas -->
            <ul class="space-y-1.5">
                <li
                    v-for="(kec, idx) in top5List"
                    :key="kec.id"
                    @click="$emit('selectKecamatan', kec.id)"
                    class="p-2 rounded-12 border border-[#243034] flex items-center justify-between cursor-pointer transition-all"
                    :class="selectedKecamatanId === kec.id 
                        ? 'bg-[#243034] border-[#19F5D4] text-[#19F5D4] font-bold shadow-[2px_2px_0_#19F5D4]' 
                        : 'bg-[#0A0D0E]/60 text-[#F4F7F5] hover:bg-[#243034] hover:border-[#3A4A4F]'"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="w-5 h-5 rounded-full text-[11px] font-extrabold flex items-center justify-center"
                            :class="idx === 0 ? 'bg-[#39FF14] text-[#04110A]' : 'bg-[#243034] text-[#8FA1A0]'"
                        >
                            {{ idx + 1 }}
                        </span>
                        <div class="flex flex-col text-left">
                            <span class="text-xs font-bold leading-tight">{{ kec.nama }}</span>
                            <span class="text-[9px] text-[#8FA1A0] font-mono leading-tight">{{ getDesaByKecamatan(kec.id).length }} Desa</span>
                        </div>
                    </div>

                    <div class="text-xs">
                        <span v-if="currentRole === 'publik'" class="text-[11px] px-2 py-0.5 rounded-full bg-[#10171A] text-[#19F5D4] border border-[#243034]">
                            {{ idx === 0 ? 'Terbanyak' : 'Tinggi' }}
                        </span>
                        <span v-else class="font-bold text-[#19F5D4]">
                            {{ formatAngka(Number(kec[selectedMetric.id as keyof KecamatanData])) }} <span class="text-[10px] text-[#8FA1A0] font-normal">{{ selectedMetric.satuan }}</span>
                        </span>
                    </div>
                </li>
            </ul>
        </section>

        <!-- TOMBOL TANYA DATA (Chatbot AI Publik) -->
        <button
            @click="$emit('openChat')"
            class="btn-primary-tactile w-full gap-2 text-sm"
        >
            <MessageSquareText class="w-4 h-4 stroke-[2.5]" />
            <span>Tanya Data Peternakan</span>
        </button>
    </aside>
</template>

<style scoped>
.rounded-4 {
    border-radius: 4px;
}
.rounded-10 {
    border-radius: 10px;
}
</style>
