<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
    Check,
} from 'lucide-vue-next';
import ThemeToggle from '../ThemeToggle.vue';
import NotificationPopover, { type NotificationNavigatePayload } from './NotificationPopover.vue';
import type { AdminTab } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = withDefaults(
    defineProps<{
        isMobileMenuOpen: boolean;
        isSidebarCollapsed: boolean;
        userName?: string;
        userRole?: string;
        isSuperAdmin?: boolean;
    }>(),
    {
        userName: 'Satrio Wibowo',
        userRole: 'admin',
        isSuperAdmin: false,
    }
);

const userInitial = computed(() => {
    const name = props.userName?.trim();
    return name && name.length > 0 ? name.charAt(0).toUpperCase() : 'U';
});

const userRoleBadge = computed(() => {
    const role = props.userRole?.toLowerCase();
    if (role === 'superadmin' || props.isSuperAdmin) {
        return {
            label: 'Super Admin',
            colorClass: 'text-[#00D084]',
        };
    }
    if (role === 'admin_dinas') {
        return {
            label: 'Admin Dinas Teknis',
            colorClass: 'text-sky-500 dark:text-sky-400',
        };
    }
    if (role === 'admin') {
        return {
            label: 'Administrator',
            colorClass: 'text-sky-500 dark:text-sky-400',
        };
    }
    if (role === 'stakeholder') {
        return {
            label: 'Petugas Dinas',
            colorClass: 'text-slate-500 dark:text-slate-400',
        };
    }
    return {
        label: props.userRole || 'Admin',
        colorClass: 'text-[#00D084]',
    };
});

const emit = defineEmits<{
    (e: 'update:isMobileMenuOpen', val: boolean): void;
    (e: 'update:isSidebarCollapsed', val: boolean): void;
    (e: 'selectTab', tab: AdminTab): void;
    (e: 'globalSearch', query: string): void;
    (e: 'goToPublicPortal'): void;
    (e: 'goToDinasMap'): void;
    (e: 'dateRangeChanged', range: { startDate: string; endDate: string; label: string }): void;
    (e: 'filterYear', year: number | 'all'): void;
}>();

const topSearch = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);

// Interactive Notification Popover
const isNotificationOpen = ref(false);
const unreadNotificationCount = ref(3);
const notificationContainerRef = ref<HTMLElement | null>(null);

function handleNotificationNavigate(payload: NotificationNavigatePayload): void {
    isNotificationOpen.value = false;
    emit('selectTab', payload.tab);
}

function handleSelectTabFromNotification(tab: AdminTab): void {
    isNotificationOpen.value = false;
    emit('selectTab', tab);
}

function handleUnreadCountUpdate(count: number): void {
    unreadNotificationCount.value = count;
}

// Interactive Date Range Dropdown Popover
const isDateDropdownOpen = ref(false);
const selectedDateRange = ref('Tahun 2026 (Terbaru)');
const dateDropdownRef = ref<HTMLElement | null>(null);

const startDateInput = ref('2026-01-01');
const endDateInput = ref('2026-12-31');

export interface DateRangePreset {
    id: string;
    label: string;
    sublabel: string;
    startDate: string;
    endDate: string;
    year: number | 'all';
}

const DATE_RANGE_PRESETS: DateRangePreset[] = [
    {
        id: 'all',
        label: 'Semua Periode (2024 - 2026)',
        sublabel: 'Rentang Arsip Lengkap 3 Tahun',
        startDate: '2024-01-01',
        endDate: '2026-12-31',
        year: 'all',
    },
    {
        id: '2026',
        label: 'Tahun 2026 (Terbaru)',
        sublabel: 'Data Berjalan Terkini (1 Okt 2026 - 31 Okt 2026)',
        startDate: '2026-01-01',
        endDate: '2026-12-31',
        year: 2026,
    },
    {
        id: '2025',
        label: 'Tahun 2025',
        sublabel: 'Data Tahunan Konsolidasi',
        startDate: '2025-01-01',
        endDate: '2025-12-31',
        year: 2025,
    },
    {
        id: '2024',
        label: 'Tahun 2024 (Data Awal)',
        sublabel: 'Data Historis Awal Pencatatan',
        startDate: '2024-01-01',
        endDate: '2024-12-31',
        year: 2024,
    },
];

function formatDateDisplay(isoDate: string): string {
    if (!isoDate) return '';
    const parts = isoDate.split('-');
    if (parts.length === 3) {
        const [y, m, d] = parts;
        return `${d}/${m}/${y}`;
    }
    return isoDate;
}

function selectPreset(preset: DateRangePreset): void {
    selectedDateRange.value = preset.label;
    startDateInput.value = preset.startDate;
    endDateInput.value = preset.endDate;
    emit('dateRangeChanged', {
        startDate: preset.startDate,
        endDate: preset.endDate,
        label: preset.label,
    });
    emit('filterYear', preset.year);
    isDateDropdownOpen.value = false;
}

function applyCustomDateRange(): void {
    if (!startDateInput.value || !endDateInput.value) return;

    let start = startDateInput.value;
    let end = endDateInput.value;
    if (start > end) {
        const temp = start;
        start = end;
        end = temp;
        startDateInput.value = start;
        endDateInput.value = end;
    }

    const startFormatted = formatDateDisplay(start);
    const endFormatted = formatDateDisplay(end);
    const label = `${startFormatted} - ${endFormatted}`;
    selectedDateRange.value = label;

    const startYear = parseInt(start.split('-')[0] ?? '2026', 10);
    const endYear = parseInt(end.split('-')[0] ?? '2026', 10);
    const year = startYear === endYear ? startYear : 'all';

    emit('dateRangeChanged', {
        startDate: start,
        endDate: end,
        label,
    });
    emit('filterYear', year);
    isDateDropdownOpen.value = false;
}

function handleClickOutside(event: MouseEvent): void {
    const target = event.target as Node;
    if (dateDropdownRef.value && !dateDropdownRef.value.contains(target)) {
        isDateDropdownOpen.value = false;
    }
    if (notificationContainerRef.value && !notificationContainerRef.value.contains(target)) {
        isNotificationOpen.value = false;
    }
}

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
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('click', handleClickOutside);
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
                    <span class="font-display font-bold text-base tracking-normal dark:text-[#F5F5F5] text-[#171B17]">
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
                    placeholder="Cari komoditas atau wilayah"
                    class="w-52 xl:w-64 h-9 dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] focus:dark:border-[#00D084]/60 focus:border-[#00D084]/60 rounded-lg pl-9 pr-16 text-xs dark:text-[#F5F5F5] text-[#171B17] dark:placeholder:text-[#858585] placeholder:text-[#737B71] outline-none transition-colors"
                />
                <div class="absolute right-2 flex items-center pointer-events-none">
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-medium dark:text-[#B3B3B3] text-[#555E54] dark:bg-[#181818] bg-[#E4E8E1] border dark:border-[#1E1E1E] border-[#CBD2C8] rounded shadow-xs">Ctrl + K</kbd>
                </div>
            </div>

            <!-- Date Range Picker interactive dropdown popover -->
            <div ref="dateDropdownRef" class="relative hidden xl:block">
                <button
                    type="button"
                    @click="isDateDropdownOpen = !isDateDropdownOpen"
                    class="flex items-center gap-2 h-9 dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#171B17]/40 dark:text-[#F5F5F5] text-[#171B17] text-xs px-3 rounded-lg transition-colors cursor-pointer select-none"
                    :title="`Rentang Waktu Data: ${selectedDateRange}`"
                    :aria-expanded="isDateDropdownOpen"
                    aria-haspopup="true"
                >
                    <Calendar class="w-3.5 h-3.5 dark:text-[#00D084] text-[#171B17] shrink-0" />
                    <span class="font-medium dark:text-[#F5F5F5] text-[#171B17]">{{ selectedDateRange }}</span>
                    <ChevronDown
                        class="w-3 h-3 dark:text-[#858585] text-[#737B71] shrink-0 transition-transform duration-200"
                        :class="{ 'rotate-180': isDateDropdownOpen }"
                    />
                </button>

                <!-- Popover Dropdown -->
                <div
                    v-if="isDateDropdownOpen"
                    class="absolute right-0 top-full mt-1.5 w-80 rounded-xl bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] shadow-xl z-50 overflow-hidden focus:outline-none"
                    role="menu"
                >
                    <!-- Popover Header -->
                    <div class="px-3.5 py-2.5 border-b border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-between bg-[#FBFBFA] dark:bg-[#141414]">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-[#737B71] dark:text-[#858585]">
                            Rentang Waktu Data
                        </span>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-[#00D084]/15 text-[#00D084] font-medium border border-[#00D084]/20">
                            2024 &ndash; 2026
                        </span>
                    </div>

                    <!-- Presets Section -->
                    <div class="p-1.5 space-y-0.5">
                        <div class="px-2 pt-1 pb-0.5 text-[9px] font-semibold text-[#858585] dark:text-[#666666] uppercase tracking-wider">
                            Pilihan Periode Standar
                        </div>
                        <button
                            v-for="preset in DATE_RANGE_PRESETS"
                            :key="preset.id"
                            type="button"
                            role="menuitem"
                            @click="selectPreset(preset)"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs flex items-center justify-between transition-colors cursor-pointer group"
                            :class="[
                                selectedDateRange === preset.label
                                    ? 'bg-[#171B17] text-white dark:bg-[#252525] dark:text-[#00D084]'
                                    : 'text-[#171B17] dark:text-[#F5F5F5] hover:bg-[#F4F5F2] dark:hover:bg-[#222222]'
                            ]"
                        >
                            <div class="flex flex-col">
                                <span class="font-medium text-xs">{{ preset.label }}</span>
                                <span
                                    class="text-[10px] mt-0.5"
                                    :class="selectedDateRange === preset.label ? 'text-[#B3B3B3] dark:text-[#00D084]/80' : 'text-[#737B71] dark:text-[#858585]'"
                                >
                                    {{ preset.sublabel }}
                                </span>
                            </div>
                            <Check
                                v-if="selectedDateRange === preset.label"
                                class="w-3.5 h-3.5 shrink-0"
                                :class="selectedDateRange === preset.label ? 'text-white dark:text-[#00D084]' : ''"
                            />
                        </button>
                    </div>

                    <!-- Custom Standard Date Form Section -->
                    <div class="p-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#FBFBFA] dark:bg-[#121212]">
                        <div class="text-[9px] font-semibold text-[#858585] dark:text-[#666666] uppercase tracking-wider mb-2">
                            Pilih Rentang Tanggal Standar
                        </div>
                        <div class="grid grid-cols-2 gap-2 mb-2.5">
                            <div>
                                <label for="admin-topbar-start-date" class="block text-[10px] font-medium text-[#737B71] dark:text-[#858585] mb-1">
                                    Tanggal Mulai
                                </label>
                                <input
                                    id="admin-topbar-start-date"
                                    aria-label="Tanggal Mulai"
                                    v-model="startDateInput"
                                    type="date"
                                    min="2024-01-01"
                                    max="2026-12-31"
                                    class="w-full h-8 px-2 text-xs rounded-md bg-white dark:bg-[#1E1E1E] border border-[#CBD2C8] dark:border-[#333333] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:ring-1 focus:ring-[#00D084] focus:border-[#00D084] transition-colors [color-scheme:light] dark:[color-scheme:dark]"
                                />
                            </div>
                            <div>
                                <label for="admin-topbar-end-date" class="block text-[10px] font-medium text-[#737B71] dark:text-[#858585] mb-1">
                                    Tanggal Selesai
                                </label>
                                <input
                                    id="admin-topbar-end-date"
                                    aria-label="Tanggal Selesai"
                                    v-model="endDateInput"
                                    type="date"
                                    min="2024-01-01"
                                    max="2026-12-31"
                                    class="w-full h-8 px-2 text-xs rounded-md bg-white dark:bg-[#1E1E1E] border border-[#CBD2C8] dark:border-[#333333] text-[#171B17] dark:text-[#F5F5F5] focus:outline-none focus:ring-1 focus:ring-[#00D084] focus:border-[#00D084] transition-colors [color-scheme:light] dark:[color-scheme:dark]"
                                />
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-1">
                            <span class="text-[10px] text-[#737B71] dark:text-[#858585]">
                                Format: Tanggal Biasa
                            </span>
                            <button
                                type="button"
                                @click="applyCustomDateRange"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#00D084] hover:bg-[#00B975] text-[#062117] transition-all shadow-xs cursor-pointer flex items-center gap-1 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
                            >
                                <Check class="w-3.5 h-3.5 stroke-[2.5]" />
                                <span>Terapkan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

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

            <!-- Notification Bell & Interactive Popover -->
            <div ref="notificationContainerRef" class="relative">
                <button
                    type="button"
                    @click.stop="isNotificationOpen = !isNotificationOpen"
                    class="relative w-9 h-9 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#00D084]/40 flex items-center justify-center dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#00D084] hover:text-[#171B17] cursor-pointer transition-colors"
                    :class="{ 'dark:border-[#00D084] border-[#00D084] dark:text-[#00D084] text-[#00D084]': isNotificationOpen }"
                    :aria-expanded="isNotificationOpen"
                    aria-haspopup="true"
                    aria-label="Pusat Peringatan & Notifikasi Dini"
                    title="Pusat Peringatan & Notifikasi Dini"
                >
                    <span
                        v-if="unreadNotificationCount > 0"
                        class="w-2 h-2 rounded-full bg-[#DC625F] absolute top-2 right-2 ring-2 dark:ring-[#090909] ring-white"
                    ></span>
                    <Bell class="w-4 h-4" />
                </button>

                <!-- Popover Dropdown -->
                <NotificationPopover
                    v-if="isNotificationOpen"
                    :is-open="isNotificationOpen"
                    @close="isNotificationOpen = false"
                    @navigate="handleNotificationNavigate"
                    @select-tab="handleSelectTabFromNotification"
                    @update:unread-count="handleUnreadCountUpdate"
                />
            </div>

            <!-- Theme Toggle Component (Obsidian Dark <-> Porcelain Light) -->
            <ThemeToggle size="sm" compact />

            <!-- User Profile Pill: Dynamic Authenticated User -->
            <button
                type="button"
                @click="emit('selectTab', 'profile')"
                class="flex items-center gap-2 pl-2 pr-3 h-9 rounded-lg dark:bg-[#111111] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/50 hover:border-[#00D084]/50 cursor-pointer transition-colors"
                :title="`Profil: ${props.userName} (${userRoleBadge.label})`"
            >
                <div class="w-7 h-7 rounded-full bg-[#00D084] text-[#062117] font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                    {{ userInitial }}
                </div>
                <div class="hidden sm:flex flex-col text-left leading-tight">
                    <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] truncate max-w-28">{{ props.userName }}</span>
                    <span class="text-[10px] font-medium" :class="userRoleBadge.colorClass">{{ userRoleBadge.label }}</span>
                </div>
                <ChevronDown class="w-3 h-3 dark:text-[#858585] text-[#737B71] hidden sm:inline" />
            </button>
        </div>
    </header>
</template>
