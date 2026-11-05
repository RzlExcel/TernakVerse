<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    LayoutDashboard,
    FolderKanban,
    PlusSquare,
    ShieldAlert,
    Building2,
    FileDown,
    User,
    ShieldCheck,
    Compass,
    LogOut,
    X,
    Database,
    Wrench,
} from 'lucide-vue-next';
import type { AdminTab } from '../../../composables/ternakverse/admin/useAdminPortal';

export type ExtendedAdminTab = AdminTab | 'maintenance';

const props = defineProps<{
    currentTab: ExtendedAdminTab;
    isMobileMenuOpen: boolean;
    isSidebarCollapsed: boolean;
    isSuperAdmin?: boolean;
    userRole?: string;
}>();

const emit = defineEmits<{
    (e: 'selectTab', tab: ExtendedAdminTab): void;
    (e: 'startCreateNew'): void;
    (e: 'update:isMobileMenuOpen', val: boolean): void;
    (e: 'goToPublicPortal'): void;
    (e: 'goToDinasMap'): void;
    (e: 'logout'): void;
}>();

interface NavItem {
    id: ExtendedAdminTab;
    label: string;
    group: 'utama' | 'monitoring';
    icon: typeof LayoutDashboard;
}

const mainNavItems: NavItem[] = [
    { id: 'dashboard', label: 'Dashboard', group: 'utama', icon: LayoutDashboard },
    { id: 'webgis', label: 'WebGIS Command Center', group: 'utama', icon: Compass },
    { id: 'data', label: 'Data Peternakan', group: 'utama', icon: FolderKanban },
    { id: 'create', label: 'Input Data Baru', group: 'utama', icon: PlusSquare },
];

const allMonitoringNavItems: NavItem[] = [
    { id: 'ews', label: 'Monitoring EWS', group: 'monitoring', icon: ShieldAlert },
    { id: 'rph', label: 'Fasilitas Peternakan', group: 'monitoring', icon: Building2 },
    { id: 'master_data', label: 'Master Data', group: 'monitoring', icon: Database },
    { id: 'maintenance', label: 'Pemeliharaan Sistem', group: 'monitoring', icon: Wrench },
    { id: 'laporan', label: 'Ekspor Laporan', group: 'monitoring', icon: FileDown },
    { id: 'profile', label: 'Pengaturan Akun', group: 'monitoring', icon: User },
    { id: 'rbac', label: 'Role & Permission', group: 'monitoring', icon: ShieldCheck },
];

const monitoringNavItems = computed<NavItem[]>(() => {
    if (props.isSuperAdmin) {
        return allMonitoringNavItems;
    }
    const superAdminTabs: ExtendedAdminTab[] = ['master_data', 'maintenance', 'rbac'];
    return allMonitoringNavItems.filter((item) => !superAdminTabs.includes(item.id));
});

const currentLiveTime = ref<string>('');

function updateLiveTime(): void {
    const now = new Date();
    currentLiveTime.value = new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(now);
}

let clockInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    updateLiveTime();
    clockInterval = setInterval(updateLiveTime, 30000);
});

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
});

function handleNavClick(tab: ExtendedAdminTab): void {
    if (tab === 'create') {
        emit('startCreateNew');
    } else {
        emit('selectTab', tab);
    }
    emit('update:isMobileMenuOpen', false);
}
</script>

<template>
    <!-- ==================================================== -->
    <!-- DESKTOP COLLAPSIBLE SIDEBAR (>= 1024px)              -->
    <!-- ==================================================== -->
    <aside
        class="hidden lg:flex h-full flex-shrink-0 dark:bg-[#111111] bg-[#F4F5F2] border-r dark:border-[#1E1E1E] border-[#E0E4DD] flex-col justify-between select-none z-20 transition-all duration-200"
        :class="props.isSidebarCollapsed ? 'w-20 p-2' : 'w-60 p-3'"
    >
        <!-- BAGIAN MENU ATAS -->
        <div class="space-y-4 overflow-y-auto pr-0.5">
            <!-- Kategori 1: MENU UTAMA -->
            <div class="space-y-1">
                <div
                    v-if="!props.isSidebarCollapsed"
                    class="px-3 py-1 text-xs font-semibold tracking-normal dark:text-[#858585] text-[#737B71]"
                >
                    Menu Utama
                </div>
                <button
                    v-for="item in mainNavItems"
                    :key="item.id"
                    type="button"
                    @click="handleNavClick(item.id)"
                    :title="item.label"
                    class="w-full h-9 rounded-lg flex items-center text-xs font-medium transition-colors cursor-pointer"
                    :class="[
                        props.isSidebarCollapsed ? 'justify-center px-0' : 'text-left px-3 gap-2.5 justify-between',
                        props.currentTab === item.id
                            ? 'bg-[#171B17] text-white shadow-xs font-semibold dark:bg-[#181818] dark:text-[#F5F5F5]'
                            : 'text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] hover:dark:text-[#F5F5F5] hover:bg-[#EDEFEB] hover:dark:bg-[#1E1E1E]',
                    ]"
                >
                    <div class="flex items-center min-w-0" :class="props.isSidebarCollapsed ? 'justify-center' : 'gap-2.5'">
                        <component
                            :is="item.icon"
                            class="w-4 h-4 flex-shrink-0"
                            :class="props.currentTab === item.id ? 'text-[#00D084]' : 'text-[#737B71] dark:text-[#B3B3B3]'"
                        />
                        <span v-if="!props.isSidebarCollapsed" class="truncate">{{ item.label }}</span>
                    </div>
                </button>
            </div>

            <!-- Kategori 2: MONITORING & FASILITAS -->
            <div class="space-y-1">
                <div
                    v-if="!props.isSidebarCollapsed"
                    class="px-3 py-1 text-xs font-semibold tracking-normal dark:text-[#858585] text-[#737B71]"
                >
                    MONITORING &amp; FASILITAS
                </div>
                <button
                    v-for="item in monitoringNavItems"
                    :key="item.id"
                    type="button"
                    @click="handleNavClick(item.id)"
                    :title="item.label"
                    class="w-full h-9 rounded-lg flex items-center text-xs font-medium transition-colors cursor-pointer"
                    :class="[
                        props.isSidebarCollapsed ? 'justify-center px-0' : 'text-left px-3 gap-2.5 justify-between',
                        props.currentTab === item.id
                            ? 'bg-[#171B17] text-white shadow-xs font-semibold dark:bg-[#181818] dark:text-[#F5F5F5]'
                            : 'text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] hover:dark:text-[#F5F5F5] hover:bg-[#EDEFEB] hover:dark:bg-[#1E1E1E]',
                    ]"
                >
                    <div class="flex items-center min-w-0" :class="props.isSidebarCollapsed ? 'justify-center' : 'gap-2.5'">
                        <component
                            :is="item.icon"
                            class="w-4 h-4 flex-shrink-0"
                            :class="props.currentTab === item.id ? 'text-[#00D084]' : 'text-[#737B71] dark:text-[#B3B3B3]'"
                        />
                        <span v-if="!props.isSidebarCollapsed" class="truncate">{{ item.label }}</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- BAGIAN BAWAH: STATUS SINKRONISASI, TAUTAN EKSTERNAL & LOGOUT -->
        <div class="pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] space-y-2 flex-shrink-0">
            <!-- Operational Sync Status Card -->
            <div
                v-if="!props.isSidebarCollapsed"
                class="p-2.5 rounded-xl dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center gap-2.5 shadow-xs"
            >
                <span class="w-2 h-2 rounded-full bg-[#00D084] shrink-0"></span>
                <div class="min-w-0 flex-1">
                    <div class="text-[11px] font-bold dark:text-[#F5F5F5] text-[#171B17] leading-tight truncate">
                        Sistem Berjalan Normal
                    </div>
                    <div class="text-[10px] dark:text-[#858585] text-[#737B71] leading-tight mt-0.5 truncate">
                        Terakhir sinkron: {{ currentLiveTime || 'Sinkronisasi...' }}
                    </div>
                </div>
            </div>
            <div
                v-else
                class="w-full flex justify-center py-1"
                :title="`Sistem Berjalan Normal (Terakhir sinkron: ${currentLiveTime || 'Sinkronisasi...'})`"
            >
                <span class="w-2.5 h-2.5 rounded-full bg-[#00D084]"></span>
            </div>

            <!-- Tautan Eksternal & Logout -->
            <div class="space-y-1">
                <div
                    v-if="!props.isSidebarCollapsed"
                    class="px-2 text-xs font-semibold tracking-normal dark:text-[#858585] text-[#737B71]"
                >
                    Tautan Eksternal
                </div>

                <button
                    type="button"
                    @click="emit('goToDinasMap')"
                    title="WebGIS Dinas"
                    class="w-full h-8 rounded-lg dark:text-[#B3B3B3] text-[#444C43] hover:text-[#00D084] dark:hover:text-[#00D084] dark:hover:bg-[#1E1E1E] hover:bg-[#EDEFEB] font-medium text-xs flex items-center transition-colors cursor-pointer"
                    :class="props.isSidebarCollapsed ? 'justify-center px-0' : 'px-3 gap-2'"
                >
                    <Compass class="w-3.5 h-3.5 text-[#00D084] flex-shrink-0" />
                    <span v-if="!props.isSidebarCollapsed">WebGIS Dinas</span>
                </button>

                <button
                    type="button"
                    @click="emit('logout')"
                    title="Keluar Sesi"
                    class="w-full h-8 rounded-lg text-[#DC625F] hover:text-[#EF8784] dark:hover:bg-[#181818] hover:bg-[#FBEAEA] font-medium text-xs flex items-center transition-colors mt-1 cursor-pointer"
                    :class="props.isSidebarCollapsed ? 'justify-center px-0' : 'px-3 gap-2'"
                >
                    <LogOut class="w-3.5 h-3.5 flex-shrink-0" />
                    <span v-if="!props.isSidebarCollapsed">Keluar Sesi</span>
                </button>
            </div>

            <!-- App Branding Footer -->
            <div
                v-if="!props.isSidebarCollapsed"
                class="px-2 pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] dark:text-[#858585] text-[#737B71] leading-snug"
            >
                <div class="font-bold dark:text-[#F5F5F5] text-[#171B17] tracking-wide">TERNAKVERSE</div>
                <div class="text-[9px] dark:text-[#858585] text-[#737B71]">Dinas Perikanan dan Peternakan Kabupaten Banyumas</div>
            </div>
        </div>
    </aside>

    <!-- ==================================================== -->
    <!-- MOBILE SLIDE-OVER NAVIGATION DRAWER (< 1024px)       -->
    <!-- ==================================================== -->
    <div
        v-if="props.isMobileMenuOpen"
        class="fixed inset-0 z-50 lg:hidden flex"
        role="dialog"
        aria-modal="true"
        aria-label="Navigasi Panel Admin"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            @click="emit('update:isMobileMenuOpen', false)"
        ></div>

        <!-- Drawer Panel -->
        <aside class="relative z-10 w-72 max-w-[85vw] h-full dark:bg-[#111111] bg-[#F4F5F2] border-r dark:border-[#1E1E1E] border-[#E0E4DD] p-4 flex flex-col justify-between select-none shadow-2xl">
            <div class="space-y-4 overflow-y-auto">
                <!-- Header Drawer -->
                <div class="flex items-center justify-between pb-3 border-b dark:border-[#1E1E1E] border-[#E0E4DD]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center justify-center text-[#00D084] shadow-xs shrink-0">
                            <Compass class="w-4 h-4 text-[#00D084] stroke-[2.2]" />
                        </div>
                        <div>
                            <div class="font-display font-extrabold text-sm dark:text-[#F5F5F5] text-[#171B17] leading-none">
                                TERNAKVERSE
                            </div>
                            <span class="text-[10px] font-mono text-[#00D084] font-bold">PANEL ADMIN</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('update:isMobileMenuOpen', false)"
                        class="w-8 h-8 rounded-lg border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#181818] bg-white dark:text-[#B3B3B3] text-[#444C43] hover:dark:text-[#F5F5F5] hover:text-[#171B17] flex items-center justify-center cursor-pointer transition-colors"
                        aria-label="Tutup menu navigasi"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- MENU UTAMA -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-xs font-semibold tracking-normal dark:text-[#858585] text-[#737B71]">
                        Menu Utama
                    </div>
                    <button
                        v-for="item in mainNavItems"
                        :key="item.id"
                        type="button"
                        @click="handleNavClick(item.id)"
                        class="w-full text-left px-3 h-10 rounded-lg flex items-center justify-between text-xs font-medium transition-colors cursor-pointer"
                        :class="
                            props.currentTab === item.id
                                ? 'bg-[#171B17] text-white shadow-xs font-semibold dark:bg-[#181818] dark:text-[#F5F5F5]'
                                : 'text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] hover:dark:text-[#F5F5F5] hover:bg-[#EDEFEB] hover:dark:bg-[#1E1E1E]'
                        "
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <component
                                :is="item.icon"
                                class="w-4 h-4 flex-shrink-0"
                                :class="props.currentTab === item.id ? 'text-[#00D084]' : 'text-[#737B71] dark:text-[#B3B3B3]'"
                            />
                            <span class="truncate">{{ item.label }}</span>
                        </div>
                    </button>
                </div>

                <!-- MONITORING & FASILITAS -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-xs font-semibold tracking-normal dark:text-[#858585] text-[#737B71]">
                        MONITORING &amp; FASILITAS
                    </div>
                    <button
                        v-for="item in monitoringNavItems"
                        :key="item.id"
                        type="button"
                        @click="handleNavClick(item.id)"
                        class="w-full text-left px-3 h-10 rounded-lg flex items-center justify-between text-xs font-medium transition-colors cursor-pointer"
                        :class="
                            props.currentTab === item.id
                                ? 'bg-[#171B17] text-white shadow-xs font-semibold dark:bg-[#181818] dark:text-[#F5F5F5]'
                                : 'text-[#444C43] dark:text-[#B3B3B3] hover:text-[#171B17] hover:dark:text-[#F5F5F5] hover:bg-[#EDEFEB] hover:dark:bg-[#1E1E1E]'
                        "
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <component
                                :is="item.icon"
                                class="w-4 h-4 flex-shrink-0"
                                :class="props.currentTab === item.id ? 'text-[#00D084]' : 'text-[#737B71] dark:text-[#B3B3B3]'"
                            />
                            <span class="truncate">{{ item.label }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer Drawer -->
            <div class="pt-3 border-t dark:border-[#1E1E1E] border-[#E0E4DD] space-y-2 flex-shrink-0">
                <!-- Mobile Sync Status Card -->
                <div class="p-2.5 rounded-xl dark:bg-[#181818] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] flex items-center gap-2.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-[#00D084] shrink-0"></span>
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold dark:text-[#F5F5F5] text-[#171B17] leading-tight">
                            Sistem Berjalan Normal
                        </div>
                        <div class="text-[10px] dark:text-[#858585] text-[#737B71] leading-tight mt-0.5">
                            Terakhir sinkron: {{ currentLiveTime || 'Sinkronisasi...' }}
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <button
                        type="button"
                        @click="emit('goToDinasMap')"
                        class="w-full h-9 px-3 rounded-lg dark:text-[#B3B3B3] text-[#444C43] hover:text-[#00D084] dark:hover:text-[#00D084] dark:hover:bg-[#1E1E1E] hover:bg-[#EDEFEB] font-medium text-xs flex items-center gap-2 transition-colors cursor-pointer"
                    >
                        <Compass class="w-4 h-4 text-[#00D084]" />
                        <span>WebGIS Dinas</span>
                    </button>
                    <button
                        type="button"
                        @click="emit('logout')"
                        class="w-full h-9 px-3 rounded-lg text-[#DC625F] hover:text-[#EF8784] dark:hover:bg-[#181818] hover:bg-[#FBEAEA] font-medium text-xs flex items-center gap-2 transition-colors cursor-pointer"
                    >
                        <LogOut class="w-4 h-4" />
                        <span>Keluar Sesi</span>
                    </button>
                </div>

                <!-- Drawer Branding -->
                <div class="px-2 pt-1 border-t dark:border-[#1E1E1E] border-[#E0E4DD] text-[10px] dark:text-[#858585] text-[#737B71]">
                    <div class="font-bold dark:text-[#F5F5F5] text-[#171B17]">TERNAKVERSE</div>
                    <div class="text-[9px] dark:text-[#858585] text-[#737B71]">Dinas Perikanan dan Peternakan Banyumas</div>
                </div>
            </div>
        </aside>
    </div>
</template>
