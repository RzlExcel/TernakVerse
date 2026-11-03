<script setup lang="ts">
import type { RphFacility } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = defineProps<{
    rphList: RphFacility[];
}>();

const emit = defineEmits<{
    (e: 'toggleStatus', id: number): void;
}>();
</script>

<template>
    <div class="w-full space-y-6">
        <div>
            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                Sebaran Rumah Potong Hewan (RPH) Banyumas
            </h1>
            <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                Mandat KAK Geospasial Peternakan 2026: Fasilitas Pemotongan Resmi &amp; RPH Swasta
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 w-full">
            <div
                v-for="rph in props.rphList"
                :key="rph.id"
                class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 space-y-3 hover:border-[#00D084]/40 hover:-translate-y-0.5 transition-all shadow-sm"
            >
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">{{ rph.nama }}</h3>
                    <button
                        type="button"
                        @click="emit('toggleStatus', rph.id)"
                        class="px-2.5 py-0.5 rounded-full font-semibold text-[10px] border transition-colors cursor-pointer"
                        :class="
                            rph.status === 'Aktif Operasional'
                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-100 dark:hover:bg-emerald-500/20'
                                : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30 hover:bg-amber-100 dark:hover:bg-amber-500/20'
                        "
                        title="Klik untuk mengganti status operasional"
                    >
                        {{ rph.status }}
                    </button>
                </div>
                <div class="text-xs text-[#6E776D] dark:text-[#B3B3B3] space-y-1.5">
                    <div><b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Kecamatan:</b> {{ rph.kecamatan }}</div>
                    <div><b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Pengelola:</b> {{ rph.pengelola }}</div>
                    <div>
                        <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Kapasitas:</b>
                        <span class="font-mono tabular-nums font-semibold text-emerald-600 dark:text-[#00D084]"> {{ rph.kapasitas }} ekor/hari</span>
                        ({{ rph.jenis }})
                    </div>
                    <div class="text-[11px] text-teal-600 dark:text-teal-400 font-mono tabular-nums font-medium">
                        GPS: {{ rph.lat }}, {{ rph.lng }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
