<script setup lang="ts">
import {
    Upload,
    X,
    Download,
    FileText,
    FileDown,
    SlidersHorizontal,
    Check,
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
    (e: 'importSuccess'): void;
    (e: 'exportData', format: 'excel' | 'csv'): void;
    (e: 'resetFilters'): void;
}>();
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
        <div class="bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#292929] rounded-2xl p-6 w-full max-w-lg shadow-2xl text-[#171B17] dark:text-[#F5F5F5]">
            <div class="flex items-center justify-between pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E] mb-4">
                <h3 class="font-display font-extrabold text-xl text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                    <Upload class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
                    <span>Impor Data CSV / Excel</span>
                </h3>
                <button
                    type="button"
                    @click="emit('update:isImportModalOpen', false)"
                    class="w-8 h-8 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-emerald-500/40 flex items-center justify-center transition-all cursor-pointer"
                    aria-label="Tutup modal impor"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div class="space-y-4">
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3]">
                    Unggah berkas lembar kerja rekapitulasi data peternakan (.csv atau .xlsx). Sistem akan memvalidasi kode kecamatan dan menghitung total agregat secara otomatis.
                </p>

                <div class="border border-dashed border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-emerald-500/50 rounded-xl p-6 text-center cursor-pointer transition-colors bg-[#F4F5F2] dark:bg-[#111111] text-[#171B17] dark:text-[#F5F5F5]">
                    <Upload class="w-8 h-8 text-emerald-500 dark:text-emerald-400 mx-auto mb-2" />
                    <span class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5] block">Tarik berkas ke sini atau klik untuk memilih</span>
                    <span class="text-[10px] text-[#6E776D] dark:text-[#B3B3B3] block mt-1 font-mono tabular-nums">Mendukung format .CSV, .XLS, .XLSX (Maks 10 MB)</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <button
                        type="button"
                        @click="emit('update:isImportModalOpen', false)"
                        class="h-10 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:bg-[#F4F5F2] dark:hover:bg-[#111111] cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="emit('importSuccess')"
                        class="h-10 px-5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-2 transition-colors cursor-pointer"
                    >
                        <Upload class="w-4 h-4" />
                        <span>Unggah &amp; Sinkronkan</span>
                    </button>
                </div>
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
                    class="w-8 h-8 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-rose-400 flex items-center justify-center transition-all cursor-pointer"
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
                    class="w-full p-3.5 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-emerald-500/40 bg-[#F4F5F2] dark:bg-[#111111] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center justify-between transition-all group text-left shadow-sm cursor-pointer"
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
                    class="w-full p-3.5 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-teal-500/40 bg-[#F4F5F2] dark:bg-[#111111] hover:bg-[#EDEFEB] dark:hover:bg-[#181818] flex items-center justify-between transition-all group text-left shadow-sm cursor-pointer"
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
                    class="h-9 px-4 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-semibold text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-[#171B17] dark:hover:border-[#F5F5F5] transition-all cursor-pointer"
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
                    class="w-8 h-8 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#6E776D] dark:text-[#B3B3B3] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-rose-400 flex items-center justify-center transition-all cursor-pointer"
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
        class="fixed bottom-6 right-6 z-50 bg-white/95 dark:bg-[#181818]/95 border border-emerald-500/40 text-[#171B17] dark:text-[#F5F5F5] px-5 py-3 rounded-full shadow-2xl shadow-emerald-950/20 backdrop-blur-md text-xs font-bold flex items-center gap-2.5"
    >
        <Check class="w-4 h-4 text-emerald-500 dark:text-emerald-400 stroke-[3]" />
        <span>{{ props.toastMsg }}</span>
    </div>
</template>
