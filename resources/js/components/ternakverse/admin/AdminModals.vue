<script setup lang="ts">
import { ref, watch } from 'vue';
import {
    Upload,
    X,
    Download,
    FileText,
    FileDown,
    SlidersHorizontal,
    Check,
    FileSpreadsheet,
    AlertCircle,
    ArrowRight,
    FileCheck,
} from 'lucide-vue-next';

const props = defineProps<{
    isImportModalOpen: boolean;
    isExportModalOpen: boolean;
    isFilterModalOpen: boolean;
    selectedRowCount: number;
    filteredCount: number;
    selectedWabahFilter: string;
    selectedPoskeswanFilter: string;
    minPopulasiFilter: number;
    toastMsg: string | null;
}>();

const emit = defineEmits<{
    (e: 'update:isImportModalOpen', val: boolean): void;
    (e: 'update:isExportModalOpen', val: boolean): void;
    (e: 'update:isFilterModalOpen', val: boolean): void;
    (e: 'update:selectedWabahFilter', val: string): void;
    (e: 'update:selectedPoskeswanFilter', val: string): void;
    (e: 'update:minPopulasiFilter', val: number): void;
    (e: 'importSuccess', parsedRows?: Record<string, unknown>[]): void;
    (e: 'exportData', format: 'excel' | 'csv'): void;
    (e: 'resetFilters'): void;
}>();

// ========================================================
// INTELLIGENT VARIABLE ALIAS MAPPING DICTIONARY
// ========================================================
const VARIABLE_ALIASES: Record<string, string[]> = {
    nama: ['kecamatan', 'nama_kecamatan', 'nama', 'wilayah', 'distrik', 'subdistrict', 'kec'],
    kode_kec: ['kode', 'kode_kecamatan', 'kode_wilayah', 'kode_kec', 'kdcpum'],
    sapi_potong: ['sapi potong', 'sapi_potong', 'potong', 'beef cattle', 'sapi pedaging', 'populasi sapi potong', 'sapi'],
    sapi_perah: ['sapi perah', 'sapi_perah', 'perah', 'dairy cow', 'dairy cattle', 'populasi sapi perah'],
    kambing: ['kambing', 'goat', 'kambing_ekor', 'populasi kambing'],
    domba: ['domba', 'sheep', 'biri-biri', 'domba_ekor', 'populasi domba'],
    ayam_potong: ['ayam broiler', 'ayam potong', 'ayam pedaging', 'broiler', 'ayam_potong', 'ayam_broiler'],
    ayam_layer: ['ayam petelur', 'ayam layer', 'layer', 'ayam_layer', 'ayam_telur'],
    daging_sapi: ['daging', 'produksi daging', 'meat', 'daging_sapi', 'total_daging'],
    susu_liter: ['susu', 'produksi susu', 'milk', 'susu_liter', 'total_susu'],
    pmk: ['pmk', 'kasus pmk', 'fmd', 'wabah pmk'],
    lsd: ['lsd', 'kasus lsd', 'lumpy skin', 'wabah lsd'],
    poskeswan: ['poskeswan', 'unit poskeswan', 'pos kesehatan hewan'],
};

interface MappedVariable {
    header: string;
    canonicalKey: string;
    sampleValue: string;
}

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const selectedFileName = ref('');
const selectedFileSize = ref('');
const detectedRowsCount = ref(0);
const mappedVariables = ref<MappedVariable[]>([]);
const unmappedHeaders = ref<string[]>([]);
const parsedRows = ref<Record<string, unknown>[]>([]);
const importErrorMessage = ref<string | null>(null);

function normalizeStr(str: string): string {
    return str
        .trim()
        .toLowerCase()
        .replace(/['"]/g, '')
        .replace(/[_\-.]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function findCanonicalKey(header: string): string | null {
    const clean = normalizeStr(header);
    if (!clean) return null;

    // 1. Direct match with canonical key or alias
    for (const [canonicalKey, aliases] of Object.entries(VARIABLE_ALIASES)) {
        if (clean === canonicalKey.replace(/_/g, ' ') || clean === canonicalKey) {
            return canonicalKey;
        }
        for (const alias of aliases) {
            const cleanAlias = normalizeStr(alias);
            if (clean === cleanAlias) {
                return canonicalKey;
            }
        }
    }

    // 2. Contains match - pick the longest matching alias
    let bestMatch: { key: string; aliasLen: number } | null = null;
    for (const [canonicalKey, aliases] of Object.entries(VARIABLE_ALIASES)) {
        for (const alias of aliases) {
            const cleanAlias = normalizeStr(alias);
            if (clean.includes(cleanAlias)) {
                if (!bestMatch || cleanAlias.length > bestMatch.aliasLen) {
                    bestMatch = { key: canonicalKey, aliasLen: cleanAlias.length };
                }
            }
        }
    }

    return bestMatch ? bestMatch.key : null;
}

function parseCsvLine(line: string, delimiter: string): string[] {
    const result: string[] = [];
    let current = '';
    let inQuotes = false;
    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        if (char === '"' || char === "'") {
            if (inQuotes && line[i + 1] === char) {
                current += char;
                i++;
            } else {
                inQuotes = !inQuotes;
            }
        } else if (char === delimiter && !inQuotes) {
            result.push(current.trim());
            current = '';
        } else {
            current += char;
        }
    }
    result.push(current.trim());
    return result;
}

function parseCellValue(raw: string, key: string): string | number {
    const trimmed = raw.trim().replace(/^["']|["']$/g, '');
    if (key === 'nama' || key === 'kode_kec') {
        return trimmed;
    }
    const cleaned = trimmed.replace(/[^0-9.,-]/g, '').replace(/,/g, '');
    const num = Number(cleaned);
    return Number.isNaN(num) || cleaned === '' ? trimmed : num;
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function processFileContent(content: string, fileName: string, fileSize: number): void {
    importErrorMessage.value = null;
    const cleanContent = content.replace(/^\uFEFF/, '').trim();
    if (!cleanContent) {
        importErrorMessage.value = 'Berkas kosong atau tidak memuat data yang valid.';
        return;
    }

    const rawLines = cleanContent
        .split(/\r?\n/)
        .map(l => l.trim())
        .filter(l => l.length > 0);

    if (rawLines.length < 2) {
        importErrorMessage.value = 'Berkas minimal harus memiliki 1 baris tajuk kolom (header) dan 1 baris data.';
        return;
    }

    const headerLine = rawLines[0]!;
    // Delimiter detection: comma, semicolon, tab
    const commaCount = (headerLine.match(/,/g) || []).length;
    const semiCount = (headerLine.match(/;/g) || []).length;
    const tabCount = (headerLine.match(/\t/g) || []).length;
    let delimiter = ',';
    if (semiCount > commaCount && semiCount >= tabCount) delimiter = ';';
    else if (tabCount > commaCount && tabCount > semiCount) delimiter = '\t';

    const rawHeaders = parseCsvLine(headerLine, delimiter);
    const mapping: { colIdx: number; rawHeader: string; canonicalKey: string }[] = [];
    const unmapped: string[] = [];

    rawHeaders.forEach((h, idx) => {
        const canonical = findCanonicalKey(h);
        if (canonical) {
            mapping.push({ colIdx: idx, rawHeader: h, canonicalKey: canonical });
        } else {
            unmapped.push(h);
        }
    });

    if (mapping.length === 0) {
        importErrorMessage.value = 'Tidak ada variabel atau kolom yang cocok dengan skema basis data TernakVerse.';
        return;
    }

    const rows: Record<string, unknown>[] = [];
    for (let i = 1; i < rawLines.length; i++) {
        const lineCells = parseCsvLine(rawLines[i]!, delimiter);
        if (lineCells.length === 0 || (lineCells.length === 1 && !lineCells[0])) continue;
        const rowObj: Record<string, unknown> = {};
        mapping.forEach(m => {
            const val = lineCells[m.colIdx] ?? '';
            rowObj[m.canonicalKey] = parseCellValue(val, m.canonicalKey);
        });
        rows.push(rowObj);
    }

    if (rows.length === 0) {
        importErrorMessage.value = 'Tidak ditemukan baris data yang valid pada berkas ini.';
        return;
    }

    selectedFileName.value = fileName;
    selectedFileSize.value = formatBytes(fileSize);
    detectedRowsCount.value = rows.length;
    mappedVariables.value = mapping.map(m => ({
        header: m.rawHeader,
        canonicalKey: m.canonicalKey,
        sampleValue: rows[0]?.[m.canonicalKey] !== undefined ? String(rows[0][m.canonicalKey]) : '-',
    }));
    unmappedHeaders.value = unmapped;
    parsedRows.value = rows;
}

function handleFile(file: File): void {
    const validExtensions = ['.csv', '.txt', '.tsv'];
    const hasValidExt = validExtensions.some(ext => file.name.toLowerCase().endsWith(ext));
    if (!hasValidExt) {
        importErrorMessage.value = 'Format berkas tidak didukung. Harap unggah berkas .csv, .txt, atau .tsv.';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        const text = (e.target?.result as string) || '';
        processFileContent(text, file.name, file.size);
    };
    reader.onerror = () => {
        importErrorMessage.value = 'Gagal membaca berkas. Pastikan berkas tidak rusak atau terkunci.';
    };
    reader.readAsText(file);
}

function onFileSelected(e: Event): void {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        handleFile(target.files[0]!);
    }
}

function onFileDrop(e: DragEvent): void {
    isDragging.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
        handleFile(e.dataTransfer.files[0]!);
    }
}

function triggerFileInput(): void {
    fileInputRef.value?.click();
}

function resetImportState(): void {
    selectedFileName.value = '';
    selectedFileSize.value = '';
    detectedRowsCount.value = 0;
    mappedVariables.value = [];
    unmappedHeaders.value = [];
    parsedRows.value = [];
    importErrorMessage.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

function submitImport(): void {
    if (parsedRows.value.length === 0) {
        emit('importSuccess');
    } else {
        emit('importSuccess', parsedRows.value);
    }
    emit('update:isImportModalOpen', false);
    resetImportState();
}

watch(
    () => props.isImportModalOpen,
    (isOpen) => {
        if (!isOpen) {
            resetImportState();
        }
    }
);
</script>

<template>
    <!-- ==================================================== -->
    <!-- MODAL DIALOG IMPOR CSV/EXCEL                         -->
    <!-- ==================================================== -->
    <div
        v-if="props.isImportModalOpen"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 select-none"
        @click.self="emit('update:isImportModalOpen', false)"
    >
        <div class="bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#292929] rounded-2xl p-6 w-full max-w-lg shadow-2xl text-[#171B17] dark:text-[#F5F5F5] space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 dark:text-emerald-400">
                        <Upload class="w-4 h-4" />
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5] leading-none">
                            Impor Data CSV / Excel
                        </h3>
                        <span class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] mt-0.5 block">
                            Smart Variable Alias Mapping &bull; Sinkronisasi Otomatis
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('update:isImportModalOpen', false)"
                    class="w-8 h-8 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-emerald-500/40 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup modal impor"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- HIDDEN FILE INPUT -->
            <input
                type="file"
                ref="fileInputRef"
                accept=".csv,.txt,.tsv"
                class="hidden"
                @change="onFileSelected"
            />

            <!-- ERROR ALERT -->
            <div
                v-if="importErrorMessage"
                class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2.5"
            >
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                <span>{{ importErrorMessage }}</span>
            </div>

            <!-- STATE 1: NO FILE LOADED YET -->
            <div v-if="!selectedFileName" class="space-y-3">
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Unggah berkas lembar kerja rekapitulasi data peternakan (.csv atau .tsv). Sistem secara cerdas memetakan nama kolom/variabel yang berbeda ke skema basis data.
                </p>

                <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="onFileDrop"
                    @click="triggerFileInput"
                    :class="[
                        'border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all',
                        isDragging
                            ? 'border-emerald-500 bg-emerald-500/10'
                            : 'border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-emerald-500/50 bg-[#F4F5F2] dark:bg-[#111111]'
                    ]"
                >
                    <Upload class="w-8 h-8 text-emerald-500 dark:text-emerald-400 mx-auto mb-2" />
                    <span class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5] block">
                        Tarik berkas ke sini atau klik untuk memilih
                    </span>
                    <span class="text-[10px] text-[#6E776D] dark:text-[#B3B3B3] block mt-1 font-mono tabular-nums">
                        Mendukung format .CSV, .TSV, .TXT (Maks 10 MB)
                    </span>
                    <button
                        type="button"
                        class="mt-3 px-3.5 py-1.5 rounded-lg bg-emerald-600/15 hover:bg-emerald-600/25 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold transition-colors inline-flex items-center gap-1.5"
                    >
                        <FileSpreadsheet class="w-3.5 h-3.5" />
                        <span>Pilih Berkas</span>
                    </button>
                </div>

                <div class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-3 text-[11px] text-[#6E776D] dark:text-[#B3B3B3] space-y-1">
                    <div class="font-bold text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-1.5">
                        <Check class="w-3.5 h-3.5 text-emerald-500" />
                        <span>Fitur Smart Variable Alias Mapping:</span>
                    </div>
                    <p class="leading-relaxed">
                        Data dengan nama kolom berbeda seperti <em>"sapi pedaging"</em>, <em>"wilayah"</em>, <em>"kasus pmk"</em>, atau <em>"broiler"</em> akan otomatis terdeteksi &amp; tersinkronisasi ke 27 kecamatan.
                    </p>
                </div>
            </div>

            <!-- STATE 2: FILE LOADED & PREVIEW READY -->
            <div v-else class="space-y-3.5">
                <!-- File header bar -->
                <div class="p-3 bg-[#F4F5F2] dark:bg-[#111111] border border-emerald-500/30 rounded-xl flex items-center justify-between">
                    <div class="flex items-center gap-2.5 truncate">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-500 flex-shrink-0">
                            <FileCheck class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5] truncate">{{ selectedFileName }}</div>
                            <div class="text-[10px] text-[#6E776D] dark:text-[#B3B3B3] font-mono">{{ selectedFileSize }} &bull; {{ detectedRowsCount }} baris data</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="resetImportState"
                        class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 hover:underline px-2 py-1 cursor-pointer flex-shrink-0"
                    >
                        Ganti Berkas
                    </button>
                </div>

                <!-- Mapped variables chip display -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-[#171B17] dark:text-[#F5F5F5]">
                            {{ mappedVariables.length }} Variabel Terpetakan Otomatis:
                        </span>
                        <span class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400">
                            Smart Mapping Active
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto p-2.5 bg-[#F4F5F2] dark:bg-[#111111] rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <div
                            v-for="mv in mappedVariables"
                            :key="mv.canonicalKey + mv.header"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] bg-white dark:bg-[#181818] border border-emerald-500/30 text-[#171B17] dark:text-[#F5F5F5] font-mono shadow-2xs"
                        >
                            <span class="text-[#6E776D] dark:text-[#B3B3B3]">'{{ mv.header }}'</span>
                            <ArrowRight class="w-3 h-3 text-emerald-500 flex-shrink-0" />
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ mv.canonicalKey }}</span>
                        </div>
                    </div>
                </div>

                <!-- Unmapped headers note if any -->
                <div v-if="unmappedHeaders.length > 0" class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] flex items-center flex-wrap gap-1.5">
                    <span>Kolom diabaikan:</span>
                    <span
                        v-for="uh in unmappedHeaders"
                        :key="uh"
                        class="px-1.5 py-0.5 rounded bg-zinc-200/70 dark:bg-zinc-800 text-[10px] font-mono text-zinc-600 dark:text-zinc-400"
                    >
                        {{ uh }}
                    </span>
                </div>

                <!-- Sample first row preview -->
                <div v-if="parsedRows.length > 0" class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-2.5 space-y-1">
                    <span class="text-[10px] uppercase tracking-wider font-bold text-[#6E776D] dark:text-[#B3B3B3] block">
                        Pratinjau Sampel Data (Baris 1):
                    </span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 text-[11px] font-mono">
                        <div
                            v-for="(val, key) in parsedRows[0]"
                            :key="String(key)"
                            class="p-1 rounded bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] truncate"
                        >
                            <span class="text-[#6E776D] dark:text-[#B3B3B3] text-[9px] block">{{ key }}</span>
                            <span class="font-semibold text-[#171B17] dark:text-[#F5F5F5]">{{ val }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER BUTTONS -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                <button
                    type="button"
                    @click="emit('update:isImportModalOpen', false)"
                    class="h-10 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:bg-[#F4F5F2] dark:hover:bg-[#111111] transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="submitImport"
                    class="h-10 px-5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 transition-colors cursor-pointer shadow-sm"
                >
                    <Upload class="w-4 h-4" />
                    <span>{{ selectedFileName ? 'Unggah & Sinkronkan (' + detectedRowsCount + ' Baris)' : 'Unggah & Sinkronkan' }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- MODAL DIALOG EKSPOR CSV/EXCEL                        -->
    <!-- ==================================================== -->
    <div
        v-if="props.isExportModalOpen"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 select-none"
        @click.self="emit('update:isExportModalOpen', false)"
    >
        <div class="bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#292929] rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-4 font-ui text-[#171B17] dark:text-[#F5F5F5]">
            <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 dark:text-emerald-400">
                        <Download class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5] leading-none">
                            Ekspor Data Peternakan
                        </h3>
                        <span class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] mt-0.5 block">
                            Unduh rekapitulasi data kecamatan ke spreadsheet
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('update:isExportModalOpen', false)"
                    class="w-8 h-8 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-rose-400 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup modal ekspor"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div class="bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg p-3 text-xs text-[#6E776D] dark:text-[#B3B3B3] flex items-center justify-between">
                <span>Cakupan Data:</span>
                <span class="font-semibold font-mono tabular-nums text-emerald-600 dark:text-emerald-400">
                    {{ props.selectedRowCount > 0 ? props.selectedRowCount + ' baris dipilih' : props.filteredCount + ' kecamatan' }}
                </span>
            </div>

            <div class="space-y-2.5 pt-1">
                <button
                    type="button"
                    @click="emit('exportData', 'excel')"
                    class="w-full p-3.5 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-emerald-500/40 bg-[#F4F5F2] dark:bg-[#111111] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center justify-between transition-colors group text-left shadow-sm cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 dark:text-emerald-400 flex-shrink-0">
                            <FileText class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5] group-hover:text-emerald-600 dark:group-hover:text-emerald-400 flex items-center gap-2">
                                <span>Microsoft Excel (.xls)</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">XLS</span>
                            </div>
                            <p class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                                Format spreadsheet langsung rapi di Microsoft Excel &amp; Google Sheets.
                            </p>
                        </div>
                    </div>
                    <Download class="w-4 h-4 text-[#6E776D] dark:text-[#B3B3B3] group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" />
                </button>

                <button
                    type="button"
                    @click="emit('exportData', 'csv')"
                    class="w-full p-3.5 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-teal-500/40 bg-[#F4F5F2] dark:bg-[#111111] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center justify-between transition-colors group text-left shadow-sm cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-500 dark:text-teal-400 flex-shrink-0">
                            <FileDown class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5] group-hover:text-teal-600 dark:group-hover:text-teal-400 flex items-center gap-2">
                                <span>Comma-Separated Values (.csv)</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-teal-500/15 text-teal-600 dark:text-teal-400">CSV</span>
                            </div>
                            <p class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                                Format teks standar dengan encoding UTF-8 BOM dan pemisah koma.
                            </p>
                        </div>
                    </div>
                    <Download class="w-4 h-4 text-[#6E776D] dark:text-[#B3B3B3] group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors" />
                </button>
            </div>

            <div class="pt-2 flex justify-end">
                <button
                    type="button"
                    @click="emit('update:isExportModalOpen', false)"
                    class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-[#171B17] dark:hover:border-[#F5F5F5] transition-colors cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- MODAL DIALOG PARAMETER FILTER LANJUTAN               -->
    <!-- ==================================================== -->
    <div
        v-if="props.isFilterModalOpen"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 select-none"
        @click.self="emit('update:isFilterModalOpen', false)"
    >
        <div class="bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#292929] rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-4 font-ui text-[#171B17] dark:text-[#F5F5F5]">
            <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 dark:text-emerald-400">
                        <SlidersHorizontal class="w-4 h-4" />
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-[#171B17] dark:text-[#F5F5F5] leading-none">
                            Parameter Filter Lanjutan
                        </h3>
                        <span class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] mt-0.5 block">
                            Sesuaikan kriteria populasi, kasus wabah, &amp; fasilitas
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('update:isFilterModalOpen', false)"
                    class="w-8 h-8 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-rose-400 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Tutup modal filter"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1.5">
                        Status Kasus Wabah (PMK / LSD)
                    </label>
                    <select
                        :value="props.selectedWabahFilter"
                        @change="emit('update:selectedWabahFilter', ($event.target as HTMLSelectElement).value)"
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-xs text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-emerald-500 cursor-pointer"
                    >
                        <option value="semua" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Semua Kondisi Kasus</option>
                        <option value="ada_kasus" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Hanya Kecamatan Berkasus (&gt;0 Kasus)</option>
                        <option value="bebas_kasus" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Bebas Kasus Wabah (0 Kasus)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1.5">
                        Fasilitas Poskeswan
                    </label>
                    <select
                        :value="props.selectedPoskeswanFilter"
                        @change="emit('update:selectedPoskeswanFilter', ($event.target as HTMLSelectElement).value)"
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-xs text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-emerald-500 cursor-pointer"
                    >
                        <option value="semua" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Semua Kecamatan</option>
                        <option value="ada" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Tersedia Poskeswan (&ge; 1 Unit)</option>
                        <option value="tidak_ada" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Belum Ada Poskeswan (0 Unit)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-1.5">
                        Ambang Populasi Ternak Minimum
                    </label>
                    <select
                        :value="props.minPopulasiFilter"
                        @change="emit('update:minPopulasiFilter', Number(($event.target as HTMLSelectElement).value))"
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-xs text-[#171B17] dark:text-[#F5F5F5] font-mono tabular-nums outline-none focus:border-emerald-500 cursor-pointer"
                    >
                        <option :value="0" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Tanpa Batas Minimum</option>
                        <option :value="1000" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">&ge; 1.000 Ekor</option>
                        <option :value="3000" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">&ge; 3.000 Ekor (Sentra Utama)</option>
                        <option :value="5000" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">&ge; 5.000 Ekor (Populasi Sangat Tinggi)</option>
                    </select>
                </div>
            </div>

            <div class="pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-between">
                <button
                    type="button"
                    @click="emit('resetFilters')"
                    class="text-xs text-rose-500 dark:text-rose-400 hover:underline font-semibold cursor-pointer"
                >
                    Reset Parameter
                </button>
                <button
                    type="button"
                    @click="emit('update:isFilterModalOpen', false)"
                    class="h-9 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors cursor-pointer"
                >
                    Terapkan Filter
                </button>
            </div>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- TOAST NOTIFIKASI DI SUDUT BAWAH                      -->
    <!-- ==================================================== -->
    <div
        v-if="props.toastMsg"
        class="fixed bottom-6 right-6 z-50 bg-white/95 dark:bg-[#181818]/95 border border-emerald-500/40 text-[#171B17] dark:text-[#F5F5F5] px-5 py-3 rounded-full shadow-xl backdrop-blur-md text-xs font-bold flex items-center gap-2.5"
    >
        <Check class="w-4 h-4 text-emerald-500 dark:text-emerald-400 stroke-[3]" />
        <span>{{ props.toastMsg }}</span>
    </div>
</template>
