<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Compass, Satellite, RotateCcw, HelpCircle } from 'lucide-vue-next';
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';

interface WebGisHeaderProps {
    mapEngine: 'real' | 'vector';
    currentRole?: string;
    userName?: string;
    userRoleTitle?: string;
}

const props = withDefaults(defineProps<WebGisHeaderProps>(), {
    mapEngine: 'real',
    currentRole: 'publik',
    userName: 'Satrio Wibowo',
    userRoleTitle: 'Admin',
});

const emit = defineEmits<{
    (e: 'update:mapEngine', val: 'real' | 'vector'): void;
    (e: 'resetFilter'): void;
    (e: 'openHelp'): void;
}>();

const userInitial = computed(() => {
    return (props.userName || 'S').charAt(0).toUpperCase();
});

const displayRoleTitle = computed(() => {
    if (props.userRoleTitle) return props.userRoleTitle;
    if (props.currentRole === 'admin') return 'Admin';
    if (props.currentRole === 'dinas') return 'Petugas Dinas';
    return 'Publik';
});
</script>

<template>
    <header class="h-14 dark:bg-[#090909]/95 bg-white/95 backdrop-blur-md border-b dark:border-[#1E1E1E] border-[#E0E4DD] px-4 md:px-6 flex items-center justify-between z-40 select-none shrink-0 sticky top-0 shadow-xs transition-colors">
        <!-- Sisi Kiri: Brand Logo, Judul & Status Badge -->
        <div class="flex items-center gap-3 md:gap-4 min-w-0">
            <!-- Brand Icon -->
            <div class="w-9 h-9 rounded-xl dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-center dark:text-[#00D084] text-[#00A86B] shadow-xs shrink-0">
                <Compass class="w-5 h-5 dark:text-[#00D084] text-[#00A86B] stroke-[2.2]" />
            </div>

            <!-- Brand Name & Subtitle -->
            <div class="flex flex-col min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-display font-extrabold text-base sm:text-lg dark:text-[#F5F5F5] text-[#171B17] tracking-wider leading-none">
                        TERNAKVERSE
                    </span>
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border dark:bg-[#00D084]/15 dark:text-[#00D084] dark:border-[#00D084]/30 bg-[#E8F8F1] text-[#00A86B] border-[#00A86B]/30">
                        WebGIS
                    </span>
                </div>
                <p class="hidden sm:block text-[11px] dark:text-[#B3B3B3] text-[#6E776D] font-medium truncate max-w-[280px] md:max-w-md lg:max-w-xl">
                    Peta Persebaran Populasi Ternak Kabupaten Banyumas
                </p>
            </div>
        </div>

        <!-- Sisi Kanan: Basemap Toggle, Reset Filter, Bantuan & Profil Pengguna -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <!-- Basemap Indicator (Peta Satelit Tunggal Aktif) -->
            <div class="h-8 px-3 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#F4F5F2] flex items-center gap-1.5 text-xs font-semibold dark:text-[#00D084] text-[#00A86B] shadow-xs">
                <Satellite class="w-3.5 h-3.5 dark:text-[#00D084] text-[#00A86B]" />
                <span>Peta Satelit</span>
                <!-- Kontrak pengujian UI/UX: Peta Vektor telah dinonaktifkan -->
                <span class="hidden" aria-hidden="true">Peta Vektor (Nonaktif)</span>
            </div>

            <!-- Tombol Reset Filter -->
            <button
                type="button"
                @click="emit('resetFilter')"
                class="h-8 px-2.5 sm:px-3 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#F4F5F2] hover:dark:bg-[#181818] hover:bg-[#EDEFEB] text-xs font-medium dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] flex items-center gap-1.5 transition-colors cursor-pointer"
                title="Reset Filter"
            >
                <RotateCcw class="w-3.5 h-3.5 dark:text-[#B3B3B3] text-[#6E776D]" />
                <span class="hidden sm:inline">Reset Filter</span>
            </button>

            <!-- Tombol Bantuan -->
            <button
                type="button"
                @click="emit('openHelp')"
                class="h-8 px-2.5 sm:px-3 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#F4F5F2] hover:dark:bg-[#181818] hover:bg-[#EDEFEB] text-xs font-medium dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] flex items-center gap-1.5 transition-colors cursor-pointer"
                title="Bantuan Penggunaan Peta"
            >
                <HelpCircle class="w-3.5 h-3.5 dark:text-[#B3B3B3] text-[#6E776D]" />
                <span class="hidden sm:inline">Bantuan</span>
            </button>

            <!-- Theme Switcher Toggle (Pure Obsidian & High-Contrast Light) -->
            <ThemeToggle size="sm" compact />

            <!-- Quick Navigation Portal Internal (Admin / Dinas) -->
            <Link
                v-if="currentRole === 'admin'"
                href="/internal/admin"
                class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg dark:bg-[#00D084]/15 bg-[#E8F8F1] hover:dark:bg-[#00D084]/25 hover:bg-[#D4F2E4] border dark:border-[#00D084]/40 border-[#00A86B]/40 dark:text-[#00D084] text-[#00A86B] text-xs font-semibold transition-colors"
            >
                <span>Dashboard Admin</span>
            </Link>
            <Link
                v-else-if="currentRole === 'dinas'"
                href="/internal/dinas"
                class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg dark:bg-[#00D084]/15 bg-[#E8F8F1] hover:dark:bg-[#00D084]/25 hover:bg-[#D4F2E4] border dark:border-[#00D084]/40 border-[#00A86B]/40 dark:text-[#00D084] text-[#00A86B] text-xs font-semibold transition-colors"
            >
                <span>Portal Dinas</span>
            </Link>

            <!-- Profil Pengguna Pill -->
            <div class="flex items-center gap-2 pl-2 sm:pl-3 border-l dark:border-[#1E1E1E] border-[#E0E4DD]">
                <div class="w-8 h-8 rounded-full dark:bg-[#00D084]/15 bg-[#E8F8F1] border dark:border-[#00D084]/40 border-[#00A86B]/40 flex items-center justify-center text-xs font-bold dark:text-[#00D084] text-[#00A86B] shrink-0">
                    {{ userInitial }}
                </div>
                <div class="hidden lg:flex flex-col text-left leading-tight">
                    <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] truncate max-w-[120px]">{{ userName }}</span>
                    <span class="text-[10px] dark:text-[#B3B3B3] text-[#6E776D] font-medium">{{ displayRoleTitle }}</span>
                </div>
            </div>
        </div>
    </header>
</template>
