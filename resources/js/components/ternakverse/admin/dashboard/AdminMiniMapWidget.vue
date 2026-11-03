<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Compass, ChevronDown, ArrowRight } from 'lucide-vue-next';

const emit = defineEmits<{
    (e: 'openWebGis'): void;
    (e: 'open-web-gis'): void;
}>();

function handleOpenWebGis() {
    emit('openWebGis');
    emit('open-web-gis');
}
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import banyumasKecamatanGeoJson from '@/data/banyumas_kecamatan_web.json';
import {
    KECAMATAN_LIST,
    calculateQuantiles,
    getClassLevel,
    formatAngka,
    COLOR_CLASSES,
    type KecamatanData,
} from '@/data/banyumasData';

type CommodityKey = 'sapi_potong' | 'sapi_perah' | 'kambing' | 'domba' | 'ayam_potong';

interface CommodityOption {
    id: CommodityKey;
    label: string;
}

const commodityOptions: CommodityOption[] = [
    { id: 'sapi_potong', label: 'Sapi Potong' },
    { id: 'sapi_perah', label: 'Sapi Perah' },
    { id: 'kambing', label: 'Kambing' },
    { id: 'domba', label: 'Domba' },
    { id: 'ayam_potong', label: 'Ayam Potong' },
];

const selectedCommodity = ref<CommodityKey>('sapi_potong');

// 5 classes Master Prompt multicolor scale
const CHOROPLETH_5_CLASSES: Record<number, string> = COLOR_CLASSES || {
    1: '#3B82A0', // Sangat Rendah
    2: '#38A69A', // Rendah
    3: '#86A65A', // Sedang
    4: '#D4A34E', // Tinggi
    5: '#D9784D', // Sangat Tinggi
};

const legendItems = [
    { label: 'Sangat Tinggi', color: '#D9784D', level: 5 },
    { label: 'Tinggi', color: '#D4A34E', level: 4 },
    { label: 'Sedang', color: '#86A65A', level: 3 },
    { label: 'Rendah', color: '#38A69A', level: 2 },
    { label: 'Sangat Rendah', color: '#3B82A0', level: 1 },
];

// Map lookup data atribut berdasarkan nama kecamatan
const kecDataLookup = computed(() => {
    const mapObj = new Map<string, KecamatanData>();
    KECAMATAN_LIST.forEach((k) => {
        mapObj.set(k.nama.toLowerCase(), k);
    });
    return mapObj;
});

function getKecamatanDataFromFeature(feature: any): KecamatanData | undefined {
    const rawName = feature?.properties?.kecamatan || feature?.properties?.nama || '';
    const cleanName = rawName.replace(/^kecamatan\s+/i, '').trim().toLowerCase();
    return kecDataLookup.value.get(cleanName) || kecDataLookup.value.get(rawName.toLowerCase());
}

const quantiles = computed(() => {
    return calculateQuantiles(KECAMATAN_LIST, selectedCommodity.value as keyof KecamatanData);
});

function getFeatureStyle(feature: any) {
    const kec = getKecamatanDataFromFeature(feature);
    let fillColor = '#00B98B';
    if (kec) {
        const val = Number(kec[selectedCommodity.value as keyof KecamatanData]) || 0;
        const lvl = getClassLevel(val, quantiles.value);
        fillColor = CHOROPLETH_5_CLASSES[lvl] ?? '#00B98B';
    }

    return {
        fillColor,
        fillOpacity: 0.5,
        color: '#00B98B',
        weight: 1.5,
        opacity: 0.9,
    };
}

function getTooltipContent(feature: any): string {
    const kec = getKecamatanDataFromFeature(feature);
    if (!kec) return '';
    const val = Number(kec[selectedCommodity.value as keyof KecamatanData]) || 0;
    return `
        <div style="font-family:'Inter',sans-serif;font-size:12px;font-weight:700;color:#E8F0F6;text-align:center;">
            ${kec.nama}
            <div style="font-size:11px;color:#39D6C0;font-weight:600;font-variant-numeric:tabular-nums;margin-top:2px;">
                ${formatAngka(val)} ekor
            </div>
        </div>
    `;
}

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let geojsonLayer: L.GeoJSON | null = null;

function onEachFeature(feature: any, layer: L.Layer) {
    layer.bindTooltip(getTooltipContent(feature), {
        permanent: false,
        direction: 'top',
        className: 'admin-minimap-tooltip',
    });

    layer.on('mouseover', () => {
        (layer as L.Path).setStyle({
            weight: 2.5,
            color: '#FFFFFF',
            fillOpacity: 0.75,
        });
    });

    layer.on('mouseout', () => {
        (layer as L.Path).setStyle(getFeatureStyle(feature));
    });
}

function initMap() {
    if (!mapContainer.value) return;

    if (map) {
        try {
            map.remove();
        } catch {}
        map = null;
    }
    if ((mapContainer.value as any)._leaflet_id) {
        delete (mapContainer.value as any)._leaflet_id;
    }

    map = L.map(mapContainer.value, {
        preferCanvas: true,
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
    });

    // Citra satelit asli resolusi tinggi Esri World Imagery
    L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 19,
        }
    ).addTo(map);

    // Hybrid toponimi & batas wilayah
    L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 19,
            opacity: 0.7,
        }
    ).addTo(map);

    // Layer GeoJSON resmi Banyumas
    geojsonLayer = L.geoJSON(banyumasKecamatanGeoJson as any, {
        style: getFeatureStyle,
        onEachFeature: onEachFeature,
    }).addTo(map);

    // Fit bounds agar seluruh wilayah Banyumas terpusat sempurna
    if (geojsonLayer.getBounds().isValid()) {
        map.fitBounds(geojsonLayer.getBounds(), { padding: [8, 8] });
    }
}

watch(selectedCommodity, () => {
    if (!geojsonLayer) return;
    geojsonLayer.setStyle(getFeatureStyle);
    geojsonLayer.eachLayer((layer: any) => {
        if (layer.feature) {
            layer.setTooltipContent(getTooltipContent(layer.feature));
        }
    });
});

onMounted(() => {
    initMap();
    setTimeout(() => {
        if (map && geojsonLayer && geojsonLayer.getBounds().isValid()) {
            map.invalidateSize();
            map.fitBounds(geojsonLayer.getBounds(), { padding: [8, 8] });
        }
    }, 150);
});

onUnmounted(() => {
    if (map) {
        try {
            map.remove();
        } catch {}
        map = null;
    }
});
</script>

<template>
    <div
        class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between w-full h-full relative transition-colors"
    >
        <!-- Header -->
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-start gap-2.5">
                <div class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[#00D084] flex items-center justify-center shrink-0 mt-0.5">
                    <Compass class="w-4 h-4 text-[#00D084]" />
                </div>
                <div>
                    <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] leading-snug">
                        Sebaran Populasi Wilayah
                    </h3>
                    <p class="text-xs dark:text-[#858585] text-[#737B71] mt-0.5">
                        Kondisi populasi ternak per kecamatan
                    </p>
                </div>
            </div>

            <!-- Commodity Select Dropdown -->
            <div class="relative shrink-0">
                <select
                    v-model="selectedCommodity"
                    class="appearance-none dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-lg px-2.5 py-1.5 pr-7 text-xs dark:text-[#F5F5F5] text-[#171B17] focus:outline-none focus:border-[#00D084] transition-colors cursor-pointer"
                >
                    <option
                        v-for="opt in commodityOptions"
                        :key="opt.id"
                        :value="opt.id"
                        class="dark:bg-[#111111] bg-white dark:text-[#F5F5F5] text-[#171B17]"
                    >
                        {{ opt.label }}
                    </option>
                </select>
                <ChevronDown class="w-3.5 h-3.5 dark:text-[#858585] text-[#737B71] absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
        </div>

        <!-- Content: Leaflet Mini Map & Right-side Legend -->
        <div class="relative flex-1 flex flex-row items-center justify-between gap-3 py-2 min-h-[220px]">
            <!-- Leaflet Map Canvas -->
            <div
                ref="mapContainer"
                class="relative flex-1 h-[220px] rounded-lg overflow-hidden border dark:border-[#1E1E1E] border-[#E0E4DD] shadow-inner bg-[#090909] z-0"
            ></div>

            <!-- Right side: 5-class Legend -->
            <div class="flex flex-col gap-2 shrink-0 dark:bg-[#181818] bg-[#F4F5F2] p-2.5 rounded-lg border dark:border-[#1E1E1E] border-[#E0E4DD]">
                <div class="text-[10px] font-bold uppercase tracking-wider dark:text-[#858585] text-[#737B71] mb-0.5">
                    Tingkat Sebaran
                </div>
                <div
                    v-for="item in legendItems"
                    :key="item.label"
                    class="flex items-center gap-2"
                >
                    <span
                        class="w-2.5 h-2.5 rounded-full shrink-0 shadow-xs"
                        :style="{ backgroundColor: item.color }"
                    ></span>
                    <span class="text-[11px] dark:text-[#F5F5F5] text-[#171B17] font-medium whitespace-nowrap">
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Bottom-Right Action Button -->
        <div class="flex items-center justify-end pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] mt-2">
            <button
                type="button"
                @click="handleOpenWebGis"
                class="text-[#00D084] hover:text-[#00F0A0] text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer group"
                title="Lihat Detail Peta (Buka WebGIS Admin)"
            >
                <Compass class="w-3.5 h-3.5 text-[#00D084]" />
                <span>Lihat Detail Peta</span>
                <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
            </button>
        </div>
    </div>
</template>

<style>
/* Leaflet Tooltips for Admin MiniMap matching Pure Obsidian Dark GIS */
.admin-minimap-tooltip {
    background-color: #111111 !important;
    border: 1px solid #00D084 !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7) !important;
    padding: 6px 10px !important;
    pointer-events: none !important;
}

.admin-minimap-tooltip::before {
    display: none !important;
}

.leaflet-container {
    background-color: #090909 !important;
    font-family: inherit !important;
}
</style>
