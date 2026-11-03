<script setup lang="ts">
import {
    Plus,
    Search,
    X,
    SlidersHorizontal,
    RotateCcw,
    Download,
} from 'lucide-vue-next';
import { formatAngka, type KecamatanData } from '../../../data/banyumasData';
import { getDesaByKecamatan } from '../../../data/banyumasDesaData';

const props = defineProps<{
    searchQuery: string;
    selectedCategoryFilter: string;
    selectedZonaFilter: string;
    hasActiveFilters: boolean;
    activeFilterCount: number;
    selectedRowIds: string[];
    filteredCount: number;
    paginatedKecamatan: KecamatanData[];
    currentPage: number;
    rowsPerPage: number;
    totalPages: number;
}>();

const emit = defineEmits<{
    (e: 'update:searchQuery', val: string): void;
    (e: 'update:selectedCategoryFilter', val: string): void;
    (e: 'update:selectedZonaFilter', val: string): void;
    (e: 'update:rowsPerPage', val: number): void;
    (e: 'update:currentPage', val: number): void;
    (e: 'openFilterModal'): void;
    (e: 'openExportModal'): void;
    (e: 'resetFilters'): void;
    (e: 'toggleSelectAll'): void;
    (e: 'toggleSelectRow', id: string): void;
    (e: 'startCreateNew'): void;
    (e: 'editKecamatan', kec: KecamatanData): void;
    (e: 'deleteKecamatan', kec: KecamatanData): void;
}>();
</script>

<template>
    <div class="w-full space-y-4">
        <!-- Breadcrumbs -->
        <div class="text-xs font-semibold text-[#6E776D] dark:text-[#858585]">
            Beranda &gt; Katalog Komoditas &gt; Basis Data Wilayah
        </div>

        <!-- Header & Tombol Aksi + TAMBAH DATA BARU -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
            <div>
                <h1 class="font-display font-extrabold text-2xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                    Katalog Komoditas &amp; Bibit Ternak
                </h1>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                    Kelola data rumpun ternak sapi perah FH, sapi potong, kambing, dan status zonasi EWS 27 kecamatan
                </p>
            </div>

            <button
                type="button"
                @click="emit('startCreateNew')"
                class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-4 py-2.5 rounded-lg text-xs flex items-center gap-2 shadow-sm transition-all self-start sm:self-auto cursor-pointer"
            >
                <Plus class="w-4 h-4 stroke-[2.5]" />
                <span>+ Tambah Komoditas Baru</span>
            </button>
        </div>

        <!-- CONTAINER TABEL (AgriTech Slate & Emerald) -->
        <div class="w-full bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl overflow-hidden shadow-sm">
            <!-- Header Kontrol Tabel -->
            <div class="p-3.5 md:p-4 border-b border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-wrap lg:flex-nowrap items-center justify-between gap-3 w-full bg-[#F4F5F2] dark:bg-[#141414] select-none">
                <!-- SISI KIRI: Search + Dropdown Filter + Reset Filter -->
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 flex-1 min-w-0">
                    <!-- 1. Search Bar -->
                    <div class="relative w-full sm:w-72 flex-shrink-0">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#6E776D] dark:text-[#858585] pointer-events-none" />
                        <input
                            :value="props.searchQuery"
                            @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
                            type="text"
                            placeholder="Cari berdasarkan nama komoditas atau kode..."
                            class="w-full h-9 bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg pl-9 pr-8 text-xs text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084] transition-all"
                        />
                        <button
                            v-if="props.searchQuery"
                            type="button"
                            @click="emit('update:searchQuery', '')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6E776D] dark:text-[#858585] hover:text-rose-500 dark:hover:text-rose-400 transition-colors cursor-pointer"
                            title="Hapus kata kunci pencarian"
                            aria-label="Hapus kata kunci pencarian"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- 2. Dropdown Filter Kategori Komoditas Ternak -->
                    <div class="relative w-44 flex-shrink-0">
                        <select
                            :value="props.selectedCategoryFilter"
                            @change="emit('update:selectedCategoryFilter', ($event.target as HTMLSelectElement).value)"
                            class="w-full h-9 bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg pl-3 pr-7 text-xs font-medium text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] cursor-pointer transition-all appearance-none truncate"
                            title="Filter Kategori Komoditas"
                        >
                            <option value="semua" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Semua Kategori</option>
                            <option value="sapi_potong" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Sapi Potong (&gt;500)</option>
                            <option value="sapi_perah" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Sapi Perah (&gt;50)</option>
                            <option value="kambing_domba" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Kambing / Domba (&gt;1rb)</option>
                            <option value="unggas" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Ayam Broiler (&gt;5rb)</option>
                        </select>
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#6E776D] dark:text-[#858585] text-[10px]">
                            ▼
                        </div>
                    </div>

                    <!-- 3. Dropdown Filter Status Zonasi EWS -->
                    <div class="relative w-44 flex-shrink-0">
                        <select
                            :value="props.selectedZonaFilter"
                            @change="emit('update:selectedZonaFilter', ($event.target as HTMLSelectElement).value)"
                            class="w-full h-9 bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg pl-3 pr-7 text-xs font-medium text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] cursor-pointer transition-all appearance-none truncate"
                            title="Filter Status Zonasi EWS"
                        >
                            <option value="semua" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Semua Status Zonasi</option>
                            <option value="bahaya" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Zona Merah (Bahaya)</option>
                            <option value="waspada" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Zona Kuning (Waspada)</option>
                            <option value="aman" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Zona Hijau (Aman)</option>
                        </select>
                        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#6E776D] dark:text-[#858585] text-[10px]">
                            ▼
                        </div>
                    </div>

                    <!-- 4. Tombol Buka Modal Parameter Filter Lanjutan -->
                    <button
                        type="button"
                        @click="emit('openFilterModal')"
                        class="h-9 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#181818] text-xs font-medium text-[#171B17] dark:text-[#F5F5F5] hover:border-[#00D084] flex items-center gap-1.5 transition-all select-none flex-shrink-0 cursor-pointer"
                        title="Buka panel parameter filter lanjutan"
                    >
                        <SlidersHorizontal class="w-3.5 h-3.5 text-emerald-500 dark:text-[#00D084] flex-shrink-0" />
                        <span>Filter Lanjutan</span>
                    </button>

                    <!-- 5. Tombol Reset Filter -->
                    <button
                        v-if="props.hasActiveFilters"
                        type="button"
                        @click="emit('resetFilters')"
                        class="h-9 px-3 rounded-lg border border-rose-300 dark:border-rose-500/40 bg-rose-50 dark:bg-rose-500/10 text-xs font-medium text-rose-600 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-500/20 flex items-center gap-1.5 transition-all select-none whitespace-nowrap flex-shrink-0 cursor-pointer"
                        title="Reset semua filter"
                    >
                        <RotateCcw class="w-3.5 h-3.5 flex-shrink-0" />
                        <span class="font-mono tabular-nums">Reset ({{ props.activeFilterCount }})</span>
                    </button>
                </div>

                <!-- SISI KANAN: Fitur Export CSV / Excel -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button
                        type="button"
                        @click="emit('openExportModal')"
                        class="h-9 px-3 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#181818] text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] hover:border-[#00D084] flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer"
                        title="Ekspor data peternakan ke berkas CSV atau Excel"
                    >
                        <Download class="w-3.5 h-3.5 text-emerald-500 dark:text-[#00D084]" />
                        <span>Ekspor</span>
                        <span class="text-[10px] text-[#6E776D] dark:text-[#858585] font-mono tabular-nums">
                            ({{ props.selectedRowIds.length > 0 ? props.selectedRowIds.length : props.filteredCount }})
                        </span>
                    </button>
                </div>
            </div>

            <!-- TABEL DATA -->
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left text-xs font-ui">
                    <thead>
                        <tr class="border-b border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] text-[#6E776D] dark:text-[#B3B3B3] text-[11px] font-bold uppercase tracking-wider select-none">
                            <th class="py-3 px-4 w-12">
                                <input
                                    type="checkbox"
                                    :checked="props.selectedRowIds.length > 0 && props.selectedRowIds.length === props.paginatedKecamatan.length"
                                    @change="emit('toggleSelectAll')"
                                    aria-label="Pilih semua baris"
                                    class="w-4 h-4 rounded bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#292929] text-[#00D084] focus:ring-0 cursor-pointer"
                                />
                            </th>
                            <th class="py-3 px-4 text-[#444C43] dark:text-[#B3B3B3]">KOMODITAS / KECAMATAN</th>
                            <th class="py-3 px-4 text-[#444C43] dark:text-[#B3B3B3]">KATEGORI</th>
                            <th class="py-3 px-4 text-[#444C43] dark:text-[#B3B3B3]">POPULASI (EKOR)</th>
                            <th class="py-3 px-4 text-[#444C43] dark:text-[#B3B3B3]">TAHUN</th>
                            <th class="py-3 px-4 text-[#444C43] dark:text-[#B3B3B3]">STATUS ZONA EWS</th>
                            <th class="py-3 px-4 text-[#444C43] dark:text-[#B3B3B3]">TERAKHIR DIPERBARUI</th>
                            <th class="py-3 px-4 text-right text-[#444C43] dark:text-[#B3B3B3]">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E0E4DD] dark:divide-[#1E1E1E]">
                        <tr
                            v-for="kec in props.paginatedKecamatan"
                            :key="kec.id"
                            :class="[
                                props.selectedRowIds.includes(kec.id)
                                    ? 'bg-[#171B17] text-white dark:bg-[#1E1E1E] dark:text-[#F5F5F5]'
                                    : 'hover:bg-[#EDEFEB] dark:hover:bg-[#181818]',
                                'transition-colors'
                            ]"
                        >
                            <!-- Checkbox -->
                            <td class="py-3.5 px-4">
                                <input
                                    type="checkbox"
                                    :checked="props.selectedRowIds.includes(kec.id)"
                                    @change="emit('toggleSelectRow', kec.id)"
                                    :aria-label="'Pilih ' + kec.nama"
                                    class="w-4 h-4 rounded bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#292929] text-[#00D084] focus:ring-0 cursor-pointer"
                                />
                            </td>

                            <!-- Nama Kecamatan + Kode + Desa -->
                            <td class="py-3.5 px-4">
                                <div
                                    class="font-bold text-sm"
                                    :class="props.selectedRowIds.includes(kec.id) ? 'text-white' : 'text-[#171B17] dark:text-[#F5F5F5]'"
                                >
                                    {{ kec.nama }}
                                </div>
                                <div
                                    class="text-[11px] mt-0.5 flex items-center gap-1.5 font-mono tabular-nums"
                                    :class="props.selectedRowIds.includes(kec.id) ? 'text-white/70' : 'text-[#6E776D] dark:text-[#858585]'"
                                >
                                    <span>KODE: {{ kec.kode_kec }}</span>
                                    <span>•</span>
                                    <span>{{ getDesaByKecamatan(kec.id).length }} Desa</span>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-3.5 px-4">
                                <span
                                    class="font-medium"
                                    :class="props.selectedRowIds.includes(kec.id) ? 'text-white/90' : 'text-[#444C43] dark:text-[#B3B3B3]'"
                                >
                                    {{ kec.sapi_perah > 50 ? 'Sapi Perah Friesian Holstein' : 'Sapi Potong Unggul' }}
                                </span>
                            </td>

                            <!-- Populasi Ekor -->
                            <td
                                class="py-3.5 px-4 font-mono tabular-nums font-bold text-xs"
                                :class="props.selectedRowIds.includes(kec.id) ? 'text-white' : 'text-[#171B17] dark:text-[#F5F5F5]'"
                            >
                                {{ formatAngka(kec.sapi_potong) }} ekor
                            </td>

                            <!-- Year -->
                            <td
                                class="py-3.5 px-4 font-mono tabular-nums"
                                :class="props.selectedRowIds.includes(kec.id) ? 'text-white/70' : 'text-[#6E776D] dark:text-[#858585]'"
                            >
                                2026
                            </td>

                            <!-- Status Zona EWS -->
                            <td class="py-3.5 px-4">
                                <span
                                    v-if="kec.zona === 'aman'"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
                                >
                                    AKTIF / AMAN
                                </span>
                                <span
                                    v-else-if="kec.zona === 'waspada'"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400"
                                >
                                    WASPADA
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-400"
                                >
                                    BAHAYA
                                </span>
                            </td>

                            <!-- Updated At -->
                            <td
                                class="py-3.5 px-4 text-xs font-mono tabular-nums"
                                :class="props.selectedRowIds.includes(kec.id) ? 'text-white/70' : 'text-[#6E776D] dark:text-[#858585]'"
                            >
                                {{ kec.last_updated }}
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-3 font-semibold text-xs">
                                    <button
                                        type="button"
                                        @click="emit('editKecamatan', kec)"
                                        class="transition-colors cursor-pointer"
                                        :class="props.selectedRowIds.includes(kec.id) ? 'text-[#00D084]' : 'text-emerald-600 dark:text-[#00D084] hover:text-emerald-500 dark:hover:text-emerald-300'"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="emit('deleteKecamatan', kec)"
                                        class="transition-colors cursor-pointer"
                                        :class="props.selectedRowIds.includes(kec.id) ? 'text-rose-300' : 'text-rose-600 dark:text-rose-400 hover:text-rose-500 dark:hover:text-rose-300'"
                                        title="Hapus Data"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="props.paginatedKecamatan.length === 0">
                            <td colspan="8" class="text-center py-10 text-[#6E776D] dark:text-[#858585]">
                                Tidak ada data komoditas yang sesuai pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- FOOTER TABEL -->
            <div class="p-3.5 md:p-4 border-t border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#6E776D] dark:text-[#858585] w-full bg-[#F4F5F2] dark:bg-[#141414]">
                <div class="font-medium font-mono tabular-nums text-[#6E776D] dark:text-[#858585]">
                    Showing {{ props.filteredCount === 0 ? 0 : (props.currentPage - 1) * props.rowsPerPage + 1 }} to {{ Math.min(props.currentPage * props.rowsPerPage, props.filteredCount) }} of {{ props.filteredCount }} results
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] px-3 py-1 rounded-lg text-[#6E776D] dark:text-[#B3B3B3]">
                        <span>Per page</span>
                        <select
                            :value="props.rowsPerPage"
                            @change="emit('update:rowsPerPage', Number(($event.target as HTMLSelectElement).value))"
                            class="bg-transparent text-[#171B17] dark:text-[#F5F5F5] font-semibold font-mono tabular-nums outline-none cursor-pointer"
                        >
                            <option :value="5" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">5</option>
                            <option :value="10" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">10</option>
                            <option :value="20" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">20</option>
                            <option :value="50" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">50</option>
                        </select>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center gap-1.5 font-mono tabular-nums">
                        <button
                            type="button"
                            @click="emit('update:currentPage', Math.max(1, props.currentPage - 1))"
                            :disabled="props.currentPage === 1"
                            class="h-8 px-2.5 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#181818] text-[#444C43] dark:text-[#B3B3B3] hover:bg-[#EDEFEB] dark:hover:bg-[#282828] disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer text-xs"
                            aria-label="Halaman sebelumnya"
                        >
                            &lt;
                        </button>

                        <template v-for="p in props.totalPages" :key="p">
                            <button
                                v-if="props.totalPages <= 7 || Math.abs(p - props.currentPage) <= 1 || p === 1 || p === props.totalPages"
                                type="button"
                                @click="emit('update:currentPage', p)"
                                :class="[
                                    p === props.currentPage
                                        ? 'bg-[#171B17] text-white font-bold border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#00D084]'
                                        : 'bg-white dark:bg-[#181818] text-[#444C43] dark:text-[#B3B3B3] border border-[#E0E4DD] dark:border-[#1E1E1E] hover:bg-[#EDEFEB] dark:hover:bg-[#282828]',
                                    'h-8 min-w-[32px] px-2 rounded-lg border text-xs cursor-pointer transition-all'
                                ]"
                            >
                                {{ p }}
                            </button>
                            <span
                                v-else-if="(p === 2 && props.currentPage > 3) || (p === props.totalPages - 1 && props.currentPage < props.totalPages - 2)"
                                class="px-1 text-[#6E776D] dark:text-[#858585] text-xs"
                            >
                                ...
                            </span>
                        </template>

                        <button
                            type="button"
                            @click="emit('update:currentPage', Math.min(props.totalPages, props.currentPage + 1))"
                            :disabled="props.currentPage === props.totalPages"
                            class="h-8 px-2.5 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#181818] text-[#444C43] dark:text-[#B3B3B3] hover:bg-[#EDEFEB] dark:hover:bg-[#282828] disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer text-xs"
                            aria-label="Halaman selanjutnya"
                        >
                            &gt;
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
