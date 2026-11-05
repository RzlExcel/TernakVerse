<script setup lang="ts">
import { ref, computed } from 'vue';
import { MapPin, Building2, ShoppingBag, Stethoscope } from 'lucide-vue-next';
import type { RphFacility } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = defineProps<{
    rphList: RphFacility[];
}>();

const emit = defineEmits<{
    (e: 'toggleStatus', id: number): void;
}>();

type FilterType = 'semua' | 'RPH' | 'Pasar Hewan' | 'Puskeswan';
const activeFilter = ref<FilterType>('semua');

const rphCount = computed(() => props.rphList.filter(f => (f.tipe ?? 'RPH') === 'RPH').length);
const pasarCount = computed(() => props.rphList.filter(f => f.tipe === 'Pasar Hewan').length);
const puskeswanCount = computed(() => props.rphList.filter(f => f.tipe === 'Puskeswan').length);

const filteredList = computed(() => {
    if (activeFilter.value === 'semua') return props.rphList;
    return props.rphList.filter(f => (f.tipe ?? 'RPH') === activeFilter.value);
});

function getTypeBadgeClass(tipe?: string): string {
    switch (tipe) {
        case 'Pasar Hewan':
            return 'bg-amber-100/80 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800/60';
        case 'Puskeswan':
            return 'bg-sky-100/80 dark:bg-sky-950/40 text-sky-800 dark:text-sky-300 border-sky-300 dark:border-sky-800/60';
        case 'RPH':
        default:
            return 'bg-emerald-100/80 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800/60';
    }
}

function getTypeLabel(tipe?: string): string {
    if (tipe === 'Puskeswan') return 'UPT Puskeswan';
    if (tipe === 'Pasar Hewan') return 'Pasar Hewan';
    return 'RPH';
}
</script>

<template>
    <div class="w-full space-y-6">
        <!-- Header -->
        <div>
            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                Sebaran Fasilitas Peternakan Resmi Banyumas
            </h1>
            <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                6 UPT RPH, 3 Pasar Hewan, dan 2 UPT Puskeswan Terdaftar
            </p>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-[#E0E4DD] dark:border-[#1E1E1E] pb-3" role="tablist" aria-label="Filter Fasilitas">
            <button
                type="button"
                role="tab"
                :aria-selected="activeFilter === 'semua'"
                @click="activeFilter = 'semua'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                :class="
                    activeFilter === 'semua'
                        ? 'bg-[#00D084] text-[#0A0D0B] shadow-sm font-bold'
                        : 'bg-white dark:bg-[#141815] text-[#6E776D] dark:text-[#A0A8A0] border border-[#E0E4DD] dark:border-[#222723] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-[#00D084]/40'
                "
            >
                <span>Semua ({{ props.rphList.length }})</span>
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeFilter === 'RPH'"
                @click="activeFilter = 'RPH'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                :class="
                    activeFilter === 'RPH'
                        ? 'bg-emerald-600 text-white shadow-sm font-bold dark:bg-emerald-500 dark:text-[#0A0D0B]'
                        : 'bg-white dark:bg-[#141815] text-[#6E776D] dark:text-[#A0A8A0] border border-[#E0E4DD] dark:border-[#222723] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-emerald-500/40'
                "
            >
                <Building2 class="w-3.5 h-3.5" />
                <span>RPH ({{ rphCount }})</span>
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeFilter === 'Pasar Hewan'"
                @click="activeFilter = 'Pasar Hewan'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                :class="
                    activeFilter === 'Pasar Hewan'
                        ? 'bg-amber-500 text-white shadow-sm font-bold dark:bg-amber-400 dark:text-[#0A0D0B]'
                        : 'bg-white dark:bg-[#141815] text-[#6E776D] dark:text-[#A0A8A0] border border-[#E0E4DD] dark:border-[#222723] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-amber-500/40'
                "
            >
                <ShoppingBag class="w-3.5 h-3.5" />
                <span>Pasar Hewan ({{ pasarCount }})</span>
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeFilter === 'Puskeswan'"
                @click="activeFilter = 'Puskeswan'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                :class="
                    activeFilter === 'Puskeswan'
                        ? 'bg-sky-600 text-white shadow-sm font-bold dark:bg-sky-400 dark:text-[#0A0D0B]'
                        : 'bg-white dark:bg-[#141815] text-[#6E776D] dark:text-[#A0A8A0] border border-[#E0E4DD] dark:border-[#222723] hover:text-[#171B17] dark:hover:text-[#F5F5F5] hover:border-sky-500/40'
                "
            >
                <Stethoscope class="w-3.5 h-3.5" />
                <span>UPT Puskeswan ({{ puskeswanCount }})</span>
            </button>
        </div>

        <!-- Facility Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 w-full">
            <div
                v-for="rph in filteredList"
                :key="rph.id"
                class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 space-y-3 hover:border-[#00D084]/40 transition-colors shadow-sm flex flex-col justify-between"
            >
                <!-- Card Header -->
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1 min-w-0">
                            <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5] truncate" :title="rph.nama">
                                {{ rph.nama }}
                            </h3>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border tracking-wide uppercase"
                                :class="getTypeBadgeClass(rph.tipe)"
                            >
                                {{ getTypeLabel(rph.tipe) }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="emit('toggleStatus', rph.id)"
                            class="px-2.5 py-1 rounded-full font-semibold text-[10px] border transition-colors cursor-pointer flex-shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                            :class="
                                rph.status === 'Aktif Operasional'
                                    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-100 dark:hover:bg-emerald-500/20'
                                    : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30 hover:bg-amber-100 dark:hover:bg-amber-500/20'
                            "
                            title="Klik untuk mengganti status operasional"
                            :aria-label="`Ubah status ${rph.nama}, saat ini ${rph.status}`"
                        >
                            {{ rph.status }}
                        </button>
                    </div>

                    <!-- Card Details -->
                    <div class="text-xs text-[#6E776D] dark:text-[#B3B3B3] space-y-1.5 pt-1">
                        <div>
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Kecamatan:</b>
                            <span class="ml-1">{{ rph.kecamatan }}</span>
                        </div>
                        <div>
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Pengelola:</b>
                            <span class="ml-1">{{ rph.pengelola }}</span>
                        </div>

                        <!-- Kapasitas / Target by Type -->
                        <div v-if="rph.tipe === 'Pasar Hewan'">
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Kapasitas Tampung:</b>
                            <span class="font-mono tabular-nums font-semibold text-amber-600 dark:text-amber-400 ml-1">
                                {{ rph.kapasitas.toLocaleString('id-ID') }} ekor
                            </span>
                            <span class="text-[11px] ml-1">({{ rph.jenis }})</span>
                        </div>
                        <div v-else-if="rph.tipe === 'Puskeswan'">
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Target Layanan:</b>
                            <span class="font-mono tabular-nums font-semibold text-sky-600 dark:text-sky-400 ml-1">
                                {{ rph.kapasitas }} kasus/bln
                            </span>
                            <span class="text-[11px] ml-1">({{ rph.jenis }})</span>
                        </div>
                        <div v-else>
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Kapasitas:</b>
                            <span class="font-mono tabular-nums font-semibold text-emerald-600 dark:text-[#00D084] ml-1">
                                {{ rph.kapasitas }} ekor/hari
                            </span>
                            <span class="text-[11px] ml-1">({{ rph.jenis }})</span>
                        </div>

                        <!-- Hari Pasaran (Pasar Hewan) -->
                        <div v-if="rph.hari_pasaran">
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Hari Pasaran:</b>
                            <span class="font-semibold text-amber-700 dark:text-amber-300 ml-1">{{ rph.hari_pasaran }}</span>
                        </div>

                        <!-- Wilayah Binaan (Puskeswan) -->
                        <div v-if="rph.wilayah_binaan">
                            <b class="text-[#171B17] dark:text-[#F5F5F5] font-semibold">Wilayah Binaan:</b>
                            <span class="text-sky-700 dark:text-sky-300 font-medium ml-1">{{ rph.wilayah_binaan }}</span>
                        </div>
                    </div>
                </div>

                <!-- GPS Coordinates Footer -->
                <div class="pt-2 border-t border-[#E0E4DD]/60 dark:border-[#1E1E1E]/80">
                    <div class="text-[11px] text-teal-600 dark:text-teal-400 font-mono tabular-nums font-medium flex items-center gap-1.5">
                        <MapPin class="w-3.5 h-3.5 flex-shrink-0" />
                        <span>GPS: {{ rph.lat }}, {{ rph.lng }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
