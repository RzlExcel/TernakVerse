<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    Bell,
    AlertTriangle,
    AlertCircle,
    Leaf,
    CheckCircle2,
    Clock,
    ArrowRight,
    CheckCheck,
    ShieldAlert,
} from 'lucide-vue-next';
import type { AdminTab } from '../../../composables/ternakverse/admin/useAdminPortal';

export type NotificationSeverity = 'critical' | 'warning' | 'notice' | 'success';

export interface NotificationItem {
    id: string;
    title: string;
    description: string;
    time: string;
    severity: NotificationSeverity;
    badgeLabel: string;
    tab: AdminTab;
    kecamatan?: string;
    actionLabel: string;
    read: boolean;
}

export interface NotificationNavigatePayload {
    tab: AdminTab;
    kecamatan?: string;
}

const props = withDefaults(
    defineProps<{
        isOpen?: boolean;
    }>(),
    {
        isOpen: true,
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'navigate', payload: NotificationNavigatePayload): void;
    (e: 'selectTab', tab: AdminTab): void;
    (e: 'update:unreadCount', count: number): void;
}>();

const popoverRef = ref<HTMLElement | null>(null);

const notifications = ref<NotificationItem[]>([
    {
        id: 'pmk-wangon',
        title: 'Lonjakan Kasus PMK di Wangon',
        description: 'Terdeteksi 19 kasus aktif PMK baru pada populasi sapi potong di Kecamatan Wangon.',
        time: '10 menit yang lalu',
        severity: 'critical',
        badgeLabel: 'Kritis',
        tab: 'ews',
        kecamatan: 'Wangon',
        actionLabel: 'Fokus EWS Wangon',
        read: false,
    },
    {
        id: 'lsd-lumbir',
        title: 'Waspada Gejala LSD di Lumbir',
        description: 'Laporan 14 kasus suspek LSD pada ternak sapi potong di wilayah perbatasan.',
        time: '45 menit yang lalu',
        severity: 'warning',
        badgeLabel: 'Waspada',
        tab: 'ews',
        kecamatan: 'Lumbir',
        actionLabel: 'Pantau EWS Lumbir',
        read: false,
    },
    {
        id: 'hmt-baturraden',
        title: 'Peringatan Daya Dukung Pakan Baturraden',
        description: 'Indeks daya dukung pakan 0.58 (Defisit HMT terhadap 28.915 ST ternak).',
        time: '2 jam yang lalu',
        severity: 'notice',
        badgeLabel: 'Defisit Pakan',
        tab: 'master_data',
        kecamatan: 'Baturraden',
        actionLabel: 'Master Data HMT',
        read: false,
    },
    {
        id: 'postgis-sync',
        title: 'Sinkronisasi Balai Desa Berhasil',
        description: '331 titik geospasial balai desa Kabupaten Banyumas berhasil terverifikasi.',
        time: 'Hari ini, 08:30',
        severity: 'success',
        badgeLabel: 'Tersinkronisasi',
        tab: 'webgis',
        actionLabel: 'WebGIS Command Center',
        read: true,
    },
]);

const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length);

function markAllAsRead(): void {
    notifications.value.forEach((item) => {
        item.read = true;
    });
    emit('update:unreadCount', 0);
}

function handleItemClick(item: NotificationItem): void {
    item.read = true;
    emit('update:unreadCount', unreadCount.value);
    emit('navigate', { tab: item.tab, kecamatan: item.kecamatan });
    emit('selectTab', item.tab);
    emit('close');
}

function handleViewAllEws(): void {
    emit('selectTab', 'ews');
    emit('navigate', { tab: 'ews' });
    emit('close');
}

function handleDocumentClick(event: MouseEvent): void {
    if (popoverRef.value && !popoverRef.value.contains(event.target as Node)) {
        emit('close');
    }
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');
    }
}

onMounted(() => {
    emit('update:unreadCount', unreadCount.value);
    window.addEventListener('keydown', handleKeydown);
    // Timeout to ensure trigger click does not immediately close popover
    setTimeout(() => {
        document.addEventListener('click', handleDocumentClick);
    }, 0);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <div
        ref="popoverRef"
        class="absolute right-0 top-full mt-2 w-80 sm:w-[420px] rounded-xl bg-white dark:bg-[#141414] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] shadow-2xl z-50 overflow-hidden focus:outline-none transition-all"
        role="dialog"
        aria-label="Pusat Peringatan &amp; Notifikasi Dini"
    >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-between bg-[#FBFBFA] dark:bg-[#111111]">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md dark:bg-[#1E1E1E] bg-[#EAECE8] flex items-center justify-center dark:text-[#00D084] text-[#171B17]">
                    <Bell class="w-3.5 h-3.5" />
                </div>
                <h3 class="font-display font-bold text-xs dark:text-[#F5F5F5] text-[#171B17] tracking-tight">
                    Pusat Peringatan &amp; Notifikasi Dini
                </h3>
                <span
                    v-if="unreadCount > 0"
                    class="px-1.5 py-0.5 rounded-full text-[10px] font-bold font-mono dark:bg-[#DC625F]/20 dark:text-[#FF7D79] dark:border dark:border-[#DC625F]/30 bg-rose-50 text-rose-700 border border-rose-200"
                >
                    {{ unreadCount }} Baru
                </span>
            </div>
            <button
                v-if="unreadCount > 0"
                type="button"
                @click="markAllAsRead"
                class="text-[11px] font-medium text-[#00D084] hover:text-[#00B975] dark:hover:text-[#00F0A0] transition-colors cursor-pointer flex items-center gap-1 focus:outline-none focus-visible:underline"
                title="Tandai semua notifikasi sudah dibaca"
            >
                <CheckCheck class="w-3.5 h-3.5 stroke-[2.2]" />
                <span class="hidden sm:inline">Tandai Semua Dibaca</span>
                <span class="sm:hidden">Baca Semua</span>
            </button>
        </div>

        <!-- Notification List -->
        <div class="max-h-[380px] overflow-y-auto divide-y divide-[#E0E4DD]/60 dark:divide-[#1E1E1E]/80">
            <div
                v-for="item in notifications"
                :key="item.id"
                role="button"
                tabindex="0"
                @click="handleItemClick(item)"
                @keydown.enter.space.prevent="handleItemClick(item)"
                class="p-3.5 flex items-start gap-3 transition-colors cursor-pointer group text-left focus:outline-none focus-visible:ring-1 focus-visible:ring-[#00D084]"
                :class="[
                    item.read
                        ? 'dark:bg-[#141414] bg-white opacity-85 hover:opacity-100 hover:dark:bg-[#1A1A1A] hover:bg-[#F8F9F7]'
                        : 'dark:bg-[#181818]/90 bg-[#F7F9F6] hover:dark:bg-[#1F1F1F] hover:bg-[#EFF3EE]'
                ]"
            >
                <!-- Severity Icon Badge -->
                <div class="shrink-0 mt-0.5">
                    <div
                        v-if="item.severity === 'critical'"
                        class="w-8 h-8 rounded-lg dark:bg-[#DC625F]/20 dark:text-[#FF7D79] dark:border-[#DC625F]/30 bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shadow-xs"
                    >
                        <AlertTriangle class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div
                        v-else-if="item.severity === 'warning'"
                        class="w-8 h-8 rounded-lg dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-500/30 bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shadow-xs"
                    >
                        <AlertCircle class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div
                        v-else-if="item.severity === 'notice'"
                        class="w-8 h-8 rounded-lg dark:bg-[#32B9D0]/20 dark:text-[#45D1E8] dark:border-[#32B9D0]/30 bg-cyan-50 text-cyan-700 border border-cyan-200 flex items-center justify-center shadow-xs"
                    >
                        <Leaf class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div
                        v-else
                        class="w-8 h-8 rounded-lg dark:bg-[#00D084]/20 dark:text-[#00D084] dark:border-[#00D084]/30 bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shadow-xs"
                    >
                        <CheckCircle2 class="w-4 h-4 stroke-[2.2]" />
                    </div>
                </div>

                <!-- Text Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1.5">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <!-- Severity Pill -->
                            <span
                                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider shrink-0"
                                :class="[
                                    item.severity === 'critical'
                                        ? 'dark:bg-[#DC625F]/20 dark:text-[#FF7D79] dark:border-[#DC625F]/40 bg-rose-50 text-rose-700 border border-rose-200'
                                        : item.severity === 'warning'
                                        ? 'dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/40 bg-amber-50 text-amber-800 border border-amber-200'
                                        : item.severity === 'notice'
                                        ? 'dark:bg-[#32B9D0]/20 dark:text-[#45D1E8] dark:border-[#32B9D0]/40 bg-cyan-50 text-cyan-800 border border-cyan-200'
                                        : 'dark:bg-[#00D084]/20 dark:text-[#00D084] dark:border-[#00D084]/40 bg-emerald-50 text-emerald-800 border border-emerald-200'
                                ]"
                            >
                                {{ item.badgeLabel }}
                            </span>
                            <span class="text-xs font-bold dark:text-[#F5F5F5] text-[#171B17] truncate group-hover:text-[#00D084] transition-colors">
                                {{ item.title }}
                            </span>
                        </div>
                        <span
                            v-if="!item.read"
                            class="w-2 h-2 rounded-full bg-[#00D084] shrink-0 ring-2 dark:ring-[#141414] ring-white"
                            title="Belum dibaca"
                        ></span>
                    </div>

                    <p class="text-xs dark:text-[#B3B3B3] text-[#444C43] leading-relaxed mt-1">
                        {{ item.description }}
                    </p>

                    <div class="flex items-center justify-between gap-2 mt-2 pt-0.5">
                        <div class="flex items-center gap-1 text-[11px] font-mono dark:text-[#858585] text-[#737B71]">
                            <Clock class="w-3 h-3" />
                            <span>{{ item.time }}</span>
                        </div>
                        <div class="flex items-center gap-1 text-[10px] font-semibold text-[#00D084] group-hover:underline">
                            <span>{{ item.actionLabel }}</span>
                            <ArrowRight class="w-3 h-3 stroke-[2.2] group-hover:translate-x-0.5 transition-transform" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#FBFBFA] dark:bg-[#111111]">
            <button
                type="button"
                @click="handleViewAllEws"
                class="w-full py-2 px-3 rounded-lg text-xs font-semibold bg-[#00D084] hover:bg-[#00B975] text-[#062117] transition-all shadow-xs cursor-pointer flex items-center justify-center gap-2 active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#00D084]"
            >
                <ShieldAlert class="w-4 h-4 stroke-[2.2]" />
                <span>Lihat Semua di Pusat Monitoring EWS</span>
                <ArrowRight class="w-3.5 h-3.5 stroke-[2.5]" />
            </button>
        </div>
    </div>
</template>
