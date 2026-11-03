<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import banyumasKecamatanGeoJson from '../../data/banyumas_kecamatan_web.json';
import banyumasKabupatenGeoJson from '../../data/banyumas_kabupaten_web.json';
import banyumasDesaGeoJson from '../../data/banyumas_desa_web.json';
import {
    KECAMATAN_LIST,
    KecamatanData,
    MetricDefinition,
    getClassLevel,
    COLOR_CLASSES,
    CHOROPLETH_DARK_CLASSES,
    formatAngka,
    GIS_TOKENS,
} from '../../data/banyumasData';
import { BANYUMAS_DESA_LIST, getDesaByKecamatan, getDesaById, getDesaStatus, DesaData } from '../../data/banyumasDesaData';
import { Plus, Minus, Compass, Layers, X, Search, Crosshair, MapPin } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        selectedMetric: MetricDefinition;
        quantiles: number[];
        currentRole: 'publik' | 'dinas' | 'admin';
        selectedKecamatanId?: string;
        layerStates?: Record<string, boolean>;
        mapEngine?: 'real' | 'vector';
    }>(),
    {
        layerStates: () => ({}),
        mapEngine: 'real',
    }
);

const emit = defineEmits<{
    (e: 'selectKecamatan', id: string): void;
    (e: 'selectDesa', desa: DesaData): void;
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let geojsonLayer: L.GeoJSON | null = null;
let outerCountyLayer: L.GeoJSON | null = null;
let desaGeoJsonLayer: L.GeoJSON | null = null;
let heatmapLayer: L.LayerGroup | null = null;
let facilitiesLayer: L.LayerGroup | null = null;
let transportLayer: L.TileLayer | null = null;
let diseaseMarkersLayer: L.LayerGroup | null = null;
let selectedBadgeMarker: L.Marker | null = null;
let currentTileLayer: L.TileLayer | null = null;
let currentReferenceLayer: L.TileLayer | null = null;

// Layer Desa
const showDesaLayer = ref(false);
const selectedDesaId = ref<string | null>(null);

// Pilihan Basemap: Default Satellite Imagery
const currentBasemap = ref<'satellite' | 'google_hybrid' | 'dark'>('satellite');
const showBasemapMenu = ref(false);

// Live Coordinates state
const formattedCoordinates = ref('7.4560° S  109.2150° E | EPSG:4326');
let isMouseOverMap = false;

// Dynamic Scale Bar state (Defaults to "0 5 10 15 km" at Banyumas overview)
const scaleValues = ref({
    mid1: '5',
    mid2: '10',
    total: '15 km',
});

// Floating Search Bar State
const searchQuery = ref('');
const isSearchFocused = ref(false);

const searchResults = computed(() => {
    if (!searchQuery.value.trim()) return [];
    const q = searchQuery.value.toLowerCase().trim();

    const kecResults = KECAMATAN_LIST.filter(
        (k) => k.nama.toLowerCase().includes(q) || k.kode_kec.includes(q)
    ).map((k) => ({
        id: k.id,
        type: 'kecamatan' as const,
        title: `Kecamatan ${k.nama}`,
        subtitle: `Kode ${k.kode_kec} · ${getDesaByKecamatan(k.id).length} Desa/Kelurahan`,
        lat: k.lat,
        lng: k.lng,
        kecamatanId: k.id,
        data: k,
    }));

    const desaResults = BANYUMAS_DESA_LIST.filter(
        (d) => d.nama.toLowerCase().includes(q) || d.kode_kd.includes(q)
    )
        .slice(0, 10)
        .map((d) => ({
            id: d.id,
            type: 'desa' as const,
            title: `${d.jenis} ${d.nama}`,
            subtitle:
                props.currentRole === 'publik'
                    ? `Kec. ${d.kecamatan_nama} (${d.kode_kd})`
                    : `Kec. ${d.kecamatan_nama} · ${formatAngka(Number(d[props.selectedMetric.id as keyof DesaData] || 0))} ${props.selectedMetric.satuan}`,
            lat: d.lat,
            lng: d.lng,
            kecamatanId: d.kecamatan_id,
            data: d,
        }));

    return [...kecResults, ...desaResults].slice(0, 10);
});

const basemapConfigs: Record<
    'satellite' | 'google_hybrid' | 'dark',
    { label: string; url: string; subdomains: string; referenceUrl?: string; desc: string }
> = {
    satellite: {
        label: 'Citra Satelit Asli',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        subdomains: '',
        referenceUrl:
            'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        desc: 'Citra satelit resolusi tinggi Esri World Imagery',
    },
    google_hybrid: {
        label: 'Google Satelit Hybrid',
        url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        subdomains: '0123',
        desc: 'Citra satelit asli resolusi tinggi + nama jalan & toponimi',
    },
    dark: {
        label: 'Vektor Gelap Esri Canvas',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}',
        subdomains: '',
        referenceUrl:
            'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Reference/MapServer/tile/{z}/{y}/{x}',
        desc: 'Palet gelap kontras tinggi Esri Canvas Dark Gray',
    },
};

// Map lookup data atribut berdasarkan nama kecamatan
const kecDataLookup = computed(() => {
    const mapObj = new Map<string, KecamatanData>();
    KECAMATAN_LIST.forEach((k) => {
        mapObj.set(k.nama.toLowerCase(), k);
    });
    return mapObj;
});

// Kecamatan Peringkat 1 untuk Stiker Sorotan
const topRankKecamatan = computed(() => {
    const key = props.selectedMetric.id as keyof KecamatanData;
    const sorted = [...KECAMATAN_LIST].sort((a, b) => Number(b[key] || 0) - Number(a[key] || 0));
    return sorted[0];
});

const topRankValueFormatted = computed(() => {
    if (!topRankKecamatan.value) return '';
    if (props.currentRole === 'publik') return 'Sentra Unggulan';
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
    const isSelected = Boolean(kec && props.selectedKecamatanId === kec.id);

    let fillColor: string = GIS_TOKENS.surface;
    if (kec && props.selectedMetric.kategori !== 'Penyakit') {
        const val = Number(kec[props.selectedMetric.id as keyof KecamatanData]) || 0;
        const lvl = getClassLevel(val, props.quantiles);
        fillColor = CHOROPLETH_DARK_CLASSES[lvl] || COLOR_CLASSES[lvl] || GIS_TOKENS.primary;
    } else if (kec && props.selectedMetric.kategori === 'Penyakit') {
        if (kec.zona === 'bahaya') fillColor = '#DC625F';
        else if (kec.zona === 'waspada') fillColor = '#E8B84C';
        else fillColor = '#20B88A';
    }

    const isSatellite = currentBasemap.value === 'satellite' || currentBasemap.value === 'google_hybrid';

    // Semi-transparent choropleth fills so satellite terrain shows through subtly
    const fillOpacity =
        props.selectedMetric.kategori === 'Penyakit'
            ? 0.35
            : isSelected
              ? 0.65
              : isSatellite
                ? 0.45
                : 0.6;

    let strokeColor = isSelected ? '#00D084' : isSatellite ? 'rgba(255, 255, 255, 0.45)' : GIS_TOKENS.border;
    if (props.selectedMetric.kategori === 'Penyakit' && !isSelected && kec) {
        if (kec.zona === 'bahaya') strokeColor = '#DC625F';
        else if (kec.zona === 'waspada') strokeColor = '#E8B84C';
        else strokeColor = '#20B88A';
    }

    return {
        fillColor: fillColor,
        fillOpacity: fillOpacity,
        // Selected polygon highlighted with Electric Emerald outline (#00D084, weight 3.5)
        color: strokeColor,
        weight: isSelected ? 3.5 : 1.5,
        opacity: isSelected ? 1 : 0.85,
    };
}

function formatCoordinates(lat: number, lng: number): string {
    const latDir = lat >= 0 ? 'N' : 'S';
    const lngDir = lng >= 0 ? 'E' : 'W';
    const latStr = Math.abs(lat).toFixed(4);
    const lngStr = Math.abs(lng).toFixed(4);
    return `${latStr}° ${latDir}  ${lngStr}° ${lngDir} | EPSG:4326`;
}

function updateLiveCoordinatesFromCenter() {
    if (!map) return;
    const center = map.getCenter();
    formattedCoordinates.value = formatCoordinates(center.lat, center.lng);
}

function updateScale() {
    if (!map) return;
    const center = map.getCenter();
    const zoom = map.getZoom();

    const metersPerPixel = (156543.03392 * Math.cos((center.lat * Math.PI) / 180)) / Math.pow(2, zoom);
    const barMeters = metersPerPixel * 128;

    if (barMeters >= 1000) {
        const totalKm = Math.round(barMeters / 1000);
        const cleanTotal =
            totalKm <= 3 ? 3 : totalKm <= 6 ? 6 : totalKm <= 12 ? 10 : totalKm <= 18 ? 15 : Math.round(totalKm / 5) * 5;
        const step = cleanTotal / 3;
        scaleValues.value = {
            mid1: step.toFixed(step % 1 === 0 ? 0 : 1),
            mid2: (step * 2).toFixed((step * 2) % 1 === 0 ? 0 : 1),
            total: `${cleanTotal} km`,
        };
    } else {
        const totalM = Math.max(300, Math.round(barMeters / 100) * 100);
        const step = totalM / 3;
        scaleValues.value = {
            mid1: `${Math.round(step)}`,
            mid2: `${Math.round(step * 2)}`,
            total: `${totalM} m`,
        };
    }
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
        center: [-7.456, 109.215],
        zoom: 11,
        minZoom: 9,
        maxZoom: 18,
        wheelDebounceTime: 40,
        wheelPxPerZoomLevel: 80,
    });

    const initialBasemap = props.mapEngine === 'vector' ? 'dark' : 'satellite';
    applyBasemap(initialBasemap);

    updateScale();
    updateLiveCoordinatesFromCenter();

    map.on('mousemove', (e: L.LeafletMouseEvent) => {
        isMouseOverMap = true;
        formattedCoordinates.value = formatCoordinates(e.latlng.lat, e.latlng.lng);
    });

    map.on('mouseout', () => {
        isMouseOverMap = false;
        updateLiveCoordinatesFromCenter();
    });

    map.on('moveend', () => {
        if (!isMouseOverMap) {
            updateLiveCoordinatesFromCenter();
        }
        updateScale();
    });

    map.on('zoomend', () => {
        updateScale();
        syncDesaLayer();
    });

    syncOuterCountyLayer();
    syncKecamatanLayer();
    syncDesaLayer();
    syncDiseaseMarkers();
    syncHeatmapLayer();
    syncFacilitiesLayer();
    syncTransportLayer();
    syncSelectedBadge();
}

function applyBasemap(type: 'satellite' | 'google_hybrid' | 'dark') {
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
        subdomains: cfg.subdomains,
    }).addTo(map);

    if (cfg.referenceUrl) {
        currentReferenceLayer = L.tileLayer(cfg.referenceUrl, {
            maxZoom: 20,
            opacity: 0.9,
        }).addTo(map);
    }

    showBasemapMenu.value = false;

    if (geojsonLayer) {
        geojsonLayer.setStyle(getFeatureStyle);
    }
}

// 1. Layer Batas Kabupaten Terluar (Prop layerStates.batasKabupaten)
function syncOuterCountyLayer() {
    if (!map) return;
    const isVisible = props.layerStates?.batasKabupaten !== false;

    if (isVisible) {
        if (!outerCountyLayer) {
            outerCountyLayer = L.geoJSON(banyumasKabupatenGeoJson as any, {
                style: {
                    fill: false,
                    color: '#39D6C0',
                    weight: 2.5,
                    opacity: 0.9,
                    dashArray: '4, 4',
                },
                interactive: false,
            });
        }
        if (!map.hasLayer(outerCountyLayer)) {
            outerCountyLayer.addTo(map);
        }
    } else {
        if (outerCountyLayer && map.hasLayer(outerCountyLayer)) {
            map.removeLayer(outerCountyLayer);
        }
    }
}

// 2. Layer 27 Kecamatan Resmi (Prop layerStates.batasKecamatan)
function syncKecamatanLayer() {
    if (!map) return;
    const isVisible = props.layerStates?.batasKecamatan !== false;

    if (isVisible) {
        if (!geojsonLayer) {
            renderOfficialGeoJson();
        } else if (!map.hasLayer(geojsonLayer)) {
            geojsonLayer.addTo(map);
        }
        if (geojsonLayer) {
            geojsonLayer.setStyle(getFeatureStyle);
        }
    } else {
        if (geojsonLayer && map.hasLayer(geojsonLayer)) {
            map.removeLayer(geojsonLayer);
        }
    }
}

function renderOfficialGeoJson() {
    if (!map) return;

    if (geojsonLayer) {
        map.removeLayer(geojsonLayer);
    }

    geojsonLayer = L.geoJSON(banyumasKecamatanGeoJson as any, {
        style: getFeatureStyle,
        onEachFeature: (feature, layer) => {
            const kec = getKecamatanDataFromFeature(feature);
            if (!kec) return;

            const metricVal = kec[props.selectedMetric.id as keyof KecamatanData];
            const desaCount = getDesaByKecamatan(kec.id).length;
            const valText =
                props.currentRole === 'publik'
                    ? `<div style="font-size:11px;color:#39D6C0;font-weight:600;font-variant-numeric:tabular-nums;">${desaCount} Desa / Kelurahan</div>`
                    : `<div style="font-size:11px;color:#39D6C0;font-weight:700;font-variant-numeric:tabular-nums;">${formatAngka(Number(metricVal))} ${props.selectedMetric.satuan} <span style="font-size:10px;color:#A6B7C8;">(${desaCount} Desa)</span></div>`;

            layer.bindTooltip(
                `
                <div style="font-family:'Inter',sans-serif;font-size:12px;font-weight:800;color:#E8F0F6;text-align:center;">
                    ${kec.nama}
                    ${valText}
                </div>
            `,
                {
                    permanent: false,
                    direction: 'center',
                    className: 'custom-leaflet-tooltip',
                }
            );

            layer.on('mouseover', () => {
                if (props.selectedKecamatanId !== kec.id) {
                    (layer as L.Path).setStyle({ weight: 3, color: '#39D6C0' });
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
        },
    }).addTo(map);
}

// 3. Layer Batas & Data 331 Desa/Kelurahan (Prop layerStates.batasDesa)
function syncDesaLayer() {
    if (!map) return;

    const hasSelectedKecamatan = Boolean(props.selectedKecamatanId);
    const isExplicitlyDisabled = props.layerStates?.batasDesa === false;
    const isExplicitlyEnabled = props.layerStates?.batasDesa === true;
    const shouldShow =
        hasSelectedKecamatan ||
        isExplicitlyEnabled ||
        (!isExplicitlyDisabled && showDesaLayer.value);

    if (!shouldShow) {
        if (desaGeoJsonLayer && map.hasLayer(desaGeoJsonLayer)) {
            map.removeLayer(desaGeoJsonLayer);
        }
        desaGeoJsonLayer = null;
        return;
    }

    renderDesaGeoJson();
}

function getDesaPolygonStyle(featureId?: string, isSelected = false, activeKec?: string) {
    if (isSelected) {
        return {
            fillColor: '#39D6C0',
            color: '#00D084',
            weight: 3.5,
            fillOpacity: 0.55,
            dashArray: undefined,
            opacity: 0.95,
        };
    }
    const d = featureId ? getDesaById(featureId) : undefined;
    const status = d ? getDesaStatus(d) : 'aman';
    if (status === 'bahaya') {
        return {
            fillColor: '#DC625F',
            color: '#DC625F',
            weight: activeKec ? 2.2 : 1.5,
            fillOpacity: activeKec ? 0.35 : 0.2,
            dashArray: '3, 4',
            opacity: 0.95,
        };
    }
    if (status === 'waspada') {
        return {
            fillColor: '#E8B84C',
            color: '#E8B84C',
            weight: activeKec ? 2.0 : 1.4,
            fillOpacity: activeKec ? 0.35 : 0.2,
            dashArray: '3, 4',
            opacity: 0.95,
        };
    }
    return {
        fillColor: '#20B88A',
        color: '#20B88A',
        weight: activeKec ? 1.8 : 1.2,
        fillOpacity: activeKec ? 0.22 : 0.12,
        dashArray: '3, 4',
        opacity: 0.95,
    };
}

function renderDesaGeoJson() {
    if (!map) return;

    if (desaGeoJsonLayer) {
        map.removeLayer(desaGeoJsonLayer);
        desaGeoJsonLayer = null;
    }

    const activeKec = props.selectedKecamatanId;
    const currentZoom = map.getZoom();

    const filterFn = (feature: any) => {
        if (activeKec) {
            return feature.properties.kecamatan_id === activeKec;
        }
        return currentZoom >= 12 || showDesaLayer.value || props.layerStates?.batasDesa === true;
    };

    desaGeoJsonLayer = L.geoJSON(banyumasDesaGeoJson as any, {
        filter: filterFn,
        style: (feature) => {
            const isSelectedDesa = selectedDesaId.value === feature?.properties?.id;
            return getDesaPolygonStyle(feature?.properties?.id, isSelectedDesa, activeKec);
        },
        onEachFeature: (feature, layer) => {
            const d = getDesaById(feature.properties.id);
            if (!d) return;

            const status = getDesaStatus(d);
            const metricVal = d[props.selectedMetric.id as keyof DesaData] || 0;
            const valFormatted = formatAngka(Number(metricVal));
            const desaDetailText =
                props.currentRole === 'publik'
                    ? `Wilayah ${d.jenis} · Kec. ${d.kecamatan_nama}`
                    : `${valFormatted} ${props.selectedMetric.satuan}`;

            layer.bindTooltip(
                `
                <div style="font-family:'Inter',sans-serif;font-size:11px;font-weight:800;color:#F5F5F5;text-align:center;padding:2px 4px;">
                    <div style="color:#F5F5F5;">${d.jenis} ${d.nama}</div>
                    <div style="font-size:10px;font-weight:600;color:#B3B3B3;font-variant-numeric:tabular-nums;">Kec. ${d.kecamatan_nama} (${d.kode_kd})</div>
                    <div style="font-size:10px;font-weight:700;margin-top:2px;color:${status === 'bahaya' ? '#DC625F' : status === 'waspada' ? '#E8B84C' : '#20B88A'};">● Status Desa: ${status.toUpperCase()} (${d.pmk || 0} PMK, ${d.lsd || 0} LSD)</div>
                    <div style="font-size:11px;font-weight:800;color:#39D6C0;margin-top:2px;font-variant-numeric:tabular-nums;">
                        ${desaDetailText}
                    </div>
                </div>
            `,
                {
                    permanent: false,
                    direction: 'center',
                    className: 'custom-desa-tooltip',
                }
            );

            layer.on('mouseover', () => {
                if (selectedDesaId.value !== d.id) {
                    (layer as L.Path).setStyle({
                        weight: 2.8,
                        color: '#39D6C0',
                        fillOpacity: 0.4,
                    });
                }
            });

            layer.on('mouseout', () => {
                if (selectedDesaId.value !== d.id) {
                    (layer as L.Path).setStyle(getDesaPolygonStyle(d.id, false, activeKec));
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
        },
    }).addTo(map);
}

// 4. Heatmap Populasi Layer (Prop layerStates.heatmapPopulasi)
function syncHeatmapLayer() {
    if (!map) return;
    const isEnabled = Boolean(props.layerStates?.heatmapPopulasi);

    if (heatmapLayer) {
        map.removeLayer(heatmapLayer);
        heatmapLayer = null;
    }

    if (isEnabled && props.selectedMetric.kategori !== 'Penyakit') {
        const markers: L.Layer[] = [];
        const metricKey = props.selectedMetric.id as keyof KecamatanData;
        const maxVal = Math.max(...KECAMATAN_LIST.map((k) => Number(k[metricKey]) || 0), 1);

        KECAMATAN_LIST.forEach((kec) => {
            const val = Number(kec[metricKey]) || 0;
            if (val > 0) {
                const ratio = val / maxVal;
                const radius = Math.max(14, Math.min(46, Math.round(14 + ratio * 32)));

                const halo = L.circleMarker([kec.lat, kec.lng], {
                    radius: radius * 1.5,
                    fillColor: '#39D6C0',
                    fillOpacity: 0.18,
                    stroke: false,
                    interactive: false,
                });
                markers.push(halo);

                const core = L.circleMarker([kec.lat, kec.lng], {
                    radius: radius,
                    fillColor: '#159B78',
                    fillOpacity: 0.45,
                    color: '#39D6C0',
                    weight: 1.5,
                    interactive: true,
                });
                core.bindTooltip(
                    `
                    <div style="font-family:'Inter',sans-serif;font-size:11px;font-weight:700;color:#E8F0F6;text-align:center;">
                        ${kec.nama}
                        <div style="color:#39D6C0;font-family:monospace;font-size:10px;">${formatAngka(val)} ${props.selectedMetric.satuan}</div>
                    </div>
                `,
                    { className: 'custom-leaflet-tooltip' }
                );
                core.on('click', () => focusKecamatan(kec));
                markers.push(core);
            }
        });

        heatmapLayer = L.layerGroup(markers).addTo(map);
    }
}

// 5. Fasilitas Peternakan Layer (Prop layerStates.fasilitasPeternakan)
function syncFacilitiesLayer() {
    if (!map) return;
    const isEnabled = Boolean(props.layerStates?.fasilitasPeternakan);

    if (facilitiesLayer) {
        map.removeLayer(facilitiesLayer);
        facilitiesLayer = null;
    }

    if (isEnabled) {
        const markers: L.Layer[] = [];

        KECAMATAN_LIST.forEach((kec) => {
            if (kec.poskeswan > 0) {
                const icon = L.divIcon({
                    className: 'facility-marker',
                    html: `
                        <div style="background:#101D2B;border:1.5px solid #39D6C0;border-radius:8px;padding:3px 6px;display:flex;align-items:center;gap:4px;box-shadow:0 4px 12px rgba(0,0,0,0.6);white-space:nowrap;">
                            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#39D6C0;"></span>
                            <span style="font-size:10px;font-weight:700;color:#E8F0F6;font-family:sans-serif;">${kec.poskeswan} Unit</span>
                        </div>
                    `,
                    iconSize: [60, 24],
                    iconAnchor: [30, 12],
                });
                const marker = L.marker([kec.lat + 0.006, kec.lng + 0.006], { icon });
                marker.bindTooltip(
                    `
                    <div style="font-family:'Inter',sans-serif;font-size:11px;font-weight:700;color:#E8F0F6;text-align:center;">
                        Poskeswan / RPH ${kec.nama}
                        <div style="color:#39D6C0;font-size:10px;font-family:monospace;">${kec.poskeswan} Unit Fasilitas Resmi</div>
                    </div>
                `,
                    { className: 'custom-leaflet-tooltip' }
                );
                marker.on('click', () => focusKecamatan(kec));
                markers.push(marker);
            }
        });

        facilitiesLayer = L.layerGroup(markers).addTo(map);
    }
}

// 6. Jalur Transportasi Layer (Prop layerStates.jalurTransportasi)
function syncTransportLayer() {
    if (!map) return;
    const isEnabled = Boolean(props.layerStates?.jalurTransportasi);

    if (transportLayer) {
        map.removeLayer(transportLayer);
        transportLayer = null;
    }

    if (isEnabled) {
        transportLayer = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 19,
                opacity: 0.85,
            }
        ).addTo(map);
    }
}

// 7. Kasus Penyakit Markers (PMK / LSD)
function syncDiseaseMarkers() {
    if (!map) return;

    if (diseaseMarkersLayer) {
        map.removeLayer(diseaseMarkersLayer);
        diseaseMarkersLayer = null;
    }

    if (props.selectedMetric.kategori === 'Penyakit') {
        const markers: L.Layer[] = [];

        KECAMATAN_LIST.forEach((kec) => {
            const count = props.selectedMetric.id === 'pmk' ? kec.pmk : kec.lsd;
            if (count > 0) {
                const radius = Math.min(8 + Math.sqrt(count) * 6, 26);

                if (props.selectedMetric.id === 'pmk') {
                    const circle = L.circleMarker([kec.lat, kec.lng], {
                        radius: radius,
                        fillColor: '#DC625F',
                        fillOpacity: 0.85,
                        color: '#F5F5F5',
                        weight: 2,
                    });
                    circle.bindTooltip(`${kec.nama}: ${count} kasus PMK`, { className: 'custom-leaflet-tooltip' });
                    circle.on('click', () => focusKecamatan(kec));
                    markers.push(circle);
                } else {
                    const icon = L.divIcon({
                        className: 'lsd-diamond-marker',
                        html: `<div style="width:${radius * 1.8}px;height:${radius * 1.8}px;background:#DC625F;opacity:0.85;border:2px solid #F5F5F5;transform:rotate(45deg);"></div>`,
                        iconSize: [radius * 1.8, radius * 1.8],
                        iconAnchor: [radius * 0.9, radius * 0.9],
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

// 8. Tooltip / Floating label over selected polygon: dark badge with region name + value
function syncSelectedBadge() {
    if (!map) return;

    if (selectedBadgeMarker) {
        map.removeLayer(selectedBadgeMarker);
        selectedBadgeMarker = null;
    }

    if (!props.selectedKecamatanId) return;

    const kec = KECAMATAN_LIST.find((k) => k.id === props.selectedKecamatanId);
    if (!kec) return;

    const metricVal = Number(kec[props.selectedMetric.id as keyof KecamatanData]) || 0;
    const isPublic = props.currentRole === 'publik';

    let displayVal: string;
    if (isPublic) {
        if (props.selectedMetric.kategori === 'Penyakit') {
            displayVal = metricVal > 0 ? 'Terdata (Aktif)' : 'Aman';
        } else {
            const lvl = getClassLevel(metricVal, props.quantiles);
            const labels = [
                'Kategori Sangat Rendah',
                'Kategori Rendah',
                'Kategori Sedang',
                'Kategori Tinggi',
                'Sentra Unggulan',
            ];
            displayVal = labels[lvl - 1] || 'Terdata';
        }
    } else {
        const valFormatted = formatAngka(metricVal);
        const unit = props.selectedMetric.satuan || (props.selectedMetric.kategori === 'Penyakit' ? 'kasus' : 'ekor');
        displayVal = `${valFormatted} ${unit}`;
    }

    const html = `
        <div style="pointer-events:none;transform:translate(-50%, -120%);">
            <div style="background:rgba(23, 23, 23, 0.95);backdrop-filter:blur(10px);border:1.5px solid #00D084;border-radius:12px;padding:6px 12px;box-shadow:0 8px 24px -4px rgba(0,0,0,0.6);display:flex;align-items:center;gap:8px;white-space:nowrap;">
                <span style="width:8px;height:8px;border-radius:50%;background:#00D084;flex-shrink:0;"></span>
                <div style="display:flex;flex-direction:column;">
                    <div style="font-family:'Inter',sans-serif;font-size:12px;font-weight:800;color:#F5F5F5;line-height:1.2;">${kec.nama}</div>
                    <div style="font-family:${isPublic ? "'Inter',sans-serif" : 'monospace'};font-size:11px;font-weight:700;color:#39D6C0;font-variant-numeric:tabular-nums;">${displayVal}</div>
                </div>
            </div>
        </div>
    `;

    const badgeIcon = L.divIcon({
        className: 'selected-polygon-badge-icon',
        html: html,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
    });

    selectedBadgeMarker = L.marker([kec.lat, kec.lng], {
        icon: badgeIcon,
        interactive: false,
        zIndexOffset: 1000,
    }).addTo(map);
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
    emit('selectDesa', desa);
    map?.flyTo([desa.lat, desa.lng], 13.5, { animate: true, duration: 0.8 });
}

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

function recenter() {
    selectedDesaId.value = null;
    map?.flyTo([-7.456, 109.215], 11, { animate: true, duration: 0.8 });
}

defineExpose({
    focusDesa,
    recenter,
    focusKecamatan,
    invalidateSize: () => map?.invalidateSize(),
});

// Watch props
watch(
    () => [props.selectedMetric, props.quantiles, props.currentRole],
    () => {
        if (geojsonLayer) {
            geojsonLayer.setStyle(getFeatureStyle);
        }
        syncDesaLayer();
        syncDiseaseMarkers();
        syncHeatmapLayer();
        syncFacilitiesLayer();
        syncSelectedBadge();
    },
    { deep: true }
);

watch(
    () => props.selectedKecamatanId,
    (newId) => {
        if (geojsonLayer) {
            geojsonLayer.setStyle(getFeatureStyle);
        }
        syncDesaLayer();
        syncSelectedBadge();

        if (newId && map) {
            const target = KECAMATAN_LIST.find((k) => k.id === newId);
            if (target) {
                map.flyTo([target.lat, target.lng], 12.5, { animate: true, duration: 0.8 });
            }
        }
    }
);

watch(
    () => props.layerStates,
    () => {
        syncOuterCountyLayer();
        syncKecamatanLayer();
        syncDesaLayer();
        syncHeatmapLayer();
        syncFacilitiesLayer();
        syncTransportLayer();
    },
    { deep: true }
);

watch(
    () => props.mapEngine,
    (engine) => {
        if (engine === 'vector') {
            applyBasemap('dark');
        } else {
            applyBasemap('satellite');
        }
    }
);

watch(selectedDesaId, () => {
    if (desaGeoJsonLayer) {
        desaGeoJsonLayer.setStyle((feature: any) => {
            const isSelectedDesa = selectedDesaId.value === feature?.properties?.id;
            return getDesaPolygonStyle(feature?.properties?.id, isSelectedDesa, props.selectedKecamatanId);
        });
    }
});

onMounted(() => {
    initMap();
});

onUnmounted(() => {
    if (map) {
        try {
            map.remove();
        } catch {}
        map = null;
    }
    if (mapContainer.value && (mapContainer.value as any)._leaflet_id) {
        delete (mapContainer.value as any)._leaflet_id;
    }
});
</script>

<template>
    <!-- Map Canvas Container: fills 100% of container, dark geospatial command center styling -->
    <div
        class="relative w-full h-full min-h-[580px] lg:min-h-[720px] flex-1 dark:bg-[#090909] bg-[#F7F9F6] border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl overflow-hidden select-none flex flex-col font-sans shadow-2xl"
    >
        <!-- Leaflet Map Canvas (100% full bleed) -->
        <div ref="mapContainer" class="w-full h-full absolute inset-0 z-0"></div>

        <!-- 1. FLOATING TOP-LEFT SEARCH BAR (top-4 left-4 z-20) -->
        <div class="absolute top-4 left-4 z-20 w-72 sm:w-84 md:w-96">
            <div class="relative">
                <div
                    class="h-11 dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] focus-within:border-[#00D084] rounded-xl px-3.5 flex items-center gap-2.5 shadow-xl transition-all"
                >
                    <Search class="w-4 h-4 text-[#00D084] shrink-0" />
                    <input
                        v-model="searchQuery"
                        @focus="isSearchFocused = true"
                        type="text"
                        placeholder="Cari kecamatan / desa di Banyumas..."
                        class="bg-transparent text-xs dark:text-[#F5F5F5] text-[#171B17] w-full outline-none placeholder:text-[#687668]"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="text-[#687668] hover:text-[#00D084] cursor-pointer transition-colors"
                        aria-label="Hapus Pencarian"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Autocomplete Dropdown List for 27 Kecamatan & 331 Desa -->
                <div
                    v-if="isSearchFocused && searchResults.length > 0"
                    class="absolute top-12 left-0 right-0 dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-1.5 shadow-2xl z-30 space-y-1 max-h-72 overflow-y-auto"
                >
                    <div
                        v-for="res in searchResults"
                        :key="res.id"
                        @click="handleSelectSearchResult(res)"
                        class="px-3 py-2 rounded-lg dark:hover:bg-[#181818] hover:bg-[#F0F4F1] cursor-pointer flex items-center justify-between text-xs transition-colors"
                    >
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <MapPin
                                class="w-3.5 h-3.5 shrink-0"
                                :class="res.type === 'desa' ? 'text-[#39D6C0]' : 'text-[#00D084]'"
                            />
                            <div class="truncate">
                                <span class="font-bold dark:text-[#F5F5F5] text-[#171B17]">{{ res.title }}</span>
                                <div class="text-[10px] dark:text-[#B3B3B3] text-[#687668] truncate tabular-nums">{{ res.subtitle }}</div>
                            </div>
                        </div>
                        <span
                            class="text-[9px] px-1.5 py-0.5 rounded font-mono tabular-nums shrink-0"
                            :class="
                                res.type === 'desa'
                                    ? 'bg-[#123D43] text-[#39D6C0] border border-[#39D6C0]/30'
                                    : 'dark:bg-[#063D2D] bg-[#E8F8F0] text-[#00D084] border border-[#00D084]/40'
                            "
                        >
                            {{ res.type === 'desa' ? 'Desa' : 'Kec' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FLOATING TOP-RIGHT BADGE SOROTAN (Simetris Sumbu Horizontal & Vertikal) -->
        <div v-if="topRankKecamatan" class="absolute top-4 right-4 z-20 hidden md:flex items-center h-11">
            <button
                type="button"
                @click="focusKecamatan(topRankKecamatan)"
                class="h-10 inline-flex items-center justify-center gap-2 px-4 rounded-xl border font-bold text-xs shadow-lg backdrop-blur-md transition-all select-none hover:scale-[1.02] active:scale-[0.98] cursor-pointer tabular-nums"
                :class="
                    selectedMetric.kategori === 'Penyakit'
                        ? 'bg-[#152538]/95 border-[#F2B84B]/50 text-[#F2B84B]'
                        : 'bg-[#101D2B]/95 border-[#159B78]/50 text-[#39D6C0]'
                "
                :title="`Klik untuk pusatkan peta ke Kecamatan ${topRankKecamatan.nama}`"
            >
                <span
                    class="w-2 h-2 rounded-full"
                    :class="selectedMetric.kategori === 'Penyakit' ? 'bg-[#F2B84B]' : 'bg-[#00B98B]'"
                ></span>
                <span class="leading-none tracking-normal">
                    {{ selectedMetric.kategori === 'Penyakit' ? 'Zona Terkritis: ' : 'Terbanyak: ' }}
                    <span class="font-extrabold text-[#E8F0F6]">{{ topRankKecamatan.nama }}</span>
                    <span class="font-mono tabular-nums text-[#A6B7C8]"> ({{ topRankValueFormatted }})</span>
                </span>
            </button>
        </div>

        <!-- 2. FLOATING BOTTOM-LEFT SCALE BAR (bottom-4 left-4 z-20) -->
        <div class="absolute bottom-4 left-4 z-20 flex flex-wrap items-center gap-2">
            <!-- Scale Bar Container -->
            <div
                class="flex items-center gap-2.5 dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] px-3.5 py-2 rounded-xl shadow-lg select-none"
                title="Skala Geospasial WebGIS Banyumas"
            >
                <div class="flex flex-col gap-1">
                    <div
                        class="flex justify-between w-32 text-[10px] font-mono tabular-nums dark:text-[#B3B3B3] text-[#687668] leading-none px-0.5"
                    >
                        <span>0</span>
                        <span>{{ scaleValues.mid1 }}</span>
                        <span>{{ scaleValues.mid2 }}</span>
                        <span>{{ scaleValues.total }}</span>
                    </div>
                    <!-- Crisp Notched Scale Bar Graphic with Ticks -->
                    <div class="relative w-32 h-2 flex items-center">
                        <div class="w-full h-1.5 dark:bg-[#090909] bg-[#E0E4DD] border dark:border-[#1E1E1E] border-[#CCD2C8] rounded-[2px] flex overflow-hidden">
                            <div class="w-1/3 h-full bg-[#00D084]"></div>
                            <div class="w-1/3 h-full dark:bg-[#090909] bg-[#E0E4DD] border-x dark:border-[#1E1E1E] border-[#CCD2C8]"></div>
                            <div class="w-1/3 h-full bg-[#00D084]"></div>
                        </div>
                        <div class="absolute inset-0 flex justify-between pointer-events-none -top-0.5">
                            <div class="w-[1.5px] h-3 bg-[#00D084]"></div>
                            <div class="w-[1px] h-3 bg-[#00D084]/70"></div>
                            <div class="w-[1px] h-3 bg-[#00D084]/70"></div>
                            <div class="w-[1.5px] h-3 bg-[#00D084]"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Basemap Style Picker -->
            <div class="relative">
                <button
                    type="button"
                    @click="showBasemapMenu = !showBasemapMenu"
                    class="h-10 px-3 dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] hover:border-[#00D084] dark:text-[#F5F5F5] text-[#171B17] rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg dark:hover:bg-[#181818] hover:bg-[#F0F4F1] transition-all cursor-pointer"
                    title="Ganti Jenis Basemap Peta"
                >
                    <Layers class="w-3.5 h-3.5 text-[#00D084]" />
                    <span class="hidden sm:inline">{{ basemapConfigs[currentBasemap].label }}</span>
                </button>

                <!-- Basemap Choices Popover -->
                <div
                    v-if="showBasemapMenu"
                    class="absolute bottom-12 left-0 w-64 dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-2xl p-2.5 shadow-2xl z-30 space-y-1.5"
                >
                    <div class="text-[10px] font-bold dark:text-[#687668] text-[#8C988C] px-2 py-1 uppercase tracking-wider">
                        Pilihan Basemap:
                    </div>
                    <button
                        v-for="(cfg, key) in basemapConfigs"
                        :key="key"
                        type="button"
                        @click="applyBasemap(key as any)"
                        class="w-full text-left p-2.5 rounded-xl flex flex-col transition-all cursor-pointer"
                        :class="
                            currentBasemap === key
                                ? 'dark:bg-[#063D2D] bg-[#E8F8F0] border border-[#00D084]'
                                : 'dark:hover:bg-[#181818] hover:bg-[#F0F4F1] border border-transparent'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold"
                                :class="currentBasemap === key ? 'text-[#00D084]' : 'dark:text-[#F5F5F5] text-[#171B17]'"
                            >
                                {{ cfg.label }}
                            </span>
                            <span v-if="currentBasemap === key" class="w-2 h-2 rounded-full bg-[#00D084]"></span>
                        </div>
                        <span class="text-[10px] dark:text-[#B3B3B3] text-[#687668] leading-tight mt-0.5">{{ cfg.desc }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. FLOATING BOTTOM-CENTER-RIGHT COORDINATES CHIP (bottom-4 right-72 sm:right-80 z-20) -->
        <div
            class="absolute bottom-4 right-72 sm:right-80 z-20 hidden sm:flex items-center gap-2 dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] px-3.5 py-2 rounded-xl shadow-lg text-xs font-mono tabular-nums dark:text-[#F5F5F5] text-[#171B17] select-none"
            title="Koordinat Geospasial Aktif (WGS84)"
        >
            <Compass class="w-3.5 h-3.5 text-[#00D084] shrink-0" />
            <span>{{ formattedCoordinates }}</span>
        </div>

        <!-- 4. FLOATING BOTTOM-RIGHT ZOOM CONTROLS (bottom-24 right-4 z-20) -->
        <div class="absolute bottom-24 right-4 z-20 flex flex-col gap-2">
            <!-- Recenter Button -->
            <button
                type="button"
                @click="recenter"
                class="w-9 h-9 rounded-lg dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] hover:border-[#00D084] dark:text-[#F5F5F5] text-[#171B17] flex items-center justify-center shadow-lg transition-all hover:text-[#00D084] cursor-pointer"
                title="Pusatkan Peta ke Banyumas"
                aria-label="Pusatkan Peta ke Kabupaten Banyumas"
            >
                <Crosshair class="w-4 h-4 stroke-[2.2]" />
            </button>

            <!-- Compact Plus (+) & Minus (-) Zoom Control Group -->
            <div
                class="flex flex-col dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-lg overflow-hidden shadow-lg divide-y dark:divide-[#1E1E1E] divide-[#E0E4DD]"
            >
                <button
                    type="button"
                    @click="zoomIn"
                    class="w-9 h-9 flex items-center justify-center dark:text-[#F5F5F5] text-[#171B17] dark:hover:bg-[#181818] hover:bg-[#F0F4F1] hover:text-[#00D084] transition-colors cursor-pointer"
                    title="Perbesar (Zoom In)"
                    aria-label="Perbesar Peta"
                >
                    <Plus class="w-4 h-4 stroke-[2.2]" />
                </button>
                <button
                    type="button"
                    @click="zoomOut"
                    class="w-9 h-9 flex items-center justify-center dark:text-[#F5F5F5] text-[#171B17] dark:hover:bg-[#181818] hover:bg-[#F0F4F1] hover:text-[#00D084] transition-colors cursor-pointer"
                    title="Perkecil (Zoom Out)"
                    aria-label="Perkecil Peta"
                >
                    <Minus class="w-4 h-4 stroke-[2.2]" />
                </button>
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet Tooltips matching Forest Intelligence Dark & Light GIS */
.custom-leaflet-tooltip {
    background-color: #111111 !important;
    color: #F5F5F5 !important;
    border: 1px solid #1E1E1E !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
    padding: 6px 10px !important;
}
.custom-leaflet-tooltip::before {
    display: none !important;
}

:root:not(.dark) .custom-leaflet-tooltip {
    background-color: #FFFFFF !important;
    color: #171B17 !important;
    border: 1px solid #E0E4DD !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

.custom-desa-tooltip {
    background-color: #111111 !important;
    color: #F5F5F5 !important;
    border: 1px solid #1E1E1E !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
    padding: 4px 8px !important;
}
.custom-desa-tooltip::before {
    display: none !important;
}

:root:not(.dark) .custom-desa-tooltip {
    background-color: #FFFFFF !important;
    color: #171B17 !important;
    border: 1px solid #E0E4DD !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

.leaflet-container {
    background-color: #090909 !important;
    font-family: inherit !important;
}

:root:not(.dark) .leaflet-container {
    background-color: #F7F9F6 !important;
}

.selected-polygon-badge-icon {
    background: transparent !important;
    border: none !important;
}
</style>
