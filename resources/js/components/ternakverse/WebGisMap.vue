<script setup lang="ts">
import { ref, computed } from 'vue';
import { KecamatanData, MetricDefinition, getClassLevel, COLOR_CLASSES, formatAngka } from '../../data/banyumasData';
import { Plus, Minus, Maximize2, Compass, Info, X } from 'lucide-vue-next';

const props = defineProps<{
    kecamatanList: KecamatanData[];
    selectedMetric: MetricDefinition;
    quantiles: number[];
    currentRole: 'publik' | 'dinas' | 'admin';
    selectedKecamatanId?: string;
}>();

const emit = defineEmits<{
    (e: 'selectKecamatan', id: string, event?: MouseEvent): void;
}>();

// Zoom & Pan State
const zoomLevel = ref(1.0);
const panX = ref(0);
const panY = ref(10);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Koordinat kursor live (Bab 5.5)
const cursorCoord = ref({ bt: '109,14° BT', ls: '7,45° LS' });

// Popover Sumber Data
const showDataSource = ref(false);

// Hover tooltip mini (desktop)
const hoveredKecamatan = ref<KecamatanData | null>(null);
const tooltipPos = ref({ x: 0, y: 0 });

// Kecamatan Peringkat 1 untuk Stiker Sorotan (Hanya 1 per layar, Bab 5.4)
const topRankKecamatan = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const sorted = [...props.kecamatanList].sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0));
    return sorted[0];
});

const topRankValueFormatted = computed(() => {
    if (!topRankKecamatan.value) return '';
    const key = props.selectedMetric.id as keyof KecamatanData;
    const val = Number(topRankKecamatan.value[key]) || 0;
    const unit = props.selectedMetric.satuan || (props.selectedMetric.kategori === 'Penyakit' ? 'kasus' : 'ekor');
    return `${formatAngka(val)} ${unit}`;
});

function handleMouseDown(e: MouseEvent) {
    if ((e.target as HTMLElement).tagName.toLowerCase() === 'path') return;
    isDragging.value = true;
    dragStart.value = { x: e.clientX - panX.value, y: e.clientY - panY.value };
}

function handleMouseMove(e: MouseEvent) {
    // Update live coordinates simulation
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const relX = (e.clientX - rect.left) / rect.width;
    const relY = (e.clientY - rect.top) / rect.height;
    const bt = (108.95 + relX * 0.45).toFixed(2).replace('.', ',');
    const ls = (7.30 + relY * 0.35).toFixed(2).replace('.', ',');
    cursorCoord.value = { bt: `${bt}° BT`, ls: `${ls}° LS` };

    if (isDragging.value) {
        panX.value = e.clientX - dragStart.value.x;
        panY.value = e.clientY - dragStart.value.y;
    }
}

function handleMouseUp() {
    isDragging.value = false;
}

function zoomIn() {
    zoomLevel.value = Math.min(zoomLevel.value + 0.25, 2.5);
}

function zoomOut() {
    zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.75);
}

function resetZoom() {
    zoomLevel.value = 1.0;
    panX.value = 0;
    panY.value = 10;
}

function handleKecamatanHover(kec: KecamatanData, e: MouseEvent) {
    hoveredKecamatan.value = kec;
    tooltipPos.value = { x: e.clientX, y: e.clientY };
}

function handleKecamatanLeave() {
    hoveredKecamatan.value = null;
}

// Menentukan warna isi poligon
function getPolygonFill(kec: KecamatanData): string {
    if (props.selectedMetric.kategori === 'Penyakit') {
        return '#243034'; // Neutral fill untuk peta penyakit, pola zona dan simbol proporsional dipakai
    }
    const val = Number(kec[props.selectedMetric.id as keyof KecamatanData]) || 0;
    const lvl = getClassLevel(val, props.quantiles);
    return COLOR_CLASSES[lvl];
}

// Menentukan warna teks label nama kecamatan
function getLabelColor(kec: KecamatanData): string {
    if (props.selectedMetric.kategori === 'Penyakit') return '#F4F7F5';
    const val = Number(kec[props.selectedMetric.id as keyof KecamatanData]) || 0;
    const lvl = getClassLevel(val, props.quantiles);
    return lvl >= 4 ? '#04110A' : '#F4F7F5';
}

// Radius simbol proporsional PMK/LSD
function getSymbolRadius(val: number): number {
    if (val <= 0) return 0;
    return Math.min(6 + Math.sqrt(val) * 4.5, 22);
}
</script>

<template>
    <div class="relative w-full h-[620px] lg:h-[700px] bg-[#0A0D0E] border-2 border-[#243034] rounded-24 overflow-hidden select-none flex flex-col">
        <!-- HEADER DI ATAS PETA (Judul & Subjudul Bab 4.1 & 9) -->
        <div class="p-4 sm:p-5 pb-2 z-10 bg-gradient-to-b from-[#0A0D0E] via-[#0A0D0E]/90 to-transparent flex items-start justify-between">
            <div>
                <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-[#F4F7F5] tracking-tight">
                    Peta Persebaran {{ selectedMetric.label }}
                </h1>
                <p class="text-xs sm:text-sm text-[#8FA1A0] mt-1">
                    <template v-if="selectedMetric.kategori === 'Penyakit'">
                        Makin besar simbolnya, makin banyak kasusnya. Pola menunjukkan status zona.
                    </template>
                    <template v-else-if="currentRole === 'publik'">
                        Makin terang warnanya, makin banyak ternaknya. Klik poligon untuk info lengkap.
                    </template>
                    <template v-else>
                        Warna menunjukkan jumlah ekor per kecamatan. Angka detail aktif untuk Petugas Dinas.
                    </template>
                </p>
            </div>

            <!-- Stiker Sorotan Tunggal di Sudut (Maks 1 per layar, Simetris) -->
            <div v-if="topRankKecamatan" class="hidden sm:flex items-center">
                <button
                    type="button"
                    @click="$emit('selectKecamatan', topRankKecamatan.id, $event)"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full border-2 border-[#F4F7F5] font-extrabold text-xs shadow-[0_2px_8px_rgba(0,0,0,0.5)] transition-all select-none hover:scale-105 active:scale-95 cursor-pointer"
                    :class="selectedMetric.kategori === 'Penyakit' ? 'bg-[#FFC53D] text-[#04110A]' : 'bg-[#39FF14] text-[#04110A]'"
                    :title="`Klik untuk memilih Kecamatan ${topRankKecamatan.nama}`"
                >
                    <span class="text-xs leading-none select-none">⭐</span>
                    <span class="leading-none tracking-normal">
                        {{ selectedMetric.kategori === 'Penyakit' ? 'Zona Terkritis: ' : 'Terbanyak: ' }}
                        <span class="font-black">{{ topRankKecamatan.nama }}</span>
                        <span> ({{ topRankValueFormatted }})</span>
                    </span>
                </button>
            </div>
        </div>

        <!-- AREA KANVAS SVG PETA BANYUMAS -->
        <div
            class="flex-1 w-full h-full relative cursor-grab active:cursor-grabbing overflow-hidden"
            @mousedown="handleMouseDown"
            @mousemove="handleMouseMove"
            @mouseup="handleMouseUp"
            @mouseleave="handleMouseUp"
        >
            <svg
                viewBox="0 0 800 580"
                class="w-full h-full"
                style="transform-origin: center center;"
            >
                <defs>
                    <!-- Pola Garis Diagonal untuk Zona Bahaya (Bab 5.2) -->
                    <pattern id="diag-merah" width="10" height="10" patternTransform="rotate(45 0 0)" patternUnits="userSpaceOnUse">
                        <line x1="0" y1="0" x2="0" y2="10" stroke="#FF5C5C" stroke-width="3" stroke-opacity="0.8" />
                    </pattern>
                    <!-- Pola Titik Kuning untuk Zona Waspada (Bab 5.2) -->
                    <pattern id="dots-kuning" width="10" height="10" patternUnits="userSpaceOnUse">
                        <circle cx="4" cy="4" r="2" fill="#FFC53D" fill-opacity="0.9" />
                    </pattern>
                </defs>

                <!-- Layer Transform Peta dengan Zoom & Pan -->
                <g :transform="`translate(${panX}, ${panY}) scale(${zoomLevel})`" class="transition-transform duration-75">
                    
                    <!-- Label Kabupaten Tetangga di Luar Batas (Bab 5.1) -->
                    <text x="310" y="55" class="fill-[#8FA1A0] text-sm italic font-ui font-medium pointer-events-none">Kab. Tegal & Brebes</text>
                    <text x="660" y="240" class="fill-[#8FA1A0] text-sm italic font-ui font-medium pointer-events-none">Kab. Purbalingga</text>
                    <text x="680" y="560" class="fill-[#8FA1A0] text-sm italic font-ui font-medium pointer-events-none">Kab. Kebumen</text>
                    <text x="50" y="470" class="fill-[#8FA1A0] text-sm italic font-ui font-medium pointer-events-none">Kab. Cilacap</text>

                    <!-- Poligon 27 Kecamatan -->
                    <g id="layer-poligon">
                        <g
                            v-for="kec in kecamatanList"
                            :key="kec.id"
                            class="transition-all duration-150 cursor-pointer group"
                            @click="$emit('selectKecamatan', kec.id, $event)"
                            @mouseenter="handleKecamatanHover(kec, $event)"
                            @mouseleave="handleKecamatanLeave"
                        >
                            <!-- Poligon Dasar -->
                            <path
                                :d="kec.path"
                                :fill="getPolygonFill(kec)"
                                stroke="#0A0D0E"
                                stroke-width="2.5"
                                stroke-linejoin="round"
                                class="transition-all duration-200 group-hover:scale-[1.015] origin-center"
                                :class="[
                                    selectedKecamatanId === kec.id ? 'stroke-[#39FF14] stroke-[3.5] z-30' : 'group-hover:stroke-[#F4F7F5] group-hover:stroke-[2.5]'
                                ]"
                            />

                            <!-- Pola Penyakit Overlay jika Metrik Penyakit aktif -->
                            <path
                                v-if="selectedMetric.kategori === 'Penyakit' && kec.zona === 'bahaya'"
                                :d="kec.path"
                                fill="url(#diag-merah)"
                                class="pointer-events-none opacity-80"
                            />
                            <path
                                v-if="selectedMetric.kategori === 'Penyakit' && kec.zona === 'waspada'"
                                :d="kec.path"
                                fill="url(#dots-kuning)"
                                class="pointer-events-none opacity-80"
                            />

                            <!-- Simbol Kasus Proporsional PMK (Lingkaran Merah) & LSD (Belah Ketupat) (Bab 5.2) -->
                            <template v-if="selectedMetric.kategori === 'Penyakit'">
                                <!-- Lingkaran PMK -->
                                <circle
                                    v-if="selectedMetric.id === 'pmk' && kec.pmk > 0"
                                    :cx="kec.center[0]"
                                    :cy="kec.center[1]"
                                    :r="getSymbolRadius(kec.pmk)"
                                    fill="#FF5C5C"
                                    fill-opacity="0.65"
                                    stroke="#FF5C5C"
                                    stroke-width="2"
                                    class="pointer-events-none"
                                />
                                <!-- Belah Ketupat LSD -->
                                <polygon
                                    v-if="selectedMetric.id === 'lsd' && kec.lsd > 0"
                                    :points="`
                                        ${kec.center[0]},${kec.center[1] - getSymbolRadius(kec.lsd)} 
                                        ${kec.center[0] + getSymbolRadius(kec.lsd)},${kec.center[1]} 
                                        ${kec.center[0]},${kec.center[1] + getSymbolRadius(kec.lsd)} 
                                        ${kec.center[0] - getSymbolRadius(kec.lsd)},${kec.center[1]}
                                    `"
                                    fill="#FF5C5C"
                                    fill-opacity="0.65"
                                    stroke="#FF5C5C"
                                    stroke-width="2"
                                    class="pointer-events-none"
                                />
                            </template>

                            <!-- Label Nama Kecamatan & Angka (Bab 5.1) -->
                            <g class="pointer-events-none">
                                <text
                                    :x="kec.center[0]"
                                    :y="selectedMetric.kategori === 'Penyakit' ? kec.center[1] - 12 : kec.center[1]"
                                    text-anchor="middle"
                                    class="font-ui font-bold select-none text-[12px] sm:text-[13px] tracking-tight"
                                    :fill="getLabelColor(kec)"
                                >
                                    {{ kec.nama }}
                                </text>

                                <!-- Angka Kuantitatif Mode Dinas -->
                                <text
                                    v-if="currentRole !== 'publik'"
                                    :x="kec.center[0]"
                                    :y="selectedMetric.kategori === 'Penyakit' ? kec.center[1] + 16 : kec.center[1] + 13"
                                    text-anchor="middle"
                                    class="font-display font-extrabold select-none text-[11px]"
                                    :fill="getLabelColor(kec)"
                                >
                                    {{ formatAngka(Number(kec[selectedMetric.id as keyof KecamatanData])) }}
                                </text>
                            </g>
                        </g>
                    </g>
                </g>
            </svg>

            <!-- KONTROL ZOOM MELAYANG (Kanan Atas, Bab 5.5) -->
            <div class="absolute top-4 right-4 z-20 flex flex-col gap-1.5 bg-[#10171A] border-2 border-[#3A4A4F] rounded-16 p-1.5 shadow-[3px_3px_0_#243034]">
                <button
                    @click="zoomIn"
                    class="w-10 h-10 rounded-12 bg-[#0A0D0E] border border-[#243034] text-[#F4F7F5] flex items-center justify-center hover:bg-[#243034] hover:text-[#19F5D4] transition-colors"
                    title="Perbesar Peta"
                >
                    <Plus class="w-5 h-5 stroke-[2.5]" />
                </button>
                <button
                    @click="zoomOut"
                    class="w-10 h-10 rounded-12 bg-[#0A0D0E] border border-[#243034] text-[#F4F7F5] flex items-center justify-center hover:bg-[#243034] hover:text-[#19F5D4] transition-colors"
                    title="Perkecil Peta"
                >
                    <Minus class="w-5 h-5 stroke-[2.5]" />
                </button>
                <button
                    @click="resetZoom"
                    class="w-10 h-10 rounded-12 bg-[#0A0D0E] border border-[#243034] text-[#F4F7F5] flex items-center justify-center hover:bg-[#243034] hover:text-[#39FF14] transition-colors"
                    title="Pas Layar"
                >
                    <Maximize2 class="w-4 h-4 stroke-[2.5]" />
                </button>
            </div>

            <!-- INDIKATOR SPASIAL (Kiri Bawah Dalam Bingkai, Bab 5.5) -->
            <div class="absolute bottom-4 left-4 z-20 flex items-center gap-3 bg-[#10171A]/90 border border-[#243034] px-3 py-1.5 rounded-full backdrop-blur-none text-xs text-[#8FA1A0] font-ui">
                <!-- Arah Utara -->
                <div class="flex items-center gap-1 text-[#19F5D4] font-bold">
                    <Compass class="w-3.5 h-3.5" />
                    <span>U</span>
                </div>
                <div class="h-3 w-[1px] bg-[#243034]"></div>
                <!-- Skala Batang -->
                <div class="flex items-center gap-1.5">
                    <div class="w-10 h-1.5 bg-[#243034] border border-[#3A4A4F] relative">
                        <div class="w-1/2 h-full bg-[#19F5D4]"></div>
                    </div>
                    <span class="text-[11px] font-mono">5 km</span>
                </div>
                <div class="h-3 w-[1px] bg-[#243034]"></div>
                <!-- Koordinat Kursor -->
                <span class="font-mono text-[11px] text-[#F4F7F5]">{{ cursorCoord.bt }}, {{ cursorCoord.ls }}</span>
            </div>

            <!-- TOMBOL SUMBER DATA (Kanan Bawah -> Popover, Bab 5.5) -->
            <div class="absolute bottom-4 right-4 z-20">
                <button
                    @click="showDataSource = !showDataSource"
                    class="px-3 py-1.5 bg-[#10171A] border border-[#3A4A4F] text-[#8FA1A0] hover:text-[#19F5D4] hover:border-[#19F5D4] rounded-full text-xs font-semibold flex items-center gap-1.5 transition-all shadow-[2px_2px_0_#243034]"
                >
                    <Info class="w-3.5 h-3.5" />
                    <span>Sumber data</span>
                </button>

                <!-- Popover Sumber Data -->
                <div
                    v-if="showDataSource"
                    class="absolute bottom-10 right-0 w-72 bg-[#10171A] border-2 border-[#19F5D4] rounded-16 p-4 text-xs shadow-[3px_3px_0_#19F5D4] z-50 animate-in fade-in"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-[#F4F7F5]">Sumber & Glosarium Data</span>
                        <button @click="showDataSource = false" class="text-[#8FA1A0] hover:text-[#F4F7F5]">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <p class="text-[#8FA1A0] mb-2 leading-relaxed">
                        Data resmi dihimpun oleh <strong class="text-[#F4F7F5]">Dinas Perikanan dan Peternakan Kabupaten Banyumas</strong> periode sinkronisasi Oktober 2026.
                    </p>
                    <div class="space-y-1.5 border-t border-[#243034] pt-2 text-[11px]">
                        <div><strong class="text-[#FF5C5C]">PMK:</strong> Penyakit Mulut dan Kuku (Aphtae epizooticae).</div>
                        <div><strong class="text-[#FFC53D]">LSD:</strong> Lumpy Skin Disease (Cacar Sapi Berbenjol).</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
