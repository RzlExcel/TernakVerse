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

interface FacilityItem {
    id: string | number;
    nama?: string;
    nama_rph?: string;
    nama_puskeswan?: string;
    nama_pasar?: string;
    tipe_fasilitas?: 'RPH' | 'Puskeswan' | 'Pasar Hewan' | string;
    tipe?: string;
    jenis?: string;
    alamat?: string;
    alamat_lengkap?: string;
    status_operasional?: string;
    lat: number;
    lng: number;
    kecamatan?: string;
    nama_kecamatan?: string;
    kapasitas_potong_ekor_hari?: number;
    jenis_hewan_potong?: string;
    wilayah_binaan?: string;
    penanggung_jawab?: string;
    nomor_kontak?: string;
    hari_pasaran?: string;
    kapasitas?: number;
    kapasitas_tampung_ekor?: number;
}

const props = withDefaults(
    defineProps<{
        selectedMetric: MetricDefinition;
        quantiles: number[];
        currentRole: 'publik' | 'dinas' | 'admin';
        selectedKecamatanId?: string;
        layerStates?: Record<string, boolean>;
        mapEngine?: 'real' | 'vector';
        facilities?: FacilityItem[];
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
let heatmapLayer: L.Layer | null = null;
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

function getDesaMetricValue(d: DesaData, metricId: string): number {
    if (metricId === 'populasi_total') {
        return (
            (d.sapi_potong || 0) +
            (d.sapi_perah || 0) +
            (d.kambing || 0) +
            (d.domba || 0) +
            (d.ayam_potong || 0) +
            (d.ayam_petelur || 0)
        );
    }
    if (metricId === 'kasus_total') {
        return (d.pmk || 0) + (d.lsd || 0);
    }
    if (metricId === 'produksi_total') {
        return (
            (d.telur_ayam || 0) +
            (d.daging_ayam || 0) +
            (d.daging_sapi || 0) +
            (d.daging_kambing || 0) +
            (d.daging_domba || 0) +
            (d.susu_sapi || 0) +
            (d.susu_kambing || 0) +
            (d.susu_domba || 0)
        );
    }
    if (metricId === 'fasilitas_total') {
        return (d.poskeswan || 0) + (d.rph || 0);
    }
    return Number(d[metricId as keyof DesaData] || 0);
}

function getKecamatanMetricValue(kec: KecamatanData, metricId: string): number {
    if (metricId === 'populasi_total') {
        return (
            kec.populasi_total ??
            ((kec.sapi_potong || 0) +
                (kec.sapi_perah || 0) +
                (kec.kambing || 0) +
                (kec.domba || 0) +
                (kec.ayam_potong || 0) +
                (kec.ayam_petelur || 0) +
                (kec.kambing_perah || 0))
        );
    }
    if (metricId === 'kasus_total') {
        return kec.kasus_total ?? ((kec.pmk || 0) + (kec.lsd || 0));
    }
    if (metricId === 'produksi_total') {
        return (
            kec.produksi_total ??
            ((kec.telur_ayam || 0) +
                (kec.daging_ayam || 0) +
                (kec.daging_sapi || 0) +
                (kec.daging_kambing || 0) +
                (kec.daging_domba || 0) +
                (kec.susu_sapi || 0) +
                (kec.susu_kambing || 0) +
                (kec.susu_domba || 0))
        );
    }
    if (metricId === 'fasilitas_total') {
        return kec.fasilitas_total ?? ((kec.poskeswan || 0) + (kec.rph || 0));
    }
    return Number(kec[metricId as keyof KecamatanData] || 0);
}

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
                    : `Kec. ${d.kecamatan_nama} · ${formatAngka(getDesaMetricValue(d, props.selectedMetric.id))} ${props.selectedMetric.satuan}`,
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
    const sorted = [...KECAMATAN_LIST].sort(
        (a, b) => getKecamatanMetricValue(b, props.selectedMetric.id) - getKecamatanMetricValue(a, props.selectedMetric.id)
    );
    return sorted[0];
});

const topRankValueFormatted = computed(() => {
    if (!topRankKecamatan.value) return '';
    if (props.currentRole === 'publik') return 'Sentra Unggulan';
    const val = getKecamatanMetricValue(topRankKecamatan.value, props.selectedMetric.id);
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
        const val = getKecamatanMetricValue(kec, props.selectedMetric.id);
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

            const metricVal = getKecamatanMetricValue(kec, props.selectedMetric.id);
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
            const metricVal = getDesaMetricValue(d, props.selectedMetric.id);
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

// 4. Heatmap Populasi Layer (Vivid 4-tier thermal neon gradient)
const HEATMAP_CONFIG = {
    radius: 26,
    blur: 18,
    maxZoom: 14,
    gradient: {
        0.2: '#00D084', // Emerald (Densitas Rendah)
        0.5: '#F59E0B', // Amber (Densitas Sedang)
        0.75: '#FF7A45', // Coral / Oranye (Densitas Tinggi)
        1.0: '#EF4444', // Crimson Red (Densitas Sangat Tinggi / Sentra Utama)
    },
};

function createHeatCanvasLayer(points: Array<[number, number, number]>, options: typeof HEATMAP_CONFIG): L.Layer {
    const canvas = document.createElement('canvas');
    canvas.className = 'leaflet-heatmap-layer';
    canvas.style.position = 'absolute';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = '250';

    let mapInstance: L.Map | null = null;

    // 1. Create 256-pixel gradient Lookup Table (LUT)
    const lutCanvas = document.createElement('canvas');
    lutCanvas.width = 1;
    lutCanvas.height = 256;
    const lutCtx = lutCanvas.getContext('2d');
    let lutData: Uint8ClampedArray | null = null;
    if (lutCtx) {
        const grad = lutCtx.createLinearGradient(0, 0, 0, 256);
        for (const stopKey in options.gradient) {
            grad.addColorStop(parseFloat(stopKey), options.gradient[stopKey as unknown as keyof typeof options.gradient]);
        }
        lutCtx.fillStyle = grad;
        lutCtx.fillRect(0, 0, 1, 256);
        lutData = lutCtx.getImageData(0, 0, 1, 256).data;
    }

    // 2. Pre-render circular radial brush for smooth continuous canopy
    const brushCanvas = document.createElement('canvas');
    const r = options.radius;
    const blur = options.blur;
    const r2 = r + blur;
    brushCanvas.width = brushCanvas.height = r2 * 2;
    const brushCtx = brushCanvas.getContext('2d');
    if (brushCtx) {
        const radGrad = brushCtx.createRadialGradient(r2, r2, Math.max(0, r - blur), r2, r2, r2);
        radGrad.addColorStop(0, 'rgba(0,0,0,1)');
        radGrad.addColorStop(1, 'rgba(0,0,0,0)');
        brushCtx.fillStyle = radGrad;
        brushCtx.fillRect(0, 0, r2 * 2, r2 * 2);
    }

    function render() {
        if (!mapInstance || !lutData) return;
        const size = mapInstance.getSize();
        if (size.x <= 0 || size.y <= 0) return;

        canvas.width = size.x;
        canvas.height = size.y;

        const topLeft = mapInstance.containerPointToLayerPoint([0, 0]);
        L.DomUtil.setPosition(canvas, topLeft);

        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.clearRect(0, 0, size.x, size.y);

        // Render point stamps to alpha channel
        for (const [lat, lng, val] of points) {
            const p = mapInstance.latLngToContainerPoint([lat, lng]);
            if (p.x < -r2 || p.x > size.x + r2 || p.y < -r2 || p.y > size.y + r2) continue;
            ctx.globalAlpha = Math.min(Math.max(val, 0.05), 1.0);
            ctx.drawImage(brushCanvas, p.x - r2, p.y - r2);
        }

        // Apply thermal color palette from gradient LUT
        try {
            const imgData = ctx.getImageData(0, 0, size.x, size.y);
            const data = imgData.data;
            for (let i = 0; i < data.length; i += 4) {
                const alpha = data[i + 3];
                if (alpha > 0) {
                    const offset = alpha * 4;
                    data[i] = lutData[offset];         // R
                    data[i + 1] = lutData[offset + 1]; // G
                    data[i + 2] = lutData[offset + 2]; // B
                    data[i + 3] = Math.min(235, Math.round(alpha * 0.9)); // Luminous neon alpha
                }
            }
            ctx.putImageData(imgData, 0, 0);
        } catch {
            // DOM / Canvas fallback
        }
    }

    const CustomLayer = L.Layer.extend({
        onAdd(m: L.Map) {
            mapInstance = m;
            const pane = m.getPanes().overlayPane;
            pane.appendChild(canvas);
            m.on('moveend zoomend resize', render);
            render();
            return this;
        },
        onRemove(m: L.Map) {
            if (canvas.parentNode) {
                canvas.parentNode.removeChild(canvas);
            }
            m.off('moveend zoomend resize', render);
            mapInstance = null;
            return this;
        },
    });

    return new (CustomLayer as any)();
}

function syncHeatmapLayer() {
    if (!map) return;
    const isEnabled = Boolean(props.layerStates?.heatmapPopulasi);

    if (heatmapLayer) {
        map.removeLayer(heatmapLayer);
        heatmapLayer = null;
    }

    if (isEnabled && props.selectedMetric.kategori !== 'Penyakit') {
        const points: Array<[number, number, number]> = [];

        // 1. Data point per kecamatan
        const maxKecVal = Math.max(
            ...KECAMATAN_LIST.map((k) => getKecamatanMetricValue(k, props.selectedMetric.id)),
            1
        );
        KECAMATAN_LIST.forEach((kec) => {
            const val = getKecamatanMetricValue(kec, props.selectedMetric.id);
            if (val > 0) {
                const ratio = val / maxKecVal;
                points.push([kec.lat, kec.lng, Math.min(1.0, Math.max(0.2, ratio))]);
            }
        });

        // 2. Data point per desa untuk kanopi termal yang halus dan rapat
        const maxDesaVal = Math.max(
            ...BANYUMAS_DESA_LIST.map((d) => getDesaMetricValue(d, props.selectedMetric.id)),
            1
        );
        BANYUMAS_DESA_LIST.forEach((desa) => {
            const val = getDesaMetricValue(desa, props.selectedMetric.id);
            if (val > 0) {
                const ratio = val / maxDesaVal;
                points.push([desa.lat, desa.lng, Math.min(1.0, Math.max(0.15, ratio))]);
            }
        });

        if (points.length > 0) {
            if (typeof (L as any).heatLayer === 'function') {
                heatmapLayer = (L as any).heatLayer(points, HEATMAP_CONFIG).addTo(map);
            } else {
                heatmapLayer = createHeatCanvasLayer(points, HEATMAP_CONFIG).addTo(map);
            }
        }
    }
}

// 5. Fasilitas Peternakan Master Data & Layer Management (11 Fasilitas Resmi Kabupaten Banyumas)
const BANYUMAS_FACILITIES: FacilityItem[] = [
    // 6 RPH (Rumah Potong Hewan) Resmi
    {
        id: 'RPH-01',
        nama: 'RPH Sokaraja',
        tipe_fasilitas: 'RPH',
        alamat: 'Sokaraja Kidul, Kecamatan Sokaraja, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4562,
        lng: 109.2645,
        kecamatan: 'Sokaraja',
        kapasitas_potong_ekor_hari: 20,
        jenis_hewan_potong: 'Sapi, Kambing',
        nomor_kontak: '(0281) 644201',
    },
    {
        id: 'RPH-02',
        nama: 'RPH Ajibarang',
        tipe_fasilitas: 'RPH',
        alamat: 'Pancasan, Kecamatan Ajibarang, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4125,
        lng: 109.0732,
        kecamatan: 'Ajibarang',
        kapasitas_potong_ekor_hari: 15,
        jenis_hewan_potong: 'Sapi, Kambing',
        nomor_kontak: '081391234567',
    },
    {
        id: 'RPH-03',
        nama: 'RPH Wangon',
        tipe_fasilitas: 'RPH',
        alamat: 'Wangon, Kecamatan Wangon, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.5187,
        lng: 109.0521,
        kecamatan: 'Wangon',
        kapasitas_potong_ekor_hari: 15,
        jenis_hewan_potong: 'Sapi, Kambing',
        nomor_kontak: '(0281) 657190',
    },
    {
        id: 'RPH-04',
        nama: 'RPH Cilongok',
        tipe_fasilitas: 'RPH',
        alamat: 'Pernasidi, Kecamatan Cilongok, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4150,
        lng: 109.1350,
        kecamatan: 'Cilongok',
        kapasitas_potong_ekor_hari: 20,
        jenis_hewan_potong: 'Sapi, Kambing',
        nomor_kontak: '(0281) 655120',
    },
    {
        id: 'RPH-05',
        nama: 'RPH Sumpiuh',
        tipe_fasilitas: 'RPH',
        alamat: 'Kradenan Sumpiuh, Kecamatan Sumpiuh, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.6124,
        lng: 109.3582,
        kecamatan: 'Sumpiuh',
        kapasitas_potong_ekor_hari: 15,
        jenis_hewan_potong: 'Sapi, Kambing',
        nomor_kontak: '(0281) 689432',
    },
    {
        id: 'RPH-06',
        nama: 'RPH Purwokerto',
        tipe_fasilitas: 'RPH',
        alamat: 'Pasirmuncang, Purwokerto Barat, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4225,
        lng: 109.2215,
        kecamatan: 'Purwokerto Barat',
        kapasitas_potong_ekor_hari: 30,
        jenis_hewan_potong: 'Sapi, Kerbau, Kambing',
        nomor_kontak: '(0281) 632111',
    },
    // 3 Pasar Hewan Resmi
    {
        id: 'PH-01',
        nama: 'Pasar Hewan Ajibarang',
        tipe_fasilitas: 'Pasar Hewan',
        alamat: 'Ajibarang Kulon, Kecamatan Ajibarang, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4112,
        lng: 109.0768,
        kecamatan: 'Ajibarang',
        hari_pasaran: 'Pon, Wage',
        kapasitas: 1500,
        kapasitas_tampung_ekor: 1500,
    },
    {
        id: 'PH-02',
        nama: 'Pasar Hewan Sokaraja',
        tipe_fasilitas: 'Pasar Hewan',
        alamat: 'Sokaraja Kidul, Kecamatan Sokaraja, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4589,
        lng: 109.2612,
        kecamatan: 'Sokaraja',
        hari_pasaran: 'Pon, Kliwon',
        kapasitas: 1000,
        kapasitas_tampung_ekor: 1000,
    },
    {
        id: 'PH-03',
        nama: 'Pasar Hewan Cilongok / Manisan',
        tipe_fasilitas: 'Pasar Hewan',
        alamat: 'Pernasidi Cilongok, Kecamatan Cilongok, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4089,
        lng: 109.1362,
        kecamatan: 'Cilongok',
        hari_pasaran: 'Legi / Manis',
        kapasitas: 800,
        kapasitas_tampung_ekor: 800,
    },
    // 2 UPT Puskeswan Resmi
    {
        id: 'PKW-01',
        nama: 'UPT Puskeswan Banyumas',
        tipe_fasilitas: 'Puskeswan',
        alamat: 'Kejawar, Kecamatan Banyumas, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.5142,
        lng: 109.2985,
        kecamatan: 'Banyumas',
        wilayah_binaan: 'Banyumas, Somagede, Kalibagor, Patikraja, Tambak, Sumpiuh, Kemranjen',
        penanggung_jawab: 'drh. Heni Puspitasari',
        nomor_kontak: '(0281) 796123',
    },
    {
        id: 'PKW-02',
        nama: 'UPT Puskeswan Cilongok',
        tipe_fasilitas: 'Puskeswan',
        alamat: 'Pernasidi, Kecamatan Cilongok, Kabupaten Banyumas',
        status_operasional: 'Aktif',
        lat: -7.4110,
        lng: 109.1330,
        kecamatan: 'Cilongok',
        wilayah_binaan: 'Cilongok, Ajibarang, Gumelar, Pekuncen, Wangon, Lumbir, Jatilawang',
        penanggung_jawab: 'drh. Agus Santoso',
        nomor_kontak: '(0281) 655432',
    },
];

function resolveFacilityType(f: any): 'RPH' | 'Puskeswan' | 'Pasar Hewan' {
    const raw = String(
        f.tipe_fasilitas ||
        f.tipe ||
        f.jenis ||
        f.nama ||
        f.nama_rph ||
        f.nama_puskeswan ||
        f.nama_pasar ||
        ''
    ).toLowerCase();

    if (raw.includes('rph') || raw.includes('potong')) return 'RPH';
    if (raw.includes('puskeswan') || raw.includes('poskeswan')) return 'Puskeswan';
    if (raw.includes('pasar')) return 'Pasar Hewan';
    return 'Puskeswan';
}

function getFacilityColor(type: 'RPH' | 'Puskeswan' | 'Pasar Hewan'): string {
    switch (type) {
        case 'RPH':
            return '#EF4444';
        case 'Puskeswan':
            return '#00D084';
        case 'Pasar Hewan':
            return '#F59E0B';
    }
}

function createFacilityIcon(type: 'RPH' | 'Puskeswan' | 'Pasar Hewan'): L.DivIcon {
    if (type === 'RPH') {
        return L.divIcon({
            className: 'facility-marker-rph',
            html: `
                <div style="background:rgba(27,14,18,0.95);border:1.5px solid #EF4444;border-radius:10px;padding:3px 8px;display:inline-flex;align-items:center;gap:5px;box-shadow:0 4px 14px rgba(239,68,68,0.45);backdrop-filter:blur(8px);white-space:nowrap;cursor:pointer;">
                    <span style="display:flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:6px;background:rgba(239,68,68,0.2);color:#EF4444;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 18h12"/>
                            <path d="M3 22h18"/>
                            <path d="M19 10l-7-7-7 7v11h14V10z"/>
                            <path d="M12 10v4"/>
                            <path d="M10 12h4"/>
                        </svg>
                    </span>
                    <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;color:#FCA5A5;letter-spacing:0.02em;">RPH</span>
                </div>
            `,
            iconSize: [68, 28],
            iconAnchor: [34, 14],
        });
    }

    if (type === 'Puskeswan') {
        return L.divIcon({
            className: 'facility-marker-puskeswan',
            html: `
                <div style="background:rgba(8,28,22,0.95);border:1.5px solid #00D084;border-radius:10px;padding:3px 8px;display:inline-flex;align-items:center;gap:5px;box-shadow:0 4px 14px rgba(0,208,132,0.45);backdrop-filter:blur(8px);white-space:nowrap;cursor:pointer;">
                    <span style="display:flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:6px;background:rgba(0,208,132,0.2);color:#00D084;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 4v16"/>
                            <path d="M4 12h16"/>
                        </svg>
                    </span>
                    <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;color:#6EE7B7;letter-spacing:0.02em;">Puskeswan</span>
                </div>
            `,
            iconSize: [100, 28],
            iconAnchor: [50, 14],
        });
    }

    // Pasar Hewan
    return L.divIcon({
        className: 'facility-marker-pasar',
        html: `
            <div style="background:rgba(31,22,8,0.95);border:1.5px solid #F59E0B;border-radius:10px;padding:3px 8px;display:inline-flex;align-items:center;gap:5px;box-shadow:0 4px 14px rgba(245,158,11,0.45);backdrop-filter:blur(8px);white-space:nowrap;cursor:pointer;">
                <span style="display:flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:6px;background:rgba(245,158,11,0.2);color:#F59E0B;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                        <path d="M3 6h18"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                </span>
                <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;color:#FCD34D;letter-spacing:0.02em;">Pasar Hewan</span>
            </div>
        `,
        iconSize: [110, 28],
        iconAnchor: [55, 14],
    });
}

function escapeHtml(str: unknown): string {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildFacilityPopupHtml(f: any): string {
    const type = resolveFacilityType(f);
    const rawNama = f.nama || f.nama_rph || f.nama_puskeswan || f.nama_pasar || f.id || 'Fasilitas Peternakan';
    const rawAlamat = f.alamat || f.alamat_lengkap || 'Kabupaten Banyumas';
    const nama = escapeHtml(rawNama);
    const alamat = escapeHtml(rawAlamat);
    const status = escapeHtml(f.status_operasional || 'Aktif');

    let badgeColor = '#00D084';
    let badgeBg = 'rgba(0, 208, 132, 0.15)';
    let badgeBorder = 'rgba(0, 208, 132, 0.35)';
    let extraFieldsHtml = '';

    if (type === 'RPH') {
        badgeColor = '#EF4444';
        badgeBg = 'rgba(239, 68, 68, 0.15)';
        badgeBorder = 'rgba(239, 68, 68, 0.35)';
        const kap = escapeHtml(f.kapasitas_potong_ekor_hari ?? '-');
        const jenis = escapeHtml(f.jenis_hewan_potong ?? 'Sapi, Kerbau, Kambing');
        extraFieldsHtml = `
            <div style="display:flex;flex-direction:column;gap:4px;margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,0.08);font-size:11px;">
                <div style="color:#94A3B8;">Kapasitas Potong: <strong style="color:#F8FAFC;">${kap} ekor/hari</strong></div>
                <div style="color:#94A3B8;">Jenis Hewan: <strong style="color:#F8FAFC;">${jenis}</strong></div>
            </div>
        `;
    } else if (type === 'Puskeswan') {
        badgeColor = '#00D084';
        badgeBg = 'rgba(0, 208, 132, 0.15)';
        badgeBorder = 'rgba(0, 208, 132, 0.35)';
        const binaan = escapeHtml(f.wilayah_binaan ?? 'Banyumas Raya');
        const kontak = escapeHtml(f.nomor_kontak ?? f.penanggung_jawab ?? f.dokter_hewan_penanggungjawab ?? '-');
        extraFieldsHtml = `
            <div style="display:flex;flex-direction:column;gap:4px;margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,0.08);font-size:11px;">
                <div style="color:#94A3B8;">Wilayah Binaan: <strong style="color:#F8FAFC;">${binaan}</strong></div>
                <div style="color:#94A3B8;">Penanggung Jawab / Kontak: <strong style="color:#F8FAFC;">${kontak}</strong></div>
            </div>
        `;
    } else if (type === 'Pasar Hewan') {
        badgeColor = '#F59E0B';
        badgeBg = 'rgba(245, 158, 11, 0.15)';
        badgeBorder = 'rgba(245, 158, 11, 0.35)';
        const pasaran = escapeHtml(f.hari_pasaran ?? '-');
        const kap = escapeHtml(f.kapasitas ?? f.kapasitas_tampung_ekor ?? '-');
        extraFieldsHtml = `
            <div style="display:flex;flex-direction:column;gap:4px;margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,0.08);font-size:11px;">
                <div style="color:#94A3B8;">Hari Pasaran: <strong style="color:#F8FAFC;">${pasaran}</strong></div>
                <div style="color:#94A3B8;">Kapasitas: <strong style="color:#F8FAFC;">${kap} ekor</strong></div>
            </div>
        `;
    }

    return `
        <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:220px;max-width:280px;background:#0D111A;color:#E2E8F0;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,0.12);box-shadow:0 12px 30px rgba(0,0,0,0.65);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                <span style="font-size:10px;font-weight:700;color:${badgeColor};background:${badgeBg};border:1px solid ${badgeBorder};padding:2px 7px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;">
                    ${type}
                </span>
                <span style="font-size:10px;font-weight:600;color:#10B981;background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.3);padding:2px 7px;border-radius:6px;display:flex;align-items:center;gap:4px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:#10B981;"></span>
                    ${status}
                </span>
            </div>
            <div style="font-size:13px;font-weight:700;color:#F8FAFC;line-height:1.35;margin-bottom:4px;">
                ${nama}
            </div>
            <div style="font-size:11px;color:#94A3B8;line-height:1.45;">
                ${alamat}
            </div>
            ${extraFieldsHtml}
        </div>
    `;
}

// 5. Fasilitas Peternakan Layer (Support sub-filters & fallback to all if fasilitasPeternakan is true)
function syncFacilitiesLayer() {
    if (!map) return;

    if (!props.layerStates?.fasilitasPeternakan) {
        if (facilitiesLayer && map) {
            map.removeLayer(facilitiesLayer);
            facilitiesLayer = null;
        }
        return;
    }

    if (facilitiesLayer) {
        map.removeLayer(facilitiesLayer);
        facilitiesLayer = null;
    }

    const filterRph = props.layerStates?.fasilitasRph;
    const filterPuskeswan = props.layerStates?.fasilitasPuskeswan;
    const filterPasar = props.layerStates?.fasilitasPasarHewan;

    const isRphActive = filterRph !== undefined ? Boolean(filterRph) : true;
    const isPuskeswanActive = filterPuskeswan !== undefined ? Boolean(filterPuskeswan) : true;
    const isPasarActive = filterPasar !== undefined ? Boolean(filterPasar) : true;

    if (!isRphActive && !isPuskeswanActive && !isPasarActive) {
        return;
    }

    const markers: L.Layer[] = [];
    const facilitySource: FacilityItem[] = (props.facilities && props.facilities.length > 0)
        ? props.facilities
        : BANYUMAS_FACILITIES;

    facilitySource.forEach((f) => {
        const type = resolveFacilityType(f);

        if (type === 'RPH' && !isRphActive) return;
        if (type === 'Puskeswan' && !isPuskeswanActive) return;
        if (type === 'Pasar Hewan' && !isPasarActive) return;

        const icon = createFacilityIcon(type);
        const marker = L.marker([f.lat, f.lng], { icon });

        // Bind interactive detailed popup
        marker.bindPopup(buildFacilityPopupHtml(f), {
            className: 'custom-facility-popup',
            maxWidth: 320,
        });

        const displayName = f.nama || f.nama_rph || f.nama_puskeswan || f.nama_pasar || 'Fasilitas';

        // Hover tooltip for quick identity
        marker.bindTooltip(
            `
            <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;color:#F8FAFC;text-align:center;">
                ${displayName}
                <div style="font-size:10px;color:${getFacilityColor(type)};font-weight:600;">${type} · ${f.status_operasional || 'Aktif'}</div>
            </div>
            `,
            { className: 'custom-leaflet-tooltip', offset: [0, -10] }
        );

        markers.push(marker);
    });

    facilitiesLayer = L.layerGroup(markers).addTo(map);
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
            const count =
                props.selectedMetric.id === 'pmk'
                    ? kec.pmk
                    : props.selectedMetric.id === 'lsd'
                      ? kec.lsd
                      : getKecamatanMetricValue(kec, 'kasus_total');
            if (count > 0) {
                const radius = Math.min(8 + Math.sqrt(count) * 6, 26);

                if (props.selectedMetric.id === 'lsd') {
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
                } else if (props.selectedMetric.id === 'pmk') {
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
                    const circle = L.circleMarker([kec.lat, kec.lng], {
                        radius: radius,
                        fillColor: '#DC625F',
                        fillOpacity: 0.85,
                        color: '#F5F5F5',
                        weight: 2,
                    });
                    circle.bindTooltip(`${kec.nama}: ${count} total kasus (${kec.pmk || 0} PMK, ${kec.lsd || 0} LSD)`, { className: 'custom-leaflet-tooltip' });
                    circle.on('click', () => focusKecamatan(kec));
                    markers.push(circle);
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

    const metricVal = getKecamatanMetricValue(kec, props.selectedMetric.id);
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
    () => props.facilities,
    () => syncFacilitiesLayer(),
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

/* Custom Facility Popup Styling */
.custom-facility-popup .leaflet-popup-content-wrapper {
    background: transparent !important;
    padding: 0 !important;
    box-shadow: none !important;
    border-radius: 12px !important;
}
.custom-facility-popup .leaflet-popup-content {
    margin: 0 !important;
    line-height: inherit !important;
}
.custom-facility-popup .leaflet-popup-tip-container {
    display: none !important;
}
.custom-facility-popup a.leaflet-popup-close-button {
    color: #94A3B8 !important;
    padding: 6px 8px !important;
}
</style>
