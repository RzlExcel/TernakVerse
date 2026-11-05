<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Compass, ChevronDown, ArrowRight } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import banyumasKecamatanGeoJson from '@/data/banyumas_kecamatan_web.json';
import {
    KECAMATAN_LIST,
    formatAngka,
    COLOR_CLASSES,
    type KecamatanData,
} from '@/data/banyumasData';

interface MiniMapProps {
    selectedCardId?: string;
    kecamatanList?: KecamatanData[];
}

const props = withDefaults(defineProps<MiniMapProps>(), {
    selectedCardId: 'card-populasi',
    kecamatanList: () => [],
});

const emit = defineEmits<{
    (e: 'openWebGis'): void;
    (e: 'open-web-gis'): void;
}>();

function handleOpenWebGis() {
    emit('openWebGis');
    emit('open-web-gis');
}

const activeMode = computed<'card-populasi' | 'card-produksi' | 'card-penyakit' | 'card-fasilitas'>(() => {
    const raw = (props.selectedCardId || '').toLowerCase();
    if (raw.includes('produksi') || raw.includes('pangan') || raw.includes('daging')) {
        return 'card-produksi';
    }
    if (raw.includes('penyakit') || raw.includes('pmk') || raw.includes('lsd') || raw.includes('wabah')) {
        return 'card-penyakit';
    }
    if (raw.includes('fasilitas') || raw.includes('rph') || raw.includes('poskeswan')) {
        return 'card-fasilitas';
    }
    return 'card-populasi';
});

const mapTitle = computed(() => {
    switch (activeMode.value) {
        case 'card-penyakit':
            return 'Status Kewaspadaan & Zonasi EWS';
        case 'card-produksi':
            return 'Sebaran Produksi Pangan';
        case 'card-fasilitas':
            return 'Sebaran Fasilitas RPH & Poskeswan';
        case 'card-populasi':
        default:
            return 'Sebaran Populasi Ternak';
    }
});

const mapSubtitle = computed(() => {
    switch (activeMode.value) {
        case 'card-penyakit':
            return 'Zonasi persebaran kasus PMK & LSD aktif';
        case 'card-produksi':
            return 'Hasil produksi pangan ternak per kecamatan';
        case 'card-fasilitas':
            return 'Sebaran poskeswan & rumah potong hewan';
        case 'card-populasi':
        default:
            return 'Sebaran Populasi Wilayah & kondisi ternak per kecamatan';
    }
});

interface FilterOption {
    id: string;
    label: string;
}

const filterOptions = computed<FilterOption[]>(() => {
    switch (activeMode.value) {
        case 'card-penyakit':
            return [
                { id: 'total_wabah', label: 'PMK & LSD (Total)' },
                { id: 'pmk', label: 'Kasus PMK' },
                { id: 'lsd', label: 'Kasus LSD' },
            ];
        case 'card-produksi':
            return [
                { id: 'total_produksi', label: 'Total Produksi' },
                { id: 'daging_ayam', label: 'Daging Ayam' },
                { id: 'daging_sapi', label: 'Daging Sapi' },
                { id: 'susu_sapi', label: 'Susu Sapi' },
                { id: 'telur_ayam', label: 'Telur Ayam' },
            ];
        case 'card-fasilitas':
            return [
                { id: 'poskeswan', label: 'Poskeswan & RPH' },
            ];
        case 'card-populasi':
        default:
            return [
                { id: 'populasi_total', label: 'Total Populasi' },
                { id: 'sapi_potong', label: 'Sapi Potong' },
                { id: 'sapi_perah', label: 'Sapi Perah' },
                { id: 'kambing', label: 'Kambing' },
                { id: 'domba', label: 'Domba' },
                { id: 'ayam_potong', label: 'Ayam Potong' },
            ];
    }
});

const selectedFilter = ref<string>('populasi_total');

watch(activeMode, (newMode) => {
    switch (newMode) {
        case 'card-penyakit':
            selectedFilter.value = 'total_wabah';
            break;
        case 'card-produksi':
            selectedFilter.value = 'total_produksi';
            break;
        case 'card-fasilitas':
            selectedFilter.value = 'poskeswan';
            break;
        case 'card-populasi':
        default:
            selectedFilter.value = 'populasi_total';
            break;
    }
    updateMap();
});

interface LegendItem {
    label: string;
    color: string;
}

const legendTitle = computed(() => {
    switch (activeMode.value) {
        case 'card-penyakit':
            return 'Status Zonasi';
        case 'card-fasilitas':
            return 'Status Fasilitas';
        default:
            return 'Tingkat Sebaran';
    }
});

const legendItems = computed<LegendItem[]>(() => {
    switch (activeMode.value) {
        case 'card-penyakit':
            return [
                { label: 'Bahaya (>=6)', color: '#DC625F' },
                { label: 'Waspada (1-5)', color: '#E8B84C' },
                { label: 'Aman (0 kasus)', color: '#00D084' },
            ];
        case 'card-fasilitas':
            return [
                { label: 'Lengkap (>=3)', color: '#00D084' },
                { label: 'Sedang (2 unit)', color: '#32B9D0' },
                { label: 'Dasar (1 unit)', color: '#86A65A' },
                { label: 'Belum Ada (0)', color: '#6E776D' },
            ];
        case 'card-produksi':
        case 'card-populasi':
        default:
            return [
                { label: 'Sangat Tinggi', color: '#D9784D' },
                { label: 'Tinggi', color: '#D4A34E' },
                { label: 'Sedang', color: '#86A65A' },
                { label: 'Rendah', color: '#38A69A' },
                { label: 'Sangat Rendah', color: '#3B82A0' },
            ];
    }
});

const CHOROPLETH_5_CLASSES: Record<number, string> = COLOR_CLASSES || {
    1: '#3B82A0', // Sangat Rendah
    2: '#38A69A', // Rendah
    3: '#86A65A', // Sedang
    4: '#D4A34E', // Tinggi
    5: '#D9784D', // Sangat Tinggi
};

const activeKecamatanList = computed<KecamatanData[]>(() => {
    if (props.kecamatanList && props.kecamatanList.length > 0) {
        return props.kecamatanList;
    }
    return KECAMATAN_LIST;
});

const kecDataLookup = computed(() => {
    const mapObj = new Map<string, KecamatanData>();
    activeKecamatanList.value.forEach((k) => {
        mapObj.set(k.nama.toLowerCase(), k);
    });
    return mapObj;
});

function getKecamatanDataFromFeature(feature: any): KecamatanData | undefined {
    const rawName = feature?.properties?.kecamatan || feature?.properties?.nama || '';
    const cleanName = rawName.replace(/^kecamatan\s+/i, '').trim().toLowerCase();
    return kecDataLookup.value.get(cleanName) || kecDataLookup.value.get(rawName.toLowerCase());
}

function getMetricValue(kec: KecamatanData): number {
    const filter = selectedFilter.value;
    if (activeMode.value === 'card-penyakit') {
        if (filter === 'pmk') return kec.pmk || 0;
        if (filter === 'lsd') return kec.lsd || 0;
        return (kec.pmk || 0) + (kec.lsd || 0);
    }
    if (activeMode.value === 'card-produksi') {
        if (filter === 'daging_ayam') return kec.daging_ayam || 0;
        if (filter === 'daging_sapi') return kec.daging_sapi || 0;
        if (filter === 'susu_sapi') return kec.susu_sapi || 0;
        if (filter === 'telur_ayam') return kec.telur_ayam || 0;
        return (
            (kec.telur_ayam || 0) +
            (kec.daging_ayam || 0) +
            (kec.daging_sapi || 0) +
            (kec.daging_kambing || 0) +
            (kec.daging_domba || 0) +
            (kec.susu_sapi || 0) +
            (kec.susu_kambing || 0) +
            (kec.susu_domba || 0)
        );
    }
    if (activeMode.value === 'card-fasilitas') {
        return kec.poskeswan || 0;
    }
    // card-populasi
    if (filter === 'populasi_total') {
        return (
            kec.populasi_total ??
            (kec.ayam_potong + kec.ayam_petelur + kec.kambing + kec.domba + kec.sapi_potong + kec.sapi_perah)
        );
    }
    return Number(kec[filter as keyof KecamatanData]) || 0;
}

const quantiles = computed<number[]>(() => {
    if (activeMode.value === 'card-penyakit' || activeMode.value === 'card-fasilitas') {
        return [];
    }
    const values = activeKecamatanList.value
        .map(k => getMetricValue(k))
        .filter(v => typeof v === 'number' && !isNaN(v))
        .sort((a, b) => a - b);

    if (values.length === 0) return [0, 0, 0, 0];
    return [
        values[Math.floor(values.length * 0.2)] ?? 0,
        values[Math.floor(values.length * 0.4)] ?? 0,
        values[Math.floor(values.length * 0.6)] ?? 0,
        values[Math.floor(values.length * 0.8)] ?? 0,
    ];
});

function getQuantileLevel(val: number, q: number[]): number {
    if (q.length < 4) return 1;
    if (val > q[3]) return 5;
    if (val > q[2]) return 4;
    if (val > q[1]) return 3;
    if (val > q[0]) return 2;
    return 1;
}

function getFeatureStyle(feature: any) {
    const kec = getKecamatanDataFromFeature(feature);
    let fillColor = '#00B98B';

    if (kec) {
        if (activeMode.value === 'card-penyakit') {
            const total = (kec.pmk || 0) + (kec.lsd || 0);
            if (kec.zona === 'bahaya' || total >= 6) {
                fillColor = '#DC625F';
            } else if (kec.zona === 'waspada' || total >= 1) {
                fillColor = '#E8B84C';
            } else {
                fillColor = '#00D084';
            }
        } else if (activeMode.value === 'card-fasilitas') {
            const val = kec.poskeswan || 0;
            if (val >= 3) fillColor = '#00D084';
            else if (val === 2) fillColor = '#32B9D0';
            else if (val === 1) fillColor = '#86A65A';
            else fillColor = '#444C43';
        } else {
            const val = getMetricValue(kec);
            const lvl = getQuantileLevel(val, quantiles.value);
            fillColor = CHOROPLETH_5_CLASSES[lvl] ?? '#00B98B';
        }
    }

    return {
        fillColor,
        fillOpacity: 0.55,
        color: '#00B98B',
        weight: 1.5,
        opacity: 0.9,
    };
}

function getTooltipContent(feature: any): string {
    const kec = getKecamatanDataFromFeature(feature);
    if (!kec) return '';

    let detailHtml = '';
    if (activeMode.value === 'card-penyakit') {
        const total = (kec.pmk || 0) + (kec.lsd || 0);
        const color = total >= 6 ? '#DC625F' : total >= 1 ? '#E8B84C' : '#00D084';
        const zonaLabel = total >= 6 ? 'Bahaya' : total >= 1 ? 'Waspada' : 'Aman';
        detailHtml = `
            <div style="font-size:11px;color:${color};font-weight:600;margin-top:2px;">
                PMK: ${kec.pmk} · LSD: ${kec.lsd} (Zona ${zonaLabel})
            </div>
        `;
    } else if (activeMode.value === 'card-fasilitas') {
        detailHtml = `
            <div style="font-size:11px;color:#32B9D0;font-weight:600;margin-top:2px;">
                ${kec.poskeswan || 0} unit fasilitas
            </div>
        `;
    } else if (activeMode.value === 'card-produksi') {
        const val = getMetricValue(kec);
        detailHtml = `
            <div style="font-size:11px;color:#39D6C0;font-weight:600;margin-top:2px;">
                ${formatAngka(val)} kg/lt
            </div>
        `;
    } else {
        const val = getMetricValue(kec);
        detailHtml = `
            <div style="font-size:11px;color:#39D6C0;font-weight:600;margin-top:2px;">
                ${formatAngka(val)} ekor
            </div>
        `;
    }

    return `
        <div style="font-family:'Inter',sans-serif;font-size:12px;font-weight:700;color:#E8F0F6;text-align:center;">
            ${kec.nama}
            ${detailHtml}
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

function updateMap() {
    if (!geojsonLayer) return;
    geojsonLayer.setStyle(getFeatureStyle);
    geojsonLayer.eachLayer((layer: any) => {
        if (layer.feature) {
            layer.setTooltipContent(getTooltipContent(layer.feature));
        }
    });
}

watch(selectedFilter, () => {
    updateMap();
});

watch(() => props.selectedCardId, () => {
    updateMap();
});

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
                        {{ mapTitle }}
                    </h3>
                    <p class="text-xs dark:text-[#858585] text-[#737B71] mt-0.5">
                        {{ mapSubtitle }}
                    </p>
                </div>
            </div>

            <!-- Filter Select Dropdown -->
            <div class="relative shrink-0">
                <select
                    v-model="selectedFilter"
                    class="appearance-none dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-lg px-2.5 py-1.5 pr-7 text-xs dark:text-[#F5F5F5] text-[#171B17] focus:outline-none focus:border-[#00D084] transition-colors cursor-pointer"
                >
                    <option
                        v-for="opt in filterOptions"
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

            <!-- Right side: Legend -->
            <div class="flex flex-col gap-2 shrink-0 dark:bg-[#181818] bg-[#F4F5F2] p-2.5 rounded-lg border dark:border-[#1E1E1E] border-[#E0E4DD]">
                <div class="text-[10px] font-bold uppercase tracking-wider dark:text-[#858585] text-[#737B71] mb-0.5">
                    {{ legendTitle }}
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
