<script lang="ts">
export interface ActivityRow {
    tanggal: string;
    aktivitas: string;
    badgeClass: string;
    komoditas: string;
    lokasi: string;
    petugas: string;
}

export interface HistoryFeedItem {
    icon: 'trending-up' | 'users' | 'syringe' | 'building' | 'arrows';
    title: string;
    timestamp: string;
    delta: string;
    badgeTheme: 'emerald' | 'teal';
}

export interface EwsNotificationItem {
    title: string;
    timestamp: string;
    status: string;
    statusBadgeClass: string;
    iconTheme: 'danger' | 'warning' | 'safe';
}

export interface AdminOperationalRowProps {
    activities?: ActivityRow[];
    feedItems?: HistoryFeedItem[];
    ewsAlerts?: EwsNotificationItem[];
}

export function formatIndonesianTimestamp(date: Date = new Date(), withComma = true): string {
    const formatted = new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date).replace(/\./g, ':');
    return withComma ? (formatted.includes(',') ? formatted : formatted.replace(/(\d{4})\s+/, '$1, ')) : formatted.replace(',', '');
}

const defaultActivities: ActivityRow[] = [
    {
        tanggal: '14 Okt 2026 10:24',
        aktivitas: 'Vaksinasi',
        badgeClass: 'dark:bg-[#00D084]/15 dark:text-[#00D084] dark:border-[#00D084]/30 bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] px-2 py-0.5 rounded-full font-semibold',
        komoditas: 'Sapi Potong',
        lokasi: 'Sumbang',
        petugas: 'Dr. Hendrat',
    },
    {
        tanggal: '14 Okt 2026 09:17',
        aktivitas: 'Pencatatan Populasi',
        badgeClass: 'dark:bg-[#32B9D0]/15 dark:text-[#32B9D0] dark:border-[#32B9D0]/30 bg-cyan-50 text-cyan-800 border border-cyan-200 text-[10px] px-2 py-0.5 rounded-full font-semibold',
        komoditas: 'Sapi Perah',
        lokasi: 'Purwokerto Selatan',
        petugas: 'Siti Aminah',
    },
    {
        tanggal: '13 Okt 2026 15:43',
        aktivitas: 'Mutasi Ternak',
        badgeClass: 'dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/30 bg-amber-50 text-amber-800 border border-amber-200 text-[10px] px-2 py-0.5 rounded-full font-semibold',
        komoditas: 'Kambing',
        lokasi: 'Baturraden',
        petugas: 'Agus Setiawan',
    },
    {
        tanggal: formatIndonesianTimestamp(new Date(), false),
        aktivitas: 'Pemeriksaan Kesehatan',
        badgeClass: 'dark:bg-[#DC625F]/15 dark:text-[#DC625F] dark:border-[#DC625F]/30 bg-rose-50 text-rose-800 border border-rose-200 text-[10px] px-2 py-0.5 rounded-full font-semibold',
        komoditas: 'Sapi Potong',
        lokasi: 'Kedungbanteng',
        petugas: 'drh. Rina',
    },
    {
        tanggal: '12 Okt 2026 16:32',
        aktivitas: 'Input Data Baru',
        badgeClass: 'dark:bg-[#32B9D0]/15 dark:text-[#32B9D0] dark:border-[#32B9D0]/30 bg-cyan-50 text-cyan-800 border border-cyan-200 text-[10px] px-2 py-0.5 rounded-full font-semibold',
        komoditas: 'Sapi Perah',
        lokasi: 'Cilongok',
        petugas: 'Budi Santoso',
    },
];

const defaultFeedItems: HistoryFeedItem[] = [
    {
        icon: 'trending-up',
        title: 'Data populasi sapi potong',
        timestamp: formatIndonesianTimestamp(new Date(), true),
        delta: '+ 1.248 ekor',
        badgeTheme: 'emerald',
    },
    {
        icon: 'users',
        title: 'Data peternak baru',
        timestamp: '14 Okt 2026, 10:55',
        delta: '+ 12 orang',
        badgeTheme: 'teal',
    },
    {
        icon: 'syringe',
        title: 'Data vaksinasi PMK',
        timestamp: '14 Okt 2026, 09:32',
        delta: '+ 320 ekor',
        badgeTheme: 'emerald',
    },
    {
        icon: 'building',
        title: 'Data fasilitas RPH',
        timestamp: '13 Okt 2026, 16:12',
        delta: '+ 1 lokasi',
        badgeTheme: 'teal',
    },
    {
        icon: 'arrows',
        title: 'Data mutasi ternak',
        timestamp: '12 Okt 2026, 14:45',
        delta: '+ 58 ekor',
        badgeTheme: 'emerald',
    },
];

const defaultEwsAlerts: EwsNotificationItem[] = [
    {
        title: 'Kasus PMK meningkat di Kec. Sumbang',
        timestamp: '14 Okt 2026, 08:12',
        status: 'Waspada',
        statusBadgeClass: 'dark:bg-[#DC625F]/15 dark:text-[#DC625F] dark:border-[#DC625F]/30 bg-rose-50 text-rose-800 border border-rose-200 text-[10px] px-2 py-0.5 rounded font-bold',
        iconTheme: 'danger',
    },
    {
        title: 'Laporan LSD di Kec. Baturraden',
        timestamp: '13 Okt 2026, 15:07',
        status: 'Perhatian',
        statusBadgeClass: 'dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/30 bg-amber-50 text-amber-800 border border-amber-200 text-[10px] px-2 py-0.5 rounded font-bold',
        iconTheme: 'warning',
    },
    {
        title: 'Status zona aman di Kec. Wangon',
        timestamp: '12 Okt 2026, 10:33',
        status: 'Aman',
        statusBadgeClass: 'dark:bg-[#00D084]/15 dark:text-[#00D084] dark:border-[#00D084]/30 bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] px-2 py-0.5 rounded font-bold',
        iconTheme: 'safe',
    },
];
</script>

<script setup lang="ts">
import {
    Activity,
    FileText,
    TrendingUp,
    Users,
    Syringe,
    Building2,
    ArrowLeftRight,
    RefreshCw,
    AlertTriangle,
    ShieldAlert,
    CheckCircle2,
} from 'lucide-vue-next';

const props = withDefaults(defineProps<AdminOperationalRowProps>(), {
    activities: () => defaultActivities,
    feedItems: () => defaultFeedItems,
    ewsAlerts: () => defaultEwsAlerts,
});

const emit = defineEmits<{
    (e: 'selectTab', tab: string): void;
}>();

const liveSyncTimestamp = formatIndonesianTimestamp(new Date(), true);
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 w-full select-none font-ui">
        <!-- COL 1 (lg:col-span-5): Aktivitas & Mutasi Ternak -->
        <div class="lg:col-span-5 dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between transition-colors">
            <div>
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[#00D084] flex items-center justify-center flex-shrink-0">
                            <Activity class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base dark:text-[#F5F5F5] text-[#171B17] tracking-tight leading-snug">
                                Aktivitas &amp; Mutasi Ternak
                            </h3>
                            <p class="text-xs dark:text-[#858585] text-[#737B71]">
                                Log operasional data dan verifikasi lapangan
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('selectTab', 'data')"
                        class="text-xs font-semibold text-[#00D084] hover:text-[#00F0A0] transition-colors inline-flex items-center gap-1 cursor-pointer flex-shrink-0"
                    >
                        <span>Lihat Semua</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="dark:text-[#858585] text-[#737B71] text-[11px] font-semibold border-b dark:border-[#1E1E1E] border-[#E0E4DD] pb-2">
                                <th class="pb-2.5 font-semibold pr-3 whitespace-nowrap">Tanggal</th>
                                <th class="pb-2.5 font-semibold pr-3 whitespace-nowrap">Jenis Aktivitas</th>
                                <th class="pb-2.5 font-semibold pr-3 whitespace-nowrap">Komoditas</th>
                                <th class="pb-2.5 font-semibold pr-3 whitespace-nowrap">Lokasi</th>
                                <th class="pb-2.5 font-semibold whitespace-nowrap">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-[#1E1E1E] divide-[#E0E4DD] text-xs">
                            <tr
                                v-for="(item, idx) in props.activities"
                                :key="idx"
                                class="hover:dark:bg-[#181818] hover:bg-[#F4F5F2] transition-colors"
                            >
                                <td class="py-2.5 pr-3 font-mono tabular-nums dark:text-[#B3B3B3] text-[#444C43] text-[11px] whitespace-nowrap">
                                    {{ item.tanggal }}
                                </td>
                                <td class="py-2.5 pr-3 whitespace-nowrap">
                                    <span :class="item.badgeClass">
                                        {{ item.aktivitas }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-3 dark:text-[#F5F5F5] text-[#171B17] font-medium whitespace-nowrap">
                                    {{ item.komoditas }}
                                </td>
                                <td class="py-2.5 pr-3 dark:text-[#B3B3B3] text-[#444C43] whitespace-nowrap">
                                    {{ item.lokasi }}
                                </td>
                                <td class="py-2.5 dark:text-[#858585] text-[#737B71] whitespace-nowrap">
                                    {{ item.petugas }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- COL 2 (lg:col-span-3): Riwayat Input Data -->
        <div class="lg:col-span-3 dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-5 shadow-xs flex flex-col justify-between transition-colors">
            <div>
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[#00D084] flex items-center justify-center flex-shrink-0">
                            <FileText class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-display font-bold text-base dark:text-[#F5F5F5] text-[#171B17] tracking-tight leading-snug truncate">
                                Riwayat Input Data
                            </h3>
                            <p class="text-xs dark:text-[#858585] text-[#737B71] truncate">
                                Data terbaru yang masuk ke sistem
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('selectTab', 'data')"
                        class="text-xs font-semibold text-[#00D084] hover:text-[#00F0A0] transition-colors inline-flex items-center gap-1 cursor-pointer flex-shrink-0"
                    >
                        <span>Lihat Semua</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>

                <!-- Transaction Feed List -->
                <div class="space-y-3">
                    <div
                        v-for="(item, idx) in props.feedItems"
                        :key="idx"
                        class="flex items-center justify-between gap-3 p-2 rounded-lg dark:bg-[#181818]/60 bg-[#F4F5F2] hover:dark:bg-[#181818] hover:bg-[#E4E8E1] border dark:border-[#1E1E1E] border-[#E0E4DD] transition-colors"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="
                                    item.badgeTheme === 'emerald'
                                        ? 'dark:bg-[#00D084]/15 bg-[#00D084]/15 border dark:border-[#00D084]/30 border-[#00D084]/30 text-[#00D084]'
                                        : 'dark:bg-[#32B9D0]/15 bg-[#32B9D0]/15 border dark:border-[#32B9D0]/30 border-[#32B9D0]/30 text-[#32B9D0]'
                                "
                            >
                                <TrendingUp v-if="item.icon === 'trending-up'" class="w-3.5 h-3.5 stroke-[2.2]" />
                                <Users v-else-if="item.icon === 'users'" class="w-3.5 h-3.5 stroke-[2.2]" />
                                <Syringe v-else-if="item.icon === 'syringe'" class="w-3.5 h-3.5 stroke-[2.2]" />
                                <Building2 v-else-if="item.icon === 'building'" class="w-3.5 h-3.5 stroke-[2.2]" />
                                <ArrowLeftRight v-else class="w-3.5 h-3.5 stroke-[2.2]" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold dark:text-[#F5F5F5] text-[#171B17] leading-tight truncate">
                                    {{ item.title }}
                                </p>
                                <p class="text-[11px] dark:text-[#858585] text-[#737B71] font-mono mt-0.5 truncate">
                                    {{ item.timestamp }}
                                </p>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="text-[#00D084] font-mono tabular-nums font-bold text-xs whitespace-nowrap">
                                {{ item.delta }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COL 3 (lg:col-span-4): Stacked Status Widgets -->
        <div class="lg:col-span-4 flex flex-col gap-4">
            <!-- Top Widget: Status Sinkronisasi -->
            <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-4 shadow-xs transition-colors">
                <div class="flex items-center justify-between mb-1">
                    <div>
                        <h4 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] tracking-tight">
                            Status Sinkronisasi
                        </h4>
                        <p class="text-[11px] dark:text-[#858585] text-[#737B71]">
                            Semua data telah tersinkronisasi
                        </p>
                    </div>
                    <div class="w-7 h-7 rounded-lg dark:bg-[#181818] bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] text-[#00D084] flex items-center justify-center flex-shrink-0">
                        <RefreshCw class="w-3.5 h-3.5" />
                    </div>
                </div>

                <!-- Success Status Pill -->
                <div class="dark:bg-[#00D084]/15 dark:text-[#00D084] dark:border-[#00D084]/30 bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs px-2.5 py-1 rounded-md font-semibold flex items-center gap-1.5 w-fit my-2">
                    <span class="text-[#00D084] font-bold">&#10004;</span>
                    <span>Sinkronisasi Berhasil</span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2 rounded-full dark:bg-[#181818] bg-[#E4E8E1] overflow-hidden">
                    <div class="h-full bg-[#00D084] rounded-full w-full"></div>
                </div>

                <!-- Text Label -->
                <div class="text-right mt-1">
                    <span class="font-mono tabular-nums text-xs dark:text-[#858585] text-[#737B71]">100%</span>
                </div>

                <!-- Footer -->
                <p class="text-[11px] dark:text-[#858585] text-[#737B71] font-mono mt-1">
                    Terakhir sync: {{ liveSyncTimestamp }}
                </p>
            </div>

            <!-- Bottom Widget: Notifikasi EWS -->
            <div class="dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] rounded-xl p-4 shadow-xs flex flex-col justify-between transition-colors">
                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg dark:bg-[#DC625F]/15 bg-rose-50 border dark:border-[#DC625F]/30 border-rose-200 text-[#DC625F] flex items-center justify-center flex-shrink-0">
                                <AlertTriangle class="w-3.5 h-3.5" />
                            </div>
                            <h4 class="font-display font-bold text-sm dark:text-[#F5F5F5] text-[#171B17] tracking-tight">
                                Notifikasi EWS
                            </h4>
                        </div>
                        <button
                            type="button"
                            @click="emit('selectTab', 'ews')"
                            class="text-xs font-semibold text-[#00D084] hover:text-[#00F0A0] transition-colors inline-flex items-center gap-1 cursor-pointer flex-shrink-0"
                        >
                            <span>Lihat Semua</span>
                            <span aria-hidden="true">&rarr;</span>
                        </button>
                    </div>

                    <!-- 3 Alert Items -->
                    <div class="space-y-2.5">
                        <div
                            v-for="(alert, idx) in props.ewsAlerts"
                            :key="idx"
                            class="flex items-center justify-between gap-3 p-2 rounded-lg dark:bg-[#181818]/60 bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:bg-[#181818] hover:bg-[#E4E8E1] transition-colors"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                    :class="{
                                        'dark:bg-[#DC625F]/15 bg-rose-50 border dark:border-[#DC625F]/30 border-rose-200 text-[#DC625F]': alert.iconTheme === 'danger',
                                        'dark:bg-amber-950/40 bg-amber-50 border dark:border-amber-500/30 border-amber-200 text-amber-500': alert.iconTheme === 'warning',
                                        'dark:bg-[#00D084]/15 bg-emerald-50 border dark:border-[#00D084]/30 border-emerald-200 text-[#00D084]': alert.iconTheme === 'safe',
                                    }"
                                >
                                    <AlertTriangle v-if="alert.iconTheme === 'danger'" class="w-3.5 h-3.5" />
                                    <ShieldAlert v-else-if="alert.iconTheme === 'warning'" class="w-3.5 h-3.5" />
                                    <CheckCircle2 v-else class="w-3.5 h-3.5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold dark:text-[#F5F5F5] text-[#171B17] leading-tight truncate">
                                        {{ alert.title }}
                                    </p>
                                    <p class="text-[10px] dark:text-[#858585] text-[#737B71] font-mono mt-0.5 truncate">
                                        {{ alert.timestamp }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <span :class="alert.statusBadgeClass">
                                    {{ alert.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
