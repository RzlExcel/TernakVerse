<script setup lang="ts">
import { ref } from 'vue';
import { Download, FileSpreadsheet } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import AdminMiniMapWidget from './AdminMiniMapWidget.vue';

const { resolvedAppearance } = useAppearance();

const emit = defineEmits<{
    (e: 'openWebGis'): void;
    (e: 'open-web-gis'): void;
}>();

function emitOpenWebGis() {
    emit('openWebGis');
    emit('open-web-gis');
}

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

// 5 Kecamatan data for Col 1 Bar Chart (Baseline y=170, max=80.000, chartHeight=150)
const barItems: TopBarData[] = [
    {
        name: 'Wangon',
        value: 68203,
        formatted: '68.203',
        center: 90,
        x: 71,
        y: 42.12,
        height: 127.88,
        labelY: 34,
    },
    {
        name: 'Sumbang',
        value: 56417,
        formatted: '56.417',
        center: 172,
        x: 153,
        y: 64.22,
        height: 105.78,
        labelY: 56,
    },
    {
        name: 'Baturraden',
        value: 42781,
        formatted: '42.781',
        center: 254,
        x: 235,
        y: 89.79,
        height: 80.21,
        labelY: 82,
    },
    {
        name: 'Purwokerto Selatan',
        value: 38552,
        formatted: '38.552',
        center: 336,
        x: 317,
        y: 97.71,
        height: 72.29,
        labelY: 90,
        fontSize: '8.5',
    },
    {
        name: 'Kedungbanteng',
        value: 31246,
        formatted: '31.246',
        center: 418,
        x: 399,
        y: 111.41,
        height: 58.59,
        labelY: 103,
    },
];

function getTopRoundedBarPath(x: number, y: number, width: number, baseline: number, r: number = 4): string {
    return `M ${x} ${y + r} Q ${x} ${y} ${x + r} ${y} L ${x + width - r} ${y} Q ${x + width} ${y} ${x + width} ${y + r} L ${x + width} ${baseline} L ${x} ${baseline} Z`;
}

// Donut Chart Commodity Breakdown (Total 342.895 ekor)
interface DonutSegment {
    label: string;
    percentage: number;
    count: string;
    color: string;
}

const donutSegments: DonutSegment[] = [
    { label: 'Sapi Potong', percentage: 54, count: '185.000', color: '#00D084' },
    { label: 'Sapi Perah', percentage: 6, count: '20.642', color: '#32B9D0' },
    { label: 'Kambing', percentage: 40, count: '137.253', color: '#10543E' },
];

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

function exportBarChartCsv(): void {
    const headers = ['Peringkat', 'Kecamatan', 'Populasi_Ekor'];
    const rows = barItems.map((bar, idx) => [idx + 1, bar.name, bar.value]);
    downloadCsv('tren_populasi_kecamatan.csv', headers, rows);
}

function exportBarChartPng(): void {
    const bgColor = resolvedAppearance.value === 'dark' ? '#111111' : '#FFFFFF';
    exportSvgAsPng(barChartSvgRef.value, 'tren_populasi_kecamatan.png', bgColor);
}

function exportDonutChartCsv(): void {
    const headers = ['Komoditas', 'Persentase', 'Populasi_Ekor'];
    const rows = donutSegments.map((seg) => [
        seg.label,
        `${seg.percentage}%`,
        seg.count.replace(/\./g, ''),
    ]);
    downloadCsv('komposisi_komoditas.csv', headers, rows);
}

function exportDonutChartPng(): void {
    const bgColor = resolvedAppearance.value === 'dark' ? '#111111' : '#FFFFFF';
    exportSvgAsPng(donutChartSvgRef.value, 'komposisi_komoditas.png', bgColor);
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 w-full">
        <!-- COL 1: Tren Populasi Ternak per Kecamatan -->
        <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between w-full h-full min-h-[380px] transition-colors">
            <!-- Header & Filter Pills -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-3">
                <div>
                    <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] leading-snug">
                        Tren Populasi Ternak per Kecamatan
                    </h3>
                    <p class="text-xs dark:text-[#858585] text-[#737B71] mt-0.5">
                        5 kecamatan dengan populasi terbanyak (ekor)
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
                        <!-- Gradient Emerald for Top Bars -->
                        <linearGradient id="emeraldBarGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#00D084" />
                            <stop offset="100%" stop-color="#00A368" />
                        </linearGradient>
                    </defs>

                    <!-- Horizontal Grid lines & Y-axis labels -->
                    <!-- 80.000 -->
                    <line x1="50" y1="20" x2="445" y2="20" stroke="currentColor" class="dark:text-[#1E1E1E] text-[#E0E4DD]" stroke-width="1" stroke-dasharray="3, 4" />
                    <text x="44" y="23" text-anchor="end" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="9.5" font-family="monospace">80.000</text>

                    <!-- 60.000 -->
                    <line x1="50" y1="57.5" x2="445" y2="57.5" stroke="currentColor" class="dark:text-[#1E1E1E] text-[#E0E4DD]" stroke-width="1" stroke-dasharray="3, 4" />
                    <text x="44" y="60.5" text-anchor="end" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="9.5" font-family="monospace">60.000</text>

                    <!-- 40.000 -->
                    <line x1="50" y1="95" x2="445" y2="95" stroke="currentColor" class="dark:text-[#1E1E1E] text-[#E0E4DD]" stroke-width="1" stroke-dasharray="3, 4" />
                    <text x="44" y="98" text-anchor="end" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="9.5" font-family="monospace">40.000</text>

                    <!-- 20.000 -->
                    <line x1="50" y1="132.5" x2="445" y2="132.5" stroke="currentColor" class="dark:text-[#1E1E1E] text-[#E0E4DD]" stroke-width="1" stroke-dasharray="3, 4" />
                    <text x="44" y="135.5" text-anchor="end" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="9.5" font-family="monospace">20.000</text>

                    <!-- Baseline 0 -->
                    <line x1="50" y1="170" x2="445" y2="170" stroke="currentColor" class="dark:text-[#1E1E1E] text-[#E0E4DD]" stroke-width="1" />
                    <text x="44" y="173" text-anchor="end" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="9.5" font-family="monospace">0</text>

                    <!-- 5 Bars with Rounded Top Corners & Gradient Emerald -->
                    <g v-for="bar in barItems" :key="bar.name">
                        <!-- Top Text Value -->
                        <text
                            :x="bar.center"
                            :y="bar.labelY"
                            text-anchor="middle"
                            fill="currentColor"
                            class="dark:text-[#F5F5F5] text-[#171B17]"
                            font-size="10.5"
                            font-weight="700"
                            font-family="monospace"
                        >
                            {{ bar.formatted }}
                        </text>

                        <!-- Bar Path -->
                        <path
                            :d="getTopRoundedBarPath(bar.x, bar.y, 38, 170, 4)"
                            fill="url(#emeraldBarGradient)"
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
                <span class="text-[#00D084] font-mono">BPS &amp; Dinas Pertanian</span>
            </div>
        </div>

        <!-- COL 2: Komposisi Komoditas -->
        <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between w-full h-full min-h-[380px] transition-colors">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-2">
                <div>
                    <h3 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] leading-snug">
                        Komposisi Komoditas
                    </h3>
                    <p class="text-xs dark:text-[#858585] text-[#737B71] mt-0.5">
                        Persentase populasi ternak berdasarkan jenis
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

                            <!-- Sapi Potong (54% - 185.000) -> #00D084 -->
                            <circle
                                cx="100"
                                cy="100"
                                r="65"
                                fill="none"
                                stroke="#00D084"
                                stroke-width="22"
                                stroke-dasharray="220.54 408.41"
                                stroke-dashoffset="0"
                                class="transition-all duration-300 hover:opacity-90"
                            />

                            <!-- Sapi Perah (6% - 20.642) -> #32B9D0 -->
                            <circle
                                cx="100"
                                cy="100"
                                r="65"
                                fill="none"
                                stroke="#32B9D0"
                                stroke-width="22"
                                stroke-dasharray="24.50 408.41"
                                stroke-dashoffset="-220.54"
                                class="transition-all duration-300 hover:opacity-90"
                            />

                            <!-- Kambing (40% - 137.253) -> #10543E -->
                            <circle
                                cx="100"
                                cy="100"
                                r="65"
                                fill="none"
                                stroke="#10543E"
                                stroke-width="22"
                                stroke-dasharray="163.36 408.41"
                                stroke-dashoffset="-245.04"
                                class="transition-all duration-300 hover:opacity-90"
                            />
                        </g>

                        <!-- Center text: Total \n 342.895 \n ekor -->
                        <g>
                            <text x="100" y="89" text-anchor="middle" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="10.5" font-family="system-ui, sans-serif" font-weight="600" letter-spacing="1">TOTAL</text>
                            <text x="100" y="112" text-anchor="middle" fill="currentColor" class="dark:text-[#F5F5F5] text-[#171B17]" font-size="20" font-family="monospace" font-weight="800">342.895</text>
                            <text x="100" y="128" text-anchor="middle" fill="currentColor" class="dark:text-[#858585] text-[#737B71]" font-size="10.5" font-family="system-ui, sans-serif" font-weight="500">ekor</text>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Bottom Legend -->
            <div class="grid grid-cols-1 gap-2 pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] w-full text-xs">
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
                        <span class="dark:text-[#F5F5F5] text-[#171B17] font-medium">{{ seg.label }}</span>
                    </div>
                    <div class="font-mono tabular-nums dark:text-[#B3B3B3] text-[#444C43] text-xs">
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
            @open-web-gis="emitOpenWebGis"
            @openWebGis="emitOpenWebGis"
        />
    </div>
</template>
