<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import banyumasKecamatanGeoJson from '../../data/banyumas_kecamatan_web.json';
import banyumasKabupatenGeoJson from '../../data/banyumas_kabupaten_web.json';
import banyumasDesaGeoJson from '../../data/banyumas_desa_web.json';
import { KECAMATAN_LIST, KecamatanData, MetricDefinition, getClassLevel, COLOR_CLASSES, formatAngka } from '../../data/banyumasData';
import { BANYUMAS_DESA_LIST, getDesaByKecamatan, getDesaById, DesaData } from '../../data/banyumasDesaData';
import { Plus, Minus, Maximize2, Compass, Layers, Info, X, Search, Crosshair, MapPin } from 'lucide-vue-next';

const props = defineProps<{
    selectedMetric: MetricDefinition;
    quantiles: number[];
    currentRole: 'publik' | 'dinas' | 'admin';
    selectedKecamatanId?: string;
}>();

const emit = defineEmits<{
    (e: 'selectKecamatan', id: string): void;
    (e: 'selectDesa', desa: DesaData): void;
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let geojsonLayer: L.GeoJSON | null = null;
let outerCountyLayer: L.GeoJSON | null = null;
let desaGeoJsonLayer: L.GeoJSON | null = null;
let diseaseMarkersLayer: L.LayerGroup | null = null;
let currentTileLayer: L.TileLayer | null = null;
let currentReferenceLayer: L.TileLayer | null = null;

// Layer Desa
const showDesaLayer = ref(true);
const selectedDesaId = ref<string | null>(null);

// Pilihan Layer Basemap Google Maps & Tema Gelap
const currentBasemap = ref<'google_hybrid' | 'google_road' | 'google_terrain' | 'dark'>('google_hybrid');
const showBasemapMenu = ref(false);
const showDataSource = ref(false);
const liveCoords = ref({ bt: '109,2150° BT', ls: '7,4560° LS' });

// Google Maps Search Bar (Cari Kecamatan & Desa)
const searchQuery = ref('');
const isSearchFocused = ref(false);

const searchResults = computed(() => {
    if (!searchQuery.value.trim()) return [];
    const q = searchQuery.value.toLowerCase().trim();

    const kecResults = KECAMATAN_LIST.filter(k => 
        k.nama.toLowerCase().includes(q) || k.kode_kec.includes(q)
    ).map(k => ({
        id: k.id,
        type: 'kecamatan' as const,
        title: `Kecamatan ${k.nama}`,
        subtitle: `Kode ${k.kode_kec} · ${getDesaByKecamatan(k.id).length} Desa/Kelurahan`,
        lat: k.lat,
        lng: k.lng,
        kecamatanId: k.id,
        data: k
    }));

    const desaResults = BANYUMAS_DESA_LIST.filter(d =>
        d.nama.toLowerCase().includes(q) || d.kode_kd.includes(q)
    ).slice(0, 8).map(d => ({
        id: d.id,
        type: 'desa' as const,
        title: `${d.jenis} ${d.nama}`,
        subtitle: `Kec. ${d.kecamatan_nama} · ${formatAngka(Number(d[props.selectedMetric.id as keyof DesaData] || 0))} ${props.selectedMetric.satuan}`,
        lat: d.lat,
        lng: d.lng,
        kecamatanId: d.kecamatan_id,
        data: d
    }));

    return [...kecResults, ...desaResults].slice(0, 8);
});

const basemapConfigs: Record<string, { label: string; url: string; subdomains: string; desc: string }> = {
    google_hybrid: {
        label: 'Google Satelit Asli',
        url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        subdomains: '0123',
        desc: 'Citra satelit asli resolusi tinggi + nama jalan & toponimi Banyumas'
    },
    google_road: {
        label: 'Google Maps Standar',
        url: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
        subdomains: '0123',
        desc: 'Peta jalan, perumahan, jembatan, dan batas administrasi resmi'
    },
    google_terrain: {
        label: 'Google Topografi / Medan',
        url: 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
        subdomains: '0123',
        desc: 'Relief kontur Gunung Slamet dan perbukitan Banyumas'
    },
    dark: {
        label: 'Tema Gelap (Buku Stiker)',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}',
        subdomains: '',
        desc: 'Palet gelap kontras tinggi Esri Canvas resmi tanpa watermark'
    }
};

// Map lookup data atribut berdasarkan nama kecamatan
const kecDataLookup = computed(() => {
    const mapObj = new Map<string, KecamatanData>();
    KECAMATAN_LIST.forEach(k => {
        mapObj.set(k.nama.toLowerCase(), k);
    });
    return mapObj;
});

// Kecamatan Peringkat 1 untuk Stiker Sorotan (Simetris & Dinamis)
const topRankKecamatan = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const sorted = [...KECAMATAN_LIST].sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0));
    return sorted[0];
});

const topRankValueFormatted = computed(() => {
    if (!topRankKecamatan.value) return '';
    const key = props.selectedMetric.id as keyof KecamatanData;
    const val = Number(topRankKecamatan.value[key]) || 0;
    const unit = props.selectedMetric.satuan || (props.selectedMetric.kategori === 'Penyakit' ? 'kasus' : 'ekor');
    return `${formatAngka(val)} ${unit}`;
});

function getKecamatanDataFromFeature(feature: any): KecamatanData | undefined {
    const name = feature?.properties?.kecamatan || '';
    return kecDataLookup.value.get(name.toLowerCase());
}

// Menentukan style poligon GeoJSON resmi Kecamatan
function getFeatureStyle(feature: any) {
    const kec = getKecamatanDataFromFeature(feature);
    const isSelected = kec && props.selectedKecamatanId === kec.id;

    let fillColor = '#243034';
    if (kec && props.selectedMetric.kategori !== 'Penyakit') {
        const val = Number(kec[props.selectedMetric.id as keyof KecamatanData]) || 0;
        const lvl = getClassLevel(val, props.quantiles);
        fillColor = COLOR_CLASSES[lvl] || '#14535E';
    }

    const isSatellite = currentBasemap.value === 'google_hybrid' || currentBasemap.value === 'google_terrain';

    return {
        fillColor: fillColor,
        fillOpacity: props.selectedMetric.kategori === 'Penyakit' ? 0.35 : (isSatellite ? 0.55 : (isSelected ? 0.85 : 0.75)),
        color: isSelected ? '#39FF14' : (isSatellite ? '#FFFFFF' : '#0A0D0E'),
        weight: isSelected ? 4 : 2,
        opacity: 0.95
    };
}

function initMap() {
    if (!mapContainer.value) return;

    // Inisialisasi Leaflet dengan preferCanvas: true sesuai Bab 12.1
    map = L.map(mapContainer.value, {
        preferCanvas: true,
        zoomControl: false,
        attributionControl: false,
        center: [-7.4560, 109.2150],
        zoom: 11,
        minZoom: 9,
        maxZoom: 18,
        wheelDebounceTime: 40,
        wheelPxPerZoomLevel: 80
    });

    // Basemap Layer Default
    applyBasemap(currentBasemap.value);

    // Track Cursor Coordinates (Google Maps style)
    map.on('mousemove', (e: L.LeafletMouseEvent) => {
        const bt = e.latlng.lng.toFixed(4).replace('.', ',');
        const ls = Math.abs(e.latlng.lat).toFixed(4).replace('.', ',');
        liveCoords.value = { bt: `${bt}° BT`, ls: `${ls}° LS` };
    });

    map.on('zoomend', () => {
        renderDesaGeoJson();
    });

    renderOfficialGeoJson();
    renderDesaGeoJson();
    updateDiseaseMarkers();
}

function applyBasemap(type: 'google_hybrid' | 'google_road' | 'google_terrain' | 'dark') {
    if (!map) return;
    currentBasemap.value = type;

    if (currentTileLayer) {
        map.removeLayer(currentTileLayer);
        currentTileLayer = null;
    }
    if (currentReferenceLayer) {
        map.removeLayer(currentReferenceLayer);
        currentReferenceLayer = null;
    }

    const cfg = basemapConfigs[type];
    currentTileLayer = L.tileLayer(cfg.url, {
        maxZoom: 20,
        subdomains: cfg.subdomains
    }).addTo(map);

    // Untuk Tema Gelap, tambahkan layer toponimi/jalan resmi Esri Canvas tanpa watermark
    if (type === 'dark') {
        currentReferenceLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Reference/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 20
        }).addTo(map);
    }

    showBasemapMenu.value = false;

    // Refresh polygon style
    if (geojsonLayer) {
        geojsonLayer.setStyle(getFeatureStyle);
    }
}

function renderOfficialGeoJson() {
    if (!map) return;

    // Bersihkan layer sebelumnya jika ada
    if (geojsonLayer) {
        map.removeLayer(geojsonLayer);
    }
    if (outerCountyLayer) {
        map.removeLayer(outerCountyLayer);
    }

    // 1. Layer Batas Kabupaten Terluar (masking garis batas luar)
    outerCountyLayer = L.geoJSON(banyumasKabupatenGeoJson as any, {
        style: {
            fill: false,
            color: '#19F5D4',
            weight: 3,
            opacity: 0.9,
            dashArray: '4, 4'
        },
        interactive: false
    }).addTo(map);

    // 2. Layer 27 Kecamatan Resmi dari banyumas_kecamatan_web.geojson
    geojsonLayer = L.geoJSON(banyumasKecamatanGeoJson as any, {
        style: getFeatureStyle,
        onEachFeature: (feature, layer) => {
            const kec = getKecamatanDataFromFeature(feature);
            if (!kec) return;

            const metricVal = kec[props.selectedMetric.id as keyof KecamatanData];
            const desaCount = getDesaByKecamatan(kec.id).length;
            const valText = props.currentRole === 'publik'
                ? `<div style="font-size:11px;color:#19F5D4;font-weight:600">${desaCount} Desa / Kelurahan</div>`
                : `<div style="font-size:11px;color:#19F5D4;font-weight:700">${formatAngka(Number(metricVal))} ${props.selectedMetric.satuan} <span style="font-size:10px;color:#8FA1A0;">(${desaCount} Desa)</span></div>`;

            layer.bindTooltip(`
                <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:800;color:#F4F7F5;text-align:center;">
                    ${kec.nama}
                    ${valText}
                </div>
            `, {
                permanent: false,
                direction: 'center',
                className: 'custom-leaflet-tooltip'
            });

            // Hover and Click Events
            layer.on('mouseover', () => {
                if (props.selectedKecamatanId !== kec.id) {
                    (layer as L.Path).setStyle({ weight: 3.5, color: '#39FF14' });
                }
            });

            layer.on('mouseout', () => {
                if (props.selectedKecamatanId !== kec.id) {
                    (layer as L.Path).setStyle(getFeatureStyle(feature));
                }
            });

            layer.on('click', () => {
                focusKecamatan(kec);
            });
        }
    }).addTo(map);
}

// 3. Layer Batas & Data 331 Desa/Kelurahan
function renderDesaGeoJson() {
    if (!map) return;

    if (desaGeoJsonLayer) {
        map.removeLayer(desaGeoJsonLayer);
        desaGeoJsonLayer = null;
    }

    if (!showDesaLayer.value) return;

    const activeKec = props.selectedKecamatanId;
    const currentZoom = map.getZoom();

    // Filter feature: jika ada kecamatan yang dipilih, tampilkan desanya;
    // jika tidak ada, tampilkan semua desa jika zoom >= 12 atau showDesaLayer aktif
    const filterFn = (feature: any) => {
        if (activeKec) {
            return feature.properties.kecamatan_id === activeKec;
        }
        return currentZoom >= 12 || showDesaLayer.value;
    };

    desaGeoJsonLayer = L.geoJSON(banyumasDesaGeoJson as any, {
        filter: filterFn,
        style: (feature) => {
            const isSelectedDesa = selectedDesaId.value === feature?.properties?.id;
            return {
                fillColor: isSelectedDesa ? '#39FF14' : '#19F5D4',
                fillOpacity: isSelectedDesa ? 0.45 : (activeKec ? 0.22 : 0.10),
                color: isSelectedDesa ? '#39FF14' : '#19F5D4',
                weight: isSelectedDesa ? 3.5 : (activeKec ? 2 : 1.2),
                dashArray: isSelectedDesa ? undefined : '3, 4',
                opacity: 0.95
            };
        },
        onEachFeature: (feature, layer) => {
            const d = getDesaById(feature.properties.id);
            if (!d) return;

            const metricVal = d[props.selectedMetric.id as keyof DesaData] || 0;
            const valFormatted = formatAngka(Number(metricVal));

            layer.bindTooltip(`
                <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:800;color:#F4F7F5;text-align:center;padding:2px 4px;">
                    <div style="color:#F4F7F5;">${d.jenis} ${d.nama}</div>
                    <div style="font-size:10px;font-weight:600;color:#8FA1A0;">Kec. ${d.kecamatan_nama} (${d.kode_kd})</div>
                    <div style="font-size:11px;font-weight:800;color:#39FF14;margin-top:2px;">
                        ${valFormatted} ${props.selectedMetric.satuan}
                    </div>
                </div>
            `, {
                permanent: false,
                direction: 'center',
                className: 'custom-desa-tooltip'
            });

            layer.on('mouseover', () => {
                if (selectedDesaId.value !== d.id) {
                    (layer as L.Path).setStyle({
                        weight: 3,
                        color: '#39FF14',
                        fillOpacity: 0.45
                    });
                }
            });

            layer.on('mouseout', () => {
                if (selectedDesaId.value !== d.id) {
                    (layer as L.Path).setStyle({
                        fillColor: '#19F5D4',
                        fillOpacity: activeKec ? 0.22 : 0.10,
                        color: '#19F5D4',
                        weight: activeKec ? 2 : 1.2,
                        dashArray: '3, 4',
                        opacity: 0.95
                    });
                }
            });

            layer.on('click', () => {
                selectedDesaId.value = d.id;
                if (props.selectedKecamatanId !== d.kecamatan_id) {
                    emit('selectKecamatan', d.kecamatan_id);
                }
                emit('selectDesa', d);
                map?.flyTo([d.lat, d.lng], 13.5, { animate: true, duration: 0.6 });
            });
        }
    }).addTo(map);
}

function updateDiseaseMarkers() {
    if (!map) return;

    if (diseaseMarkersLayer) {
        map.removeLayer(diseaseMarkersLayer);
        diseaseMarkersLayer = null;
    }

    if (props.selectedMetric.kategori === 'Penyakit') {
        const markers: L.Layer[] = [];

        KECAMATAN_LIST.forEach(kec => {
            const count = props.selectedMetric.id === 'pmk' ? kec.pmk : kec.lsd;
            if (count > 0) {
                const radius = Math.min(8 + Math.sqrt(count) * 6, 26);

                if (props.selectedMetric.id === 'pmk') {
                    const circle = L.circleMarker([kec.lat, kec.lng], {
                        radius: radius,
                        fillColor: '#FF5C5C',
                        fillOpacity: 0.8,
                        color: '#F4F7F5',
                        weight: 2
                    });
                    circle.bindTooltip(`${kec.nama}: ${count} kasus PMK`, { className: 'custom-leaflet-tooltip' });
                    circle.on('click', () => focusKecamatan(kec));
                    markers.push(circle);
                } else {
                    const icon = L.divIcon({
                        className: 'lsd-diamond-marker',
                        html: `<div style="width:${radius*1.8}px;height:${radius*1.8}px;background:#FF5C5C;opacity:0.8;border:2px solid #F4F7F5;transform:rotate(45deg);"></div>`,
                        iconSize: [radius * 1.8, radius * 1.8],
                        iconAnchor: [radius * 0.9, radius * 0.9]
                    });
                    const marker = L.marker([kec.lat, kec.lng], { icon });
                    marker.bindTooltip(`${kec.nama}: ${count} kasus LSD`, { className: 'custom-leaflet-tooltip' });
                    marker.on('click', () => focusKecamatan(kec));
                    markers.push(marker);
                }
            }
        });

        diseaseMarkersLayer = L.layerGroup(markers).addTo(map);
    }
}

function focusKecamatan(kec: KecamatanData) {
    selectedDesaId.value = null;
    emit('selectKecamatan', kec.id);
    map?.flyTo([kec.lat, kec.lng], 12.5, { animate: true, duration: 0.8 });
    searchQuery.value = '';
    isSearchFocused.value = false;
}

function focusDesa(desa: DesaData) {
    selectedDesaId.value = desa.id;
    if (props.selectedKecamatanId !== desa.kecamatan_id) {
        emit('selectKecamatan', desa.kecamatan_id);
    }
    map?.flyTo([desa.lat, desa.lng], 13.5, { animate: true, duration: 0.8 });
}

defineExpose({
    focusKecamatan,
    focusDesa
});

function handleSelectSearchResult(item: any) {
    emit('selectKecamatan', item.kecamatanId);
    if (item.type === 'desa') {
        selectedDesaId.value = item.id;
        emit('selectDesa', item.data);
        map?.flyTo([item.lat, item.lng], 13.5, { animate: true, duration: 0.8 });
    } else {
        selectedDesaId.value = null;
        map?.flyTo([item.lat, item.lng], 12.5, { animate: true, duration: 0.8 });
    }
    searchQuery.value = '';
    isSearchFocused.value = false;
}

function zoomIn() {
    map?.zoomIn();
}

function zoomOut() {
    map?.zoomOut();
}

function recenterBanyumas() {
    selectedDesaId.value = null;
    map?.flyTo([-7.4560, 109.2150], 11, { animate: true, duration: 0.8 });
}

// Watch updates
watch(() => [props.selectedMetric, props.quantiles, props.currentRole], () => {
    if (geojsonLayer) {
        geojsonLayer.setStyle(getFeatureStyle);
    }
    renderDesaGeoJson();
    updateDiseaseMarkers();
}, { deep: true });

watch(() => props.selectedKecamatanId, (newId) => {
    if (geojsonLayer) {
        geojsonLayer.setStyle(getFeatureStyle);
    }
    renderDesaGeoJson();

    if (newId && map) {
        const target = KECAMATAN_LIST.find(k => k.id === newId);
        if (target) {
            map.flyTo([target.lat, target.lng], 12.5, { animate: true, duration: 0.8 });
        }
    }
});

onMounted(() => {
    initMap();
});

onUnmounted(() => {
    map?.remove();
});
</script>

<template>
    <div class="relative w-full h-[620px] lg:h-[720px] bg-[#0A0D0E] border-2 border-[#243034] rounded-24 overflow-hidden select-none flex flex-col font-ui">
        
        <!-- BAR PENCARIAN GOOGLE MAPS FLOATING DI ATAS PETA -->
        <div class="absolute top-4 left-4 z-20 w-72 sm:w-88">
            <div class="relative">
                <div class="h-11 bg-[#10171A] border-2 border-[#19F5D4] rounded-16 px-3 flex items-center gap-2 shadow-[3px_3px_0_#19F5D4] transition-all">
                    <Search class="w-4 h-4 text-[#19F5D4] flex-shrink-0" />
                    <input
                        v-model="searchQuery"
                        @focus="isSearchFocused = true"
                        type="text"
                        placeholder="Cari kecamatan / desa di Banyumas..."
                        class="bg-transparent text-xs text-[#F4F7F5] w-full outline-none placeholder:text-[#8FA1A0]"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="text-[#8FA1A0] hover:text-[#F4F7F5] cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Dropdown Hasil Pencarian Google Maps Style -->
                <div
                    v-if="isSearchFocused && searchResults.length > 0"
                    class="absolute top-12 left-0 right-0 bg-[#10171A] border-2 border-[#243034] rounded-16 p-1.5 shadow-[4px_4px_0_#0A0D0E] z-30 space-y-1 animate-in fade-in zoom-in-95 max-h-72 overflow-y-auto custom-popup-scroll"
                >
                    <div
                        v-for="res in searchResults"
                        :key="res.id"
                        @click="handleSelectSearchResult(res)"
                        class="px-3 py-2 rounded-12 hover:bg-[#243034] cursor-pointer flex items-center justify-between text-xs transition-colors"
                    >
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <MapPin class="w-3.5 h-3.5 shrink-0" :class="res.type === 'desa' ? 'text-[#39FF14]' : 'text-[#19F5D4]'" />
                            <div class="truncate">
                                <span class="font-bold text-[#F4F7F5]">{{ res.title }}</span>
                                <div class="text-[10px] text-[#8FA1A0] truncate">{{ res.subtitle }}</div>
                            </div>
                        </div>
                        <span
                            class="text-[9px] px-1.5 py-0.5 rounded font-mono shrink-0"
                            :class="res.type === 'desa' ? 'bg-[#39FF14]/20 text-[#39FF14]' : 'bg-[#19F5D4]/20 text-[#19F5D4]'"
                        >
                            {{ res.type === 'desa' ? 'Desa' : 'Kec' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STIKER SOROTAN DI KANAN ATAS (Simetris Sumbu Horizontal & Vertikal) -->
        <div v-if="topRankKecamatan" class="absolute top-4 right-4 z-20 hidden md:flex items-center h-11">
            <button
                type="button"
                @click="focusKecamatan(topRankKecamatan)"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full border-2 border-[#F4F7F5] font-extrabold text-xs shadow-[0_2px_8px_rgba(0,0,0,0.5)] transition-all select-none hover:scale-105 active:scale-95 cursor-pointer"
                :class="selectedMetric.kategori === 'Penyakit' ? 'bg-[#FFC53D] text-[#04110A]' : 'bg-[#39FF14] text-[#04110A]'"
                :title="`Klik untuk pusatkan peta ke Kecamatan ${topRankKecamatan.nama}`"
            >
                <span class="text-xs leading-none select-none">⭐</span>
                <span class="leading-none tracking-normal">
                    {{ selectedMetric.kategori === 'Penyakit' ? 'Zona Terkritis: ' : 'Terbanyak: ' }}
                    <span class="font-black">{{ topRankKecamatan.nama }}</span>
                    <span> ({{ topRankValueFormatted }})</span>
                </span>
            </button>
        </div>

        <!-- CONTAINER LEAFLET ENGINE (Peta Asli GeoJSON BIG/BPS + Basemap) -->
        <div ref="mapContainer" class="flex-1 w-full h-full relative z-0"></div>

        <!-- KONTROL GOOGLE MAPS DI SUDUT KANAN BAWAH -->
        <div class="absolute bottom-6 right-4 z-20 flex flex-col gap-2">
            <!-- Tombol Pusatkan ke Banyumas -->
            <button
                @click="recenterBanyumas"
                class="w-10 h-10 rounded-12 bg-[#10171A] border-2 border-[#243034] hover:border-[#19F5D4] text-[#F4F7F5] flex items-center justify-center shadow-[2px_2px_0_#0A0D0E] transition-all hover:text-[#19F5D4] cursor-pointer"
                title="Pusatkan ke Kabupaten Banyumas"
            >
                <Crosshair class="w-5 h-5 stroke-[2.5]" />
            </button>

            <!-- Tombol Zoom In & Zoom Out -->
            <div class="flex flex-col bg-[#10171A] border-2 border-[#243034] rounded-16 overflow-hidden shadow-[3px_3px_0_#0A0D0E]">
                <button
                    @click="zoomIn"
                    class="w-10 h-10 flex items-center justify-center text-[#F4F7F5] hover:bg-[#243034] hover:text-[#19F5D4] transition-colors border-b border-[#243034] cursor-pointer"
                    title="Perbesar (Zoom In)"
                >
                    <Plus class="w-5 h-5 stroke-[2.5]" />
                </button>
                <button
                    @click="zoomOut"
                    class="w-10 h-10 flex items-center justify-center text-[#F4F7F5] hover:bg-[#243034] hover:text-[#19F5D4] transition-colors cursor-pointer"
                    title="Perkecil (Zoom Out)"
                >
                    <Minus class="w-5 h-5 stroke-[2.5]" />
                </button>
            </div>
        </div>

        <!-- SELECTOR LAYER & BASEMAP STYLE (Kiri Bawah) -->
        <div class="absolute bottom-6 left-4 z-20 flex flex-wrap items-center gap-2">
            <!-- Pilihan Basemap -->
            <div class="relative">
                <button
                    @click="showBasemapMenu = !showBasemapMenu"
                    class="h-10 px-3.5 bg-[#10171A] border-2 border-[#19F5D4] text-[#F4F7F5] rounded-16 text-xs font-bold flex items-center gap-2 shadow-[3px_3px_0_#19F5D4] hover:bg-[#243034] transition-all cursor-pointer"
                >
                    <Layers class="w-4 h-4 text-[#19F5D4]" />
                    <span>{{ basemapConfigs[currentBasemap].label }}</span>
                </button>

                <!-- Menu Pilihan Layer Google Maps -->
                <div
                    v-if="showBasemapMenu"
                    class="absolute bottom-12 left-0 w-64 bg-[#10171A] border-2 border-[#19F5D4] rounded-20 p-2.5 shadow-[4px_4px_0_#19F5D4] z-30 space-y-1.5 animate-in slide-in-from-bottom-2"
                >
                    <div class="text-[11px] font-bold text-[#8FA1A0] px-2 py-1 uppercase tracking-wider">
                        Pilih Tipe Peta Asli:
                    </div>
                    <button
                        v-for="(cfg, key) in basemapConfigs"
                        :key="key"
                        @click="applyBasemap(key as any)"
                        class="w-full text-left p-2 rounded-12 flex flex-col transition-all cursor-pointer"
                        :class="currentBasemap === key ? 'bg-[#243034] border border-[#19F5D4]' : 'hover:bg-[#243034]/60'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold" :class="currentBasemap === key ? 'text-[#19F5D4]' : 'text-[#F4F7F5]'">
                                {{ cfg.label }}
                            </span>
                            <span v-if="currentBasemap === key" class="w-2 h-2 rounded-full bg-[#39FF14]"></span>
                        </div>
                        <span class="text-[10px] text-[#8FA1A0] leading-tight mt-0.5">{{ cfg.desc }}</span>
                    </button>
                </div>
            </div>

            <!-- TOGGLE LAYER DESA (331 Desa) -->
            <button
                @click="showDesaLayer = !showDesaLayer; renderDesaGeoJson();"
                class="h-10 px-3 bg-[#10171A] border-2 rounded-16 text-xs font-bold flex items-center gap-2 shadow-[3px_3px_0_#243034] transition-all cursor-pointer"
                :class="showDesaLayer ? 'border-[#39FF14] text-[#39FF14]' : 'border-[#243034] text-[#8FA1A0] hover:text-[#F4F7F5]'"
                :title="showDesaLayer ? 'Sembunyikan Batas Desa/Kelurahan' : 'Tampilkan Batas Desa/Kelurahan'"
            >
                <MapPin class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Layer Desa</span>
                <span
                    class="text-[10px] px-1.5 py-0.5 rounded-full font-mono font-bold"
                    :class="showDesaLayer ? 'bg-[#39FF14] text-[#04110A]' : 'bg-[#243034] text-[#8FA1A0]'"
                >
                    {{ selectedKecamatanId ? `${getDesaByKecamatan(selectedKecamatanId).length} Desa` : '331 Desa' }}
                </span>
            </button>

            <!-- Koordinat GPS Live -->
            <div class="hidden lg:flex items-center gap-1.5 bg-[#10171A]/90 border border-[#243034] px-3 h-10 rounded-16 text-[11px] font-mono text-[#8FA1A0]">
                <Compass class="w-3.5 h-3.5 text-[#19F5D4]" />
                <span class="text-[#F4F7F5]">{{ liveCoords.bt }}, {{ liveCoords.ls }}</span>
            </div>
        </div>
    </div>
</template>

<style>
/* Tooltip Popover Sesuai Aturan Desain */
.custom-leaflet-tooltip {
    background-color: #10171A !important;
    border: 2px solid #19F5D4 !important;
    border-radius: 12px !important;
    box-shadow: 3px 3px 0 #243034 !important;
    padding: 6px 12px !important;
}
.custom-leaflet-tooltip::before {
    display: none !important;
}

.custom-desa-tooltip {
    background-color: #0A0D0E !important;
    border: 2px solid #39FF14 !important;
    border-radius: 10px !important;
    box-shadow: 2px 2px 0 #141C1E !important;
    padding: 4px 8px !important;
}
.custom-desa-tooltip::before {
    display: none !important;
}

.leaflet-container {
    background-color: #0A0D0E !important;
}
</style>
