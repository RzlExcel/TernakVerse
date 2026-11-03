<script setup lang="ts">
import { FileDown, FileText, Compass, Layers, Download } from 'lucide-vue-next';

const emit = defineEmits<{
    (e: 'showToast', msg: string): void;
}>();
</script>

<template>
    <div class="w-full space-y-6">
        <div>
            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                Generator Laporan &amp; Interoperabilitas Metadata
            </h1>
            <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                Ekspor berkas terstandarisasi untuk Geoportal Banyumas dan Laporan Triwulanan
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
            <!-- Kartu 1: Laporan Excel Multi-Sheet -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-[#00D084]/40 hover:-translate-y-0.5 transition-all shadow-sm">
                <div class="flex items-center gap-2.5 text-emerald-600 dark:text-[#00D084]">
                    <FileDown class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Buku Kerja Excel Multi-Sheet (.xlsx)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Berkas Excel memuat <span class="font-mono tabular-nums text-[#171B17] dark:text-[#F5F5F5] font-semibold">5</span> lembar terpisah: Ringkasan, Populasi Ternak, Produksi, Kasus Penyakit, dan Sebaran RPH <span class="font-mono tabular-nums text-[#171B17] dark:text-[#F5F5F5] font-semibold">27</span> kecamatan.
                </p>
                <button
                    type="button"
                    @click="emit('showToast', 'Mengunduh Laporan_Peternakan_Banyumas_2026_TW2.xlsx')"
                    class="w-full h-10 rounded-lg bg-[#171B17] dark:bg-[#00D084] hover:bg-[#2A2E2A] dark:hover:bg-[#00B98B] text-white dark:text-[#090909] text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer shadow-xs"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Excel Multi-Sheet</span>
                </button>
            </div>

            <!-- Kartu 2: Dokumen PDF Resmi -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-teal-500/40 hover:-translate-y-0.5 transition-all shadow-sm">
                <div class="flex items-center gap-2.5 text-teal-600 dark:text-teal-400">
                    <FileText class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Laporan Ringkasan Eksekutif (PDF)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Dokumen PDF resmi berstempel dinas memuat ringkasan eksekutif, tabel data, peta zonasi, dan lembar pengesahan Kepala Bidang.
                </p>
                <button
                    type="button"
                    @click="emit('showToast', 'Mengunduh Laporan_Ringkasan_Eksekutif_Banyumas_2026.pdf')"
                    class="w-full h-10 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-teal-500/30 hover:border-teal-500 text-teal-700 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Dokumen PDF</span>
                </button>
            </div>

            <!-- Kartu 3: Metadata ISO 19115 XML -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-amber-500/40 hover:-translate-y-0.5 transition-all shadow-sm">
                <div class="flex items-center gap-2.5 text-amber-600 dark:text-amber-400">
                    <Compass class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Metadata Geospasial ISO 19115 (XML)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Format XML resmi KAK Bagian 4.d untuk interoperabilitas panen katalog (harvesting) Geoportal Kabupaten Banyumas.
                </p>
                <button
                    type="button"
                    @click="emit('showToast', 'Mengunduh ID-DINPERTAN-BMS-TERNAKVERSE-2026.xml')"
                    class="w-full h-10 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-amber-500/30 hover:border-amber-500 text-amber-700 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Metadata XML</span>
                </button>
            </div>

            <!-- Kartu 4: Shapefile KUGI (.zip) -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-[#00D084]/40 hover:-translate-y-0.5 transition-all shadow-sm">
                <div class="flex items-center gap-2.5 text-emerald-600 dark:text-[#00D084]">
                    <Layers class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Shapefile KUGI Geoportal (.zip)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Unsur ADMINISTRASI_AR_PETERNAKAN lengkap dengan file .shp, .shx, .dbf, .prj proyeksi SRGI2013-WGS84.
                </p>
                <button
                    type="button"
                    @click="emit('showToast', 'Mengunduh BANYUMAS_PETERNAKAN_KUGI_SHP.zip')"
                    class="w-full h-10 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-emerald-500/30 hover:border-[#00D084] text-emerald-700 dark:text-[#00D084] hover:text-emerald-800 dark:hover:text-emerald-300 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Paket SHP KUGI</span>
                </button>
            </div>
        </div>
    </div>
</template>
