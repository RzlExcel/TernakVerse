<script setup lang="ts">
import { ref, computed } from 'vue';
import { Download, FileSpreadsheet } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import AdminMiniMapWidget from './AdminMiniMapWidget.vue';
import {
    KECAMATAN_LIST,
    formatAngka,
    type KecamatanData,
} from '@/data/banyumasData';
import type { RphFacility } from '@/composables/ternakverse/admin/useAdminPortal';

export type RphItem = RphFacility;

interface Props {
    selectedCardId?: string; // 'card-populasi' | 'card-produksi' | 'card-penyakit' | 'card-fasilitas'
    kecamatanList?: KecamatanData[];
    rphList?: RphItem[];
    selectedYear?: number | 'all';
    dateRangeLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    selectedCardId: 'card-populasi',
    kecamatanList: () => [],
    rphList: () => [],
    selectedYear: 2026,
    dateRangeLabel: 'Tahun 2026 (Terbaru)',
});

const { resolvedAppearance } = useAppearance();

const emit = defineEmits<{
    (e: 'openWebGis'): void;
    (e: 'open-web-gis'): void;
}>();

function emitOpenWebGis() {
    emit('openWebGis');
    emit('open-web-gis');
}

// Normalized selectedCardId
const normalizedCardId = computed<'card-populasi' | 'card-produksi' | 'card-penyakit' | 'card-fasilitas'>(() => {
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

// Default 5 official RPH facilities in Banyumas (Tambaksogra, Sokaraja Lor, Kradenan Sumpiuh, Wangon, Ajibarang)
const DEFAULT_RPH_LIST: RphItem[] = [
    { id: 1, nama: 'Tambaksogra', kecamatan: 'Sumbang', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 50, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.3852, lng: 109.2512 },
    { id: 2, nama: 'Sokaraja Lor', kecamatan: 'Sokaraja', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 40, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.4589, lng: 109.2845 },
    { id: 3, nama: 'Kradenan Sumpiuh', kecamatan: 'Sumpiuh', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 35, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.6120, lng: 109.3541 },
    { id: 4, nama: 'Wangon', kecamatan: 'Wangon', pengelola: 'UPTD Diskanak', kapasitas: 30, jenis: 'Sapi & Kambing', status: 'Aktif Operasional', lat: -7.5187, lng: 109.0531 },
    { id: 5, nama: 'Ajibarang', kecamatan: 'Ajibarang', pengelola: 'Pemerintah Kab. Banyumas', kapasitas: 25, jenis: 'Sapi', status: 'Aktif Operasional', lat: -7.4124, lng: 109.0721 },
];

const activeKecamatanList = computed<KecamatanData[]>(() => {
    if (props.kecamatanList && props.kecamatanList.length > 0) {
        return props.kecamatanList;
    }
    return KECAMATAN_LIST;
});

const activeRphList = computed<RphItem[]>(() => {
    if (props.rphList && props.rphList.length > 0) {
        return props.rphList;
    }
    return DEFAULT_RPH_LIST;
});

// Filter pills state for Col 1
const activePeriod = ref<'7 Hari' | '30 Hari' | '12 Bulan'>('30 Hari');
const periods: Array<'7 Hari' | '30 Hari' | '12 Bulan'> = ['7 Hari', '30 Hari', '12 Bulan'];

interface TopBarData {
    name: string;
    value: number;
    formatted: string;
    center: number;
    x: number;
    y: number;
    height: number;
    labelY: number;
    fontSize?: string;
}

// Dynamic year label based on selectedYear
const yearLabel = computed(() => {
    if (props.selectedYear === 2024) return '2024';
    if (props.selectedYear === 2025) return '2025';
    if (props.selectedYear === 'all') return 'Arsip 2024-2026';
    return '2026';
});

// Col 1 Title & Subtitles
const barCardTitle = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return `Top 5 Kecamatan Produksi Pangan (${yearLabel.value})`;
        case 'card-penyakit':
            return `Top 5 Kecamatan Kasus Penyakit PMK & LSD (${yearLabel.value})`;
        case 'card-fasilitas':
            return 'Kapasitas 5 RPH Resmi & Sebaran Layanan';
        case 'card-populasi':
        default:
            return `Top 5 Kecamatan Populasi Ternak (${yearLabel.value})`;
    }
});

const barCardSubtitle = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return `5 kecamatan dengan akumulasi produksi tertinggi (${yearLabel.value})`;
        case 'card-penyakit':
            return `5 kecamatan dengan akumulasi kasus aktif PMK & LSD (${yearLabel.value})`;
        case 'card-fasilitas':
            return 'Kapasitas pemotongan harian unit RPH (ekor/hari)';
        case 'card-populasi':
        default:
            return `Tren Populasi Ternak per Kecamatan (5 wilayah terbanyak - ${yearLabel.value})`;
    }
});

const barGradientColors = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return { start: '#32B9D0', end: '#008C9E' };
        case 'card-penyakit':
            return { start: '#DC625F', end: '#B33936' };
        case 'card-fasilitas':
            return { start: '#32B9D0', end: '#00D084' };
        case 'card-populasi':
        default:
            return { start: '#00D084', end: '#00A368' };
    }
});

interface RawBarItem {
    name: string;
    value: number;
}

const rawBarData = computed<RawBarItem[]>(() => {
    if (normalizedCardId.value === 'card-fasilitas') {
        return activeRphList.value
            .slice()
            .sort((a, b) => b.kapasitas - a.kapasitas)
            .slice(0, 5)
            .map(rph => ({
                name: rph.nama.replace(/^RPH\s+/i, ''),
                value: rph.kapasitas,
            }));
    }

    if (normalizedCardId.value === 'card-penyakit') {
        return activeKecamatanList.value
            .slice()
            .map(k => ({
                name: k.nama,
                value: (k.pmk || 0) + (k.lsd || 0),
            }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 5);
    }

    if (normalizedCardId.value === 'card-produksi') {
        return activeKecamatanList.value
            .slice()
            .map(k => ({
                name: k.nama,
                value:
                    (k.telur_ayam || 0) +
                    (k.daging_ayam || 0) +
                    (k.daging_sapi || 0) +
                    (k.daging_kambing || 0) +
                    (k.daging_domba || 0) +
                    (k.susu_sapi || 0) +
                    (k.susu_kambing || 0) +
                    (k.susu_domba || 0),
            }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 5);
    }

    // Default: card-populasi (Wangon, Sumbang, Cilongok, Ajibarang, Kembaran)
    return activeKecamatanList.value
        .slice()
        .map(k => ({
            name: k.nama,
            value:
                k.populasi_total ??
                (k.ayam_potong + k.ayam_petelur + k.kambing + k.domba + k.sapi_potong + k.sapi_perah),
        }))
        .sort((a, b) => b.value - a.value)
        .slice(0, 5);
});

function computeNiceMax(maxVal: number): number {
    if (maxVal <= 0) return 100;
    if (maxVal > 150000) return Math.ceil(maxVal / 40000) * 40000;
    if (maxVal > 80000) return Math.ceil(maxVal / 20000) * 20000;
    if (maxVal > 20000) return Math.ceil(maxVal / 10000) * 10000;
    if (maxVal > 5000) return Math.ceil(maxVal / 2000) * 2000;
    if (maxVal > 1000) return Math.ceil(maxVal / 500) * 500;
    if (maxVal > 100) return Math.ceil(maxVal / 50) * 50;
    if (maxVal > 20) return Math.ceil(maxVal / 10) * 10;
    return Math.max(4, Math.ceil(maxVal / 4) * 4);
}

const gridScaleMax = computed<number>(() => {
    const maxVal = Math.max(...rawBarData.value.map(d => d.value), 0);
    return computeNiceMax(maxVal);
});

const gridLines = computed(() => {
    const maxVal = gridScaleMax.value;
    const step = maxVal / 4;
    return [
        { y: 20, label: formatAngka(Math.round(step * 4)), isBase: false },
        { y: 57.5, label: formatAngka(Math.round(step * 3)), isBase: false },
        { y: 95, label: formatAngka(Math.round(step * 2)), isBase: false },
        { y: 132.5, label: formatAngka(Math.round(step * 1)), isBase: false },
        { y: 170, label: '0', isBase: true },
    ];
});

const barItems = computed<TopBarData[]>(() => {
    const centers = [90, 172, 254, 336, 418];
    const maxVal = gridScaleMax.value;
    const chartHeight = 150; // Baseline y=170 to y=20

    return rawBarData.value.map((item, idx) => {
        const center = centers[idx] ?? 90 + idx * 82;
        const width = 38;
        const x = center - width / 2;
        const height = maxVal > 0 ? (item.value / maxVal) * chartHeight : 10;
        const y = 170 - height;
        const labelY = Math.max(16, y - 8);

        return {
            name: item.name,
            value: item.value,
            formatted: formatAngka(item.value),
            center,
            x,
            y,
            height,
            labelY,
            fontSize: item.name.length > 12 ? '8' : item.name.length > 9 ? '8.5' : '10',
        };
    });
});

function getTopRoundedBarPath(x: number, y: number, width: number, baseline: number, r: number = 4): string {
    return `M ${x} ${y + r} Q ${x} ${y} ${x + r} ${y} L ${x + width - r} ${y} Q ${x + width} ${y} ${x + width} ${y + r} L ${x + width} ${baseline} L ${x} ${baseline} Z`;
}

// ----------------------------------------------------
// COL 2: DONUT CHART COMPOSITION (DYNAMIC)
// ----------------------------------------------------
const donutCardTitle = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return `Komposisi Produksi Pangan (${yearLabel.value})`;
        case 'card-penyakit':
            return `Proporsi Kasus Penyakit Aktif (${yearLabel.value})`;
        case 'card-fasilitas':
            return 'Komposisi Fasilitas Peternakan';
        case 'card-populasi':
        default:
            return `Komposisi Komoditas Ternak (${yearLabel.value})`;
    }
});

const aggregatedMetrics = computed(() => {
    const kList = activeKecamatanList.value;
    const rList = activeRphList.value;

    // 1. Populasi
    const ayamPotong = kList.reduce((acc, k) => acc + (k.ayam_potong || k.ayam_broiler || 0), 0);
    const kambing = kList.reduce((acc, k) => acc + (k.kambing || 0), 0);
    const ayamPetelur = kList.reduce((acc, k) => acc + (k.ayam_petelur || k.ayam_layer || 0), 0);
    const domba = kList.reduce((acc, k) => acc + (k.domba || 0), 0);
    const sapiPotong = kList.reduce((acc, k) => acc + (k.sapi_potong || 0), 0);
    const sapiPerah = kList.reduce((acc, k) => acc + (k.sapi_perah || 0), 0);
    const totalPop = ayamPotong + kambing + ayamPetelur + domba + sapiPotong + sapiPerah;

    // 2. Produksi
    const dagingAyam = kList.reduce((acc, k) => acc + (k.daging_ayam || 0), 0);
    const dagingSapi = kList.reduce((acc, k) => acc + (k.daging_sapi || 0), 0);
    const dagingKambingDomba = kList.reduce((acc, k) => acc + (k.daging_kambing || 0) + (k.daging_domba || 0), 0);
    const totalDaging = dagingAyam + dagingSapi + dagingKambingDomba;
    const totalSusu = kList.reduce((acc, k) => acc + (k.susu_sapi || 0) + (k.susu_kambing || 0) + (k.susu_domba || 0), 0);
    const totalTelur = kList.reduce((acc, k) => acc + (k.telur_ayam || 0), 0);

    // 3. Penyakit
    const totalPmk = kList.reduce((acc, k) => acc + (k.pmk || 0), 0);
    const totalLsd = kList.reduce((acc, k) => acc + (k.lsd || 0), 0);
    const totalWabah = totalPmk + totalLsd;

    // 4. Fasilitas
    const rphCount = rList.length || 5;
    const poskeswanCount = kList.reduce((acc, k) => acc + (k.poskeswan || 0), 0) || 12;
    const totalFasilitas = rphCount + poskeswanCount;

    return {
        ayamPotong,
        kambing,
        ayamPetelur,
        domba,
        sapiPotong,
        sapiPerah,
        totalPop,
        dagingAyam,
        dagingSapi,
        dagingKambingDomba,
        totalDaging,
        totalSusu,
        totalTelur,
        totalPmk,
        totalLsd,
        totalWabah,
        rphCount,
        poskeswanCount,
        totalFasilitas,
    };
});

const donutCardSubtitle = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return `Plus ${formatAngka(aggregatedMetrics.value.totalSusu)} Liter Susu & ${formatAngka(aggregatedMetrics.value.totalTelur)} kg Telur (${yearLabel.value})`;
        case 'card-penyakit':
            return `Perbandingan sebaran PMK dan LSD di Banyumas (${yearLabel.value})`;
        case 'card-fasilitas':
            return 'Sebaran Poskeswan & Rumah Potong Hewan Resmi';
        case 'card-populasi':
        default:
            return `Persentase populasi ternak berdasarkan jenis (${yearLabel.value})`;
    }
});

interface DonutCenterInfo {
    label: string;
    value: string;
    unit: string;
}

const donutCenter = computed<DonutCenterInfo>(() => {
    const m = aggregatedMetrics.value;
    switch (normalizedCardId.value) {
        case 'card-produksi': {
            const val = m.totalDaging >= 1000000
                ? `${(m.totalDaging / 1000000).toFixed(2).replace('.', ',')} jt`
                : formatAngka(m.totalDaging);
            return {
                label: 'TOTAL DAGING',
                value: val,
                unit: 'kg',
            };
        }
        case 'card-penyakit':
            return {
                label: 'KASUS AKTIF',
                value: String(m.totalWabah),
                unit: 'kasus',
            };
        case 'card-fasilitas':
            return {
                label: 'TOTAL FASILITAS',
                value: String(m.totalFasilitas),
                unit: 'unit',
            };
        case 'card-populasi':
        default:
            return {
                label: 'TOTAL TERNAK',
                value: formatAngka(m.totalPop),
                unit: 'ekor',
            };
    }
});

interface RawDonutSegment {
    label: string;
    percentage: number;
    count: string;
    color: string;
    rawCount: number;
}

const rawDonutSegments = computed<RawDonutSegment[]>(() => {
    const m = aggregatedMetrics.value;
    switch (normalizedCardId.value) {
        case 'card-produksi': {
            const tot = m.totalDaging;
            return [
                {
                    label: 'Daging Ayam',
                    percentage: tot > 0 ? Math.round((m.dagingAyam / tot) * 1000) / 10 : 0,
                    count: `${formatAngka(m.dagingAyam)} kg`,
                    color: '#00D084',
                    rawCount: m.dagingAyam,
                },
                {
                    label: 'Daging Sapi',
                    percentage: tot > 0 ? Math.round((m.dagingSapi / tot) * 1000) / 10 : 0,
                    count: `${formatAngka(m.dagingSapi)} kg`,
                    color: '#32B9D0',
                    rawCount: m.dagingSapi,
                },
                {
                    label: 'Daging Kambing & Domba',
                    percentage: tot > 0 ? Math.round((m.dagingKambingDomba / tot) * 1000) / 10 : 0,
                    count: `${formatAngka(m.dagingKambingDomba)} kg`,
                    color: '#86A65A',
                    rawCount: m.dagingKambingDomba,
                },
            ];
        }
        case 'card-penyakit': {
            const tot = m.totalWabah;
            return [
                {
                    label: 'Kasus Aktif PMK',
                    percentage: tot > 0 ? Math.round((m.totalPmk / tot) * 1000) / 10 : 0,
                    count: `${m.totalPmk} kasus`,
                    color: '#DC625F',
                    rawCount: m.totalPmk,
                },
                {
                    label: 'Kasus Aktif LSD',
                    percentage: tot > 0 ? Math.round((m.totalLsd / tot) * 1000) / 10 : 0,
                    count: `${m.totalLsd} kasus`,
                    color: '#E8B84C',
                    rawCount: m.totalLsd,
                },
            ];
        }
        case 'card-fasilitas': {
            const tot = m.totalFasilitas;
            return [
                {
                    label: 'Poskeswan',
                    percentage: tot > 0 ? Math.round((m.poskeswanCount / tot) * 1000) / 10 : 0,
                    count: `${m.poskeswanCount} unit`,
                    color: '#32B9D0',
                    rawCount: m.poskeswanCount,
                },
                {
                    label: 'RPH Resmi',
                    percentage: tot > 0 ? Math.round((m.rphCount / tot) * 1000) / 10 : 0,
                    count: `${m.rphCount} unit`,
                    color: '#00D084',
                    rawCount: m.rphCount,
                },
            ];
        }
        case 'card-populasi':
        default: {
            const tot = m.totalPop;
            return [
                {
                    label: 'Ayam Broiler/Potong',
                    percentage: tot > 0 ? Math.round((m.ayamPotong / tot) * 1000) / 10 : 0,
                    count: formatAngka(m.ayamPotong),
                    color: '#00D084',
                    rawCount: m.ayamPotong,
                },
                {
                    label: 'Kambing',
                    percentage: tot > 0 ? Math.round((m.kambing / tot) * 1000) / 10 : 0,
                    count: formatAngka(m.kambing),
                    color: '#32B9D0',
                    rawCount: m.kambing,
                },
                {
                    label: 'Ayam Petelur/Layer',
                    percentage: tot > 0 ? Math.round((m.ayamPetelur / tot) * 1000) / 10 : 0,
                    count: formatAngka(m.ayamPetelur),
                    color: '#10543E',
                    rawCount: m.ayamPetelur,
                },
                {
                    label: 'Domba',
                    percentage: tot > 0 ? Math.round((m.domba / tot) * 1000) / 10 : 0,
                    count: formatAngka(m.domba),
                    color: '#86A65A',
                    rawCount: m.domba,
                },
                {
                    label: 'Sapi Potong',
                    percentage: tot > 0 ? Math.round((m.sapiPotong / tot) * 1000) / 10 : 0,
                    count: formatAngka(m.sapiPotong),
                    color: '#D4A34E',
                    rawCount: m.sapiPotong,
                },
                {
                    label: 'Sapi Perah',
                    percentage: tot > 0 ? Math.round((m.sapiPerah / tot) * 1000) / 10 : 0,
                    count: formatAngka(m.sapiPerah),
                    color: '#D9784D',
                    rawCount: m.sapiPerah,
                },
            ];
        }
    }
});

const CIRCUMFERENCE = 2 * Math.PI * 65; // ~408.407

export interface DonutSegment {
    label: string;
    percentage: number;
    count: string;
    color: string;
    dashArray: string;
    dashOffset: string;
}

const donutSegments = computed<DonutSegment[]>(() => {
    const raw = rawDonutSegments.value;
    const totalRaw = raw.reduce((sum, item) => sum + item.rawCount, 0);

    let currentOffset = 0;
    return raw.map((item) => {
        const fraction = totalRaw > 0 ? item.rawCount / totalRaw : 0;
        const arcLen = fraction * CIRCUMFERENCE;
        const dashArray = `${arcLen.toFixed(2)} ${CIRCUMFERENCE.toFixed(2)}`;
        const dashOffset = `-${currentOffset.toFixed(2)}`;
        currentOffset += arcLen;

        return {
            label: item.label,
            percentage: item.percentage,
            count: item.count,
            color: item.color,
            dashArray,
            dashOffset,
        };
    });
});

// SVG element references for PNG export
const barChartSvgRef = ref<SVGSVGElement | null>(null);
const donutChartSvgRef = ref<SVGSVGElement | null>(null);

function sanitizeCsvCell(val: unknown): string {
    const str = String(val ?? '');
    const escaped = str.replace(/"/g, '""');
    if (/^[=+\-@\t\r]/.test(escaped)) {
        return `"'${escaped}"`;
    }
    return `"${escaped}"`;
}

function downloadCsv(filename: string, headers: string[], rows: (string | number)[][]): void {
    const bom = '\uFEFF';
    const csvContent = [
        headers.map(sanitizeCsvCell).join(','),
        ...rows.map((row) => row.map(sanitizeCsvCell).join(',')),
    ].join('\r\n');

    const blob = new Blob([bom + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function exportSvgAsPng(svgEl: SVGSVGElement | null, filename: string, bgColor: string = '#111111'): void {
    if (!svgEl || typeof window === 'undefined') return;

    const bbox = svgEl.getBoundingClientRect();
    const width = svgEl.viewBox?.baseVal?.width || bbox.width || 460;
    const height = svgEl.viewBox?.baseVal?.height || bbox.height || 210;

    const serializer = new XMLSerializer();
    let svgString = serializer.serializeToString(svgEl);

    if (!svgString.includes('xmlns="http://www.w3.org/2000/svg"')) {
        svgString = svgString.replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"');
    }

    const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);
    const img = new Image();

    img.onload = () => {
        const canvas = document.createElement('canvas');
        const scale = 2;
        canvas.width = width * scale;
        canvas.height = height * scale;
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.fillStyle = bgColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const pngUrl = canvas.toDataURL('image/png');
            const downloadLink = document.createElement('a');
            downloadLink.href = pngUrl;
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        URL.revokeObjectURL(url);
    };

    img.onerror = () => {
        URL.revokeObjectURL(url);
    };

    img.src = url;
}

const barCategoryLabel = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-fasilitas':
            return 'Unit_RPH';
        default:
            return 'Kecamatan';
    }
});

const barUnitLabel = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return 'Produksi_Kg_Liter';
        case 'card-penyakit':
            return 'Kasus_Aktif';
        case 'card-fasilitas':
            return 'Kapasitas_Ekor_Hari';
        case 'card-populasi':
        default:
            return 'Populasi_Ekor';
    }
});

const barChartExportFilename = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return 'tren_produksi_kecamatan';
        case 'card-penyakit':
            return 'tren_penyakit_kecamatan';
        case 'card-fasilitas':
            return 'kapasitas_rph_banyumas';
        case 'card-populasi':
        default:
            return 'tren_populasi_kecamatan';
    }
});

function exportBarChartCsv(): void {
    const headers = ['Peringkat', barCategoryLabel.value, barUnitLabel.value];
    const rows = barItems.value.map((bar, idx) => [idx + 1, bar.name, bar.value]);
    downloadCsv(`${barChartExportFilename.value}.csv`, headers, rows);
}

function exportBarChartPng(): void {
    const bgColor = resolvedAppearance.value === 'dark' ? '#111111' : '#FFFFFF';
    exportSvgAsPng(barChartSvgRef.value, `${barChartExportFilename.value}.png`, bgColor);
}

const donutChartExportFilename = computed(() => {
    switch (normalizedCardId.value) {
        case 'card-produksi':
            return 'komposisi_produksi_pangan';
        case 'card-penyakit':
            return 'komposisi_kasus_penyakit';
        case 'card-fasilitas':
            return 'komposisi_fasilitas_peternakan';
        case 'card-populasi':
        default:
            return 'komposisi_komoditas_ternak';
    }
});

function exportDonutChartCsv(): void {
    const headers = ['Komposisi', 'Persentase', 'Jumlah'];
    const rows = donutSegments.value.map((seg) => [
        seg.label,
        `${seg.percentage}%`,
        seg.count.replace(/\./g, ''),
    ]);
    downloadCsv(`${donutChartExportFilename.value}.csv`, headers, rows);
}

function exportDonutChartPng(): void {
    const bgColor = resolvedAppearance.value === 'dark' ? '#111111' : '#FFFFFF';
    exportSvgAsPng(donutChartSvgRef.value, `${donutChartExportFilename.value}.png`, bgColor);
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 w-full">
        <!-- COL 1: Tren / Top 5 Bar Chart -->
        <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between w-full h-full min-h-[380px] transition-colors">
            <!-- Header & Filter Pills -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-3">
                <div>
                    <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] leading-snug">
                        {{ barCardTitle }}
                    </h3>
                    <p class="text-xs dark:text-[#858585] text-[#737B71] mt-0.5">
                        {{ barCardSubtitle }}
                    </p>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    <!-- Filter Pills: 7 Hari, 30 Hari, 12 Bulan -->
                    <div class="flex items-center gap-1 dark:bg-[#181818] bg-[#F4F5F2] p-1 rounded-lg border dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] font-mono tabular-nums shrink-0">
                        <button
                            v-for="p in periods"
                            :key="p"
                            type="button"
                            @click="activePeriod = p"
                            class="px-2 py-0.5 rounded transition-colors cursor-pointer"
                            :class="
                                activePeriod === p
                                    ? 'bg-[#171B17] text-white font-bold dark:bg-[#181818] dark:text-[#00D084] shadow-xs'
                                    : 'bg-white text-[#444C43] border border-[#E0E4DD] hover:bg-[#EDEFEB] dark:bg-[#111111] dark:text-[#B3B3B3] dark:border-[#1E1E1E] hover:dark:bg-[#1E1E1E] hover:dark:text-[#F5F5F5]'
                            "
                        >
                            {{ p }}
                        </button>
                    </div>

                    <!-- Export Actions -->
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            type="button"
                            @click="exportBarChartCsv"
                            title="Ekspor CSV"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md dark:bg-[#181818] bg-[#F4F5F2] hover:dark:bg-[#1E1E1E] hover:bg-[#EDEFEB] dark:text-[#F5F5F5] text-[#171B17] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] font-semibold transition-colors cursor-pointer"
                        >
                            <FileSpreadsheet class="w-3 h-3 text-[#00D084]" />
                            <span>Ekspor CSV</span>
                        </button>
                        <button
                            type="button"
                            @click="exportBarChartPng"
                            title="Unduh PNG"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md dark:bg-[#181818] bg-[#F4F5F2] hover:dark:bg-[#1E1E1E] hover:bg-[#EDEFEB] dark:text-[#F5F5F5] text-[#171B17] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] font-semibold transition-colors cursor-pointer"
                        >
                            <Download class="w-3 h-3 text-[#32B9D0]" />
                            <span>Unduh PNG</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Vertical Bar Chart (SVG) -->
            <div class="relative w-full flex-1 flex items-center justify-center py-2 min-h-[220px]">
                <svg
                    ref="barChartSvgRef"
                    class="w-full h-full"
                    viewBox="0 0 460 210"
                    preserveAspectRatio="xMidYMid meet"
                >
                    <defs>
                        <!-- Dynamic Gradient for Top Bars -->
                        <linearGradient id="dynamicBarGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" :stop-color="barGradientColors.start" />
                            <stop offset="100%" :stop-color="barGradientColors.end" />
                        </linearGradient>
                    </defs>

                    <!-- Horizontal Grid lines & Y-axis labels -->
                    <g v-for="grid in gridLines" :key="grid.y">
                        <line
                            x1="50"
                            :y1="grid.y"
                            x2="445"
                            :y2="grid.y"
                            stroke="currentColor"
                            class="dark:text-[#1E1E1E] text-[#E0E4DD]"
                            :stroke-width="1"
                            :stroke-dasharray="grid.isBase ? undefined : '3, 4'"
                        />
                        <text
                            x="44"
                            :y="grid.y + 3"
                            text-anchor="end"
                            fill="currentColor"
                            class="dark:text-[#858585] text-[#737B71]"
                            font-size="9.5"
                            font-family="monospace"
                        >
                            {{ grid.label }}
                        </text>
                    </g>

                    <!-- 5 Bars with Rounded Top Corners & Dynamic Gradient -->
                    <g v-for="bar in barItems" :key="bar.name">
                        <!-- Top Text Value -->
                        <text
                            :x="bar.center"
                            :y="bar.labelY"
                            text-anchor="middle"
                            fill="currentColor"
                            class="dark:text-[#F5F5F5] text-[#171B17]"
                            font-size="10"
                            font-weight="700"
                            font-family="monospace"
                        >
                            {{ bar.formatted }}
                        </text>

                        <!-- Bar Path -->
                        <path
                            :d="getTopRoundedBarPath(bar.x, bar.y, 38, 170, 4)"
                            fill="url(#dynamicBarGradient)"
                            class="transition-opacity hover:opacity-90 cursor-pointer"
                        />

                        <!-- X-axis Label -->
                        <text
                            :x="bar.center"
                            y="190"
                            text-anchor="middle"
                            fill="currentColor"
                            class="dark:text-[#858585] text-[#737B71]"
                            :font-size="bar.fontSize ?? '10'"
                            font-family="system-ui, sans-serif"
                        >
                            {{ bar.name }}
                        </text>
                    </g>
                </svg>
            </div>

            <!-- Footer note / bottom padding -->
            <div class="pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-between text-[11px] dark:text-[#858585] text-[#737B71]">
                <span>Diperbarui secara berkala</span>
                <span class="text-[#00D084] font-mono">BPS &amp; Dinas Pertanian ({{ yearLabel }})</span>
            </div>
        </div>

        <!-- COL 2: Donut Chart Komposisi -->
        <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between w-full h-full min-h-[380px] transition-colors">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-2">
                <div>
                    <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] leading-snug">
                        {{ donutCardTitle }}
                    </h3>
                    <p class="text-xs dark:text-[#858585] text-[#737B71] mt-0.5">
                        {{ donutCardSubtitle }}
                    </p>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <button
                        type="button"
                        @click="exportDonutChartCsv"
                        title="Ekspor CSV"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md dark:bg-[#181818] bg-[#F4F5F2] hover:dark:bg-[#1E1E1E] hover:bg-[#EDEFEB] dark:text-[#F5F5F5] text-[#171B17] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] font-semibold transition-colors cursor-pointer"
                    >
                        <FileSpreadsheet class="w-3 h-3 text-[#00D084]" />
                        <span>Ekspor CSV</span>
                    </button>
                    <button
                        type="button"
                        @click="exportDonutChartPng"
                        title="Unduh PNG"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md dark:bg-[#181818] bg-[#F4F5F2] hover:dark:bg-[#1E1E1E] hover:bg-[#EDEFEB] dark:text-[#F5F5F5] text-[#171B17] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] font-semibold transition-colors cursor-pointer"
                    >
                        <Download class="w-3 h-3 text-[#32B9D0]" />
                        <span>Unduh PNG</span>
                    </button>
                </div>
            </div>

            <!-- Donut Chart Center -->
            <div class="relative flex-1 flex flex-col items-center justify-center py-2">
                <div class="relative w-44 h-44 flex items-center justify-center">
                    <svg ref="donutChartSvgRef" class="w-full h-full" viewBox="0 0 200 200">
                        <g transform="rotate(-90 100 100)">
                            <!-- Background track ring -->
                            <circle
                                cx="100"
                                cy="100"
                                r="65"
                                fill="none"
                                stroke="currentColor"
                                class="dark:text-[#181818] text-[#F4F5F2]"
                                stroke-width="22"
                            />

                            <!-- Dynamic Donut Segments (100% normalized circle fill) -->
                            <circle
                                v-for="seg in donutSegments"
                                :key="seg.label"
                                cx="100"
                                cy="100"
                                r="65"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="22"
                                :stroke-dasharray="seg.dashArray"
                                :stroke-dashoffset="seg.dashOffset"
                                class="transition-all duration-300 hover:opacity-90"
                            />
                        </g>

                        <!-- Center text: Dynamic Total / Value / Unit -->
                        <g>
                            <text
                                x="100"
                                y="89"
                                text-anchor="middle"
                                fill="currentColor"
                                class="dark:text-[#858585] text-[#737B71]"
                                font-size="10"
                                font-family="system-ui, sans-serif"
                                font-weight="600"
                                letter-spacing="1"
                            >
                                {{ donutCenter.label }}
                            </text>
                            <text
                                x="100"
                                y="112"
                                text-anchor="middle"
                                fill="currentColor"
                                class="dark:text-[#F5F5F5] text-[#171B17]"
                                :font-size="donutCenter.value.length > 7 ? '17' : '20'"
                                font-family="monospace"
                                font-weight="800"
                            >
                                {{ donutCenter.value }}
                            </text>
                            <text
                                x="100"
                                y="128"
                                text-anchor="middle"
                                fill="currentColor"
                                class="dark:text-[#858585] text-[#737B71]"
                                font-size="10.5"
                                font-family="system-ui, sans-serif"
                                font-weight="500"
                            >
                                {{ donutCenter.unit }}
                            </text>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Bottom Legend -->
            <div class="grid grid-cols-1 gap-1.5 pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] w-full text-xs max-h-[140px] overflow-y-auto pr-1">
                <div
                    v-for="seg in donutSegments"
                    :key="seg.label"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="w-2.5 h-2.5 rounded-full shrink-0 shadow-xs"
                            :style="{ backgroundColor: seg.color }"
                        ></span>
                        <span class="dark:text-[#F5F5F5] text-[#171B17] font-medium text-[11px]">{{ seg.label }}</span>
                    </div>
                    <div class="font-mono tabular-nums dark:text-[#B3B3B3] text-[#444C43] text-[11px]">
                        <span class="font-semibold" :style="{ color: seg.color }">
                            {{ seg.percentage }}%
                        </span>
                        <span class="dark:text-[#858585] text-[#737B71] mx-1.5">·</span>
                        <span>{{ seg.count }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- COL 3: Mini Map Widget -->
        <AdminMiniMapWidget
            :selected-card-id="normalizedCardId"
            :kecamatan-list="activeKecamatanList"
            @open-web-gis="emitOpenWebGis"
            @openWebGis="emitOpenWebGis"
        />
    </div>
</template>
