<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import {
    Search,
    Compass,
    Globe,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    Bell,
    Calendar,
    ChevronDown,
} from 'lucide-vue-next';
import ThemeToggle from '../ThemeToggle.vue';
import type { AdminTab } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = defineProps<{
    isMobileMenuOpen: boolean;
    isSidebarCollapsed: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:isMobileMenuOpen', val: boolean): void;
    (e: 'update:isSidebarCollapsed', val: boolean): void;
    (e: 'selectTab', tab: AdminTab): void;
    (e: 'globalSearch', query: string): void;
    (e: 'goToPublicPortal'): void;
    (e: 'goToDinasMap'): void;
}>();

const topSearch = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);

function submitTopSearch(): void {
    if (topSearch.value.trim()) {
        emit('globalSearch', topSearch.value.trim());
    }
}

function handleKeydown(e: KeyboardEvent): void {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <header class="h-16 w-full flex-shrink-0 dark:bg-[#090909] bg-white border-b dark:border-[#1E1E1E] border-[#E0E4DD] px-4 md:px-6 flex items-center justify-between z-40 select-none transition-colors">
        <!-- Brand Kiri + Hamburger Mobile + Collapse Desktop -->
        <div class="flex items-center gap-3">
            <!-- Mobile Hamburger Drawer Trigger (< 1024px) -->
            <button
                type="button"
                @click="emit('update:isMobileMenuOpen', !props.isMobileMenuOpen)"
                class="lg:hidden w-9 h-9 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#00D084] hover:text-[#171B17] flex items-center justify-center transition-colors cursor-pointer"
                aria-label="Buka menu navigasi"
                title="Menu Navigasi"
            >
                <Menu class="w-5 h-5" />
            </button>

            <!-- Desktop Sidebar Collapse Toggle (>= 1024px) -->
            <button
                type="button"
                @click="emit('update:isSidebarCollapsed', !props.isSidebarCollapsed)"
                class="hidden lg:flex w-9 h-9 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#00D084] hover:text-[#171B17] items-center justify-center transition-colors cursor-pointer"
                :aria-label="props.isSidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                :title="props.isSidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
            >
                <PanelLeftOpen v-if="props.isSidebarCollapsed" class="w-4 h-4" />
                <PanelLeftClose v-else class="w-4 h-4" />
            </button>

            <!-- Brand Icon Compass -->
            <div class="w-9 h-9 rounded-xl dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-center text-[#00D084] shadow-xs shrink-0">
                <Compass class="w-5 h-5 text-[#00D084] stroke-[2.2]" />
            </div>

            <!-- Brand Title & Subtitle -->
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-display font-extrabold text-base tracking-wider dark:text-[#F5F5F5] text-[#171B17]">
                        TERNAKVERSE
                    </span>
                    <span class="dark:bg-[#181818] bg-[#E4E8E1] border dark:border-[#1E1E1E] border-[#CBD2C8] text-[#00D084] text-[10px] font-bold font-mono px-2 py-0.5 rounded">
                        PANEL ADMIN
                    </span>
                </div>
                <p class="text-[11px] dark:text-[#858585] text-[#737B71] hidden sm:block leading-none mt-0.5">
                    Sistem Informasi Peternakan Kabupaten Banyumas
                </p>
            </div>
        </div>

        <!-- Bagian Kanan: Search Bar, Date Range, Navigasi Cepat, Notifikasi, Theme Toggle & User Pill -->
        <div class="flex items-center gap-2 md:gap-3">
            <!-- Search Bar Global with Ctrl+K shortcut badge -->
            <div class="relative hidden lg:flex items-center">
                <Search class="w-3.5 h-3.5 absolute left-3 dark:text-[#858585] text-[#737B71] pointer-events-none" />
                <input
                    ref="searchInputRef"
                    v-model="topSearch"
                    @keyup.enter="submitTopSearch"
                    type="text"
                    placeholder="Cari komoditas, wilayah..."
                    class="w-52 xl:w-64 h-9 dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] focus:dark:border-[#00D084]/60 focus:border-[#00D084]/60 rounded-lg pl-9 pr-16 text-xs dark:text-[#F5F5F5] text-[#171B17] dark:placeholder:text-[#858585] placeholder:text-[#737B71] outline-none transition-colors"
                />
                <div class="absolute right-2 flex items-center pointer-events-none">
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-medium dark:text-[#B3B3B3] text-[#555E54] dark:bg-[#181818] bg-[#E4E8E1] border dark:border-[#1E1E1E] border-[#CBD2C8] rounded shadow-xs">Ctrl + K</kbd>
                </div>
            </div>

            <!-- Date Range Picker button -->
            <button
                type="button"
                class="hidden xl:flex items-center gap-2 h-9 dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#00D084]/40 dark:text-[#F5F5F5] text-[#171B17] text-xs px-3 rounded-lg transition-colors cursor-pointer"
                title="Rentang Waktu Data: 1 Okt 2026 - 31 Okt 2026"
            >
                <Calendar class="w-3.5 h-3.5 text-[#00D084] shrink-0" />
                <span class="font-medium dark:text-[#F5F5F5] text-[#171B17]">1 Okt 2026 - 31 Okt 2026</span>
                <ChevronDown class="w-3 h-3 dark:text-[#858585] text-[#737B71] shrink-0" />
            </button>

            <!-- Navigasi Cepat ke Portal Publik (Preview) -->
            <button
                type="button"
                @click="emit('goToPublicPortal')"
                class="h-9 px-2.5 md:px-3 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#171B17]/40 text-xs font-medium dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#00D084] hover:text-[#171B17] flex items-center gap-1.5 transition-colors cursor-pointer"
                title="Buka Portal Publik (Pratinjau)"
            >
                <Globe class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Portal Publik</span>
            </button>

            <!-- Navigasi Cepat Kembali ke WebGIS Dinas -->
            <button
                type="button"
                @click="emit('goToDinasMap')"
                class="h-9 px-2.5 md:px-3 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/50 hover:border-[#00D084]/50 text-xs font-medium text-[#00D084] flex items-center gap-1.5 transition-colors cursor-pointer"
                title="Buka Peta WebGIS Kuantitatif Dinas"
            >
                <Compass class="w-3.5 h-3.5 text-[#00D084]" />
                <span class="hidden sm:inline">WebGIS Dinas</span>
            </button>

            <!-- Notification Bell -->
            <button
                type="button"
                @click="emit('selectTab', 'ews')"
                class="relative w-9 h-9 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#00D084]/40 flex items-center justify-center dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#00D084] hover:text-[#171B17] cursor-pointer transition-colors"
                aria-label="Notifikasi EWS"
                title="Peringatan Dini EWS"
            >
                <span class="w-2 h-2 rounded-full bg-[#DC625F] absolute top-2 right-2 ring-2 dark:ring-[#090909] ring-white"></span>
                <Bell class="w-4 h-4" />
            </button>

            <!-- Theme Toggle Component (Obsidian Dark <-> Porcelain Light) -->
            <ThemeToggle size="sm" compact />

            <!-- User Profile Pill: Satrio Wibowo (Admin) -->
            <button
                type="button"
                @click="emit('selectTab', 'profile')"
                class="flex items-center gap-2 pl-2 pr-3 h-9 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/50 hover:border-[#00D084]/50 cursor-pointer transition-colors"
                title="Profil: Satrio Wibowo (Admin)"
            >
                <div class="w-6 h-6 rounded-full bg-[#00D084] text-[#062117] font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                    S
                </div>
                <div class="hidden sm:flex flex-col text-left leading-tight">
                    <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] truncate max-w-[110px]">Satrio Wibowo</span>
                    <span class="text-[10px] text-[#00D084] font-medium">Admin</span>
                </div>
                <ChevronDown class="w-3 h-3 dark:text-[#858585] text-[#737B71] hidden sm:inline" />
            </button>
        </div>
    </header>
</template>
