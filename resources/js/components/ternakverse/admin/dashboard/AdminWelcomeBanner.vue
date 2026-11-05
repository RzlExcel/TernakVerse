<script setup lang="ts">
import { Upload, Plus, MoreHorizontal } from 'lucide-vue-next';

interface Props {
    userName?: string;
    subtitle?: string;
}

const props = withDefaults(defineProps<Props>(), {
    userName: 'Satrio Wibowo',
    subtitle: 'Berikut adalah ringkasan data dan aktivitas terbaru dari sistem peternakan Kabupaten Banyumas.',
});

const emit = defineEmits<{
    (e: 'openImportModal'): void;
    (e: 'startCreateNew'): void;
}>();
</script>

<template>
    <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 md:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 w-full transition-colors">
        <!-- Left Side: Page Title & Subtitle (Selamat Datang) -->
        <div>
            <h1 class="font-display font-bold text-2xl md:text-3xl dark:text-[#F5F5F5] text-[#171B17] tracking-tight flex items-center gap-2">
                <span>Ringkasan Operasional Peternakan</span>
            </h1>
            <p class="text-xs md:text-sm dark:text-[#858585] text-[#737B71] mt-1">
                {{ props.subtitle }}
            </p>
        </div>

        <!-- Right Side: Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
            <!-- Impor Data (Secondary Action) -->
            <button
                type="button"
                @click="emit('openImportModal')"
                class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#171B17]/40 dark:text-[#F5F5F5] text-[#171B17] text-xs font-semibold px-3.5 py-2 rounded-lg flex items-center gap-2 transition-colors cursor-pointer"
            >
                <Upload class="w-3.5 h-3.5 dark:text-[#B3B3B3] text-[#444C43]" />
                <span>Impor Data</span>
            </button>

            <!-- + Tambah Komoditas (Primary Action: Dark Electric Emerald / Light Charcoal) -->
            <button
                type="button"
                @click="emit('startCreateNew')"
                class="dark:bg-[#00D084] dark:text-[#062117] dark:hover:bg-[#00F0A0] bg-[#171B17] text-white hover:bg-[#282828] text-xs font-semibold px-4 py-2 rounded-lg flex items-center gap-2 shadow-xs transition-colors cursor-pointer"
            >
                <Plus class="w-3.5 h-3.5 stroke-[2.5]" />
                <span>+ Tambah Komoditas</span>
            </button>

            <!-- More Button (...) -->
            <button
                type="button"
                aria-label="Opsi Lainnya"
                class="dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] px-2.5 py-2 rounded-lg cursor-pointer transition-colors flex items-center justify-center"
            >
                <MoreHorizontal class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
