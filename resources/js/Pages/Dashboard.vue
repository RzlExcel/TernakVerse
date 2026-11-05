<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ChatbotDrawer from '@/components/ternakverse/ChatbotDrawer.vue';
import { type BreadcrumbItem } from '@/types';
import { KECAMATAN_LIST } from '@/data/banyumasData';
import {
    Globe,
    BarChart3,
    Map,
    Database,
    ShieldCheck,
    FileSearch,
    ArrowUpRight,
    Building2,
    MapPin,
    Activity,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

defineProps<{
    name?: string;
}>();

const totalKecamatan = computed(() => KECAMATAN_LIST.length);

const totalPopulasiTernak = computed(() =>
    KECAMATAN_LIST.reduce(
        (sum, kec) =>
            sum +
            kec.sapi_potong +
            kec.sapi_perah +
            kec.kambing +
            kec.domba +
            kec.ayam_potong +
            kec.ayam_petelur,
        0,
    ),
);

const totalPoskeswan = computed(() =>
    KECAMATAN_LIST.reduce((sum, kec) => sum + kec.poskeswan, 0),
);

const modules = [
    {
        title: 'Portal WebGIS Publik',
        description:
            'Eksplorasi peta interaktif sebaran populasi ternak, produksi komoditas, dan titik balai desa se-Kabupaten Banyumas.',
        href: '/',
        badge: 'Akses Publik',
        icon: Globe,
    },
    {
        title: 'Analitik Eksekutif Dinas',
        description:
            'Ringkasan KPI eksekutif, tren produksi susu & daging, serta pemantauan status ketahanan pangan hewani daerah.',
        href: '/dinas/dashboard',
        badge: 'Eksekutif',
        icon: BarChart3,
    },
    {
        title: 'Peta Kuantitatif Dinas',
        description:
            'Peta tematik choropleth multi-indikator dengan rincian kuartil komoditas dan sebaran spasial 27 kecamatan.',
        href: '/internal/dinas',
        badge: 'Spasial Dinas',
        icon: Map,
    },
    {
        title: 'Panel Kelola Data & Kamus Biologis',
        description:
            'Manajemen direktori populasi kecamatan, validasi parameter biologis ternak, dan pembaruan basis data.',
        href: '/admin/panel',
        badge: 'Data Master',
        icon: Database,
    },
    {
        title: 'Portal Operasional Admin',
        description:
            'Ruang kendali operasional terpadu untuk verifikasi laporan petugas lapangan dan sinkronisasi data spasial.',
        href: '/internal/admin',
        badge: 'Operasional',
        icon: ShieldCheck,
    },
    {
        title: 'Audit Trail Forensik',
        description:
            'Catatan riwayat perubahan data komoditas dan aktivitas otorisasi pengguna secara transparan dan akuntabel.',
        href: '/admin/audit-logs',
        badge: 'Keamanan RBAC',
        icon: FileSearch,
    },
];
</script>

<template>
    <Head title="Dashboard Komando - TernakVerse Banyumas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Top Welcome Banner -->
            <div class="rounded-2xl border border-[#E0E4DD] bg-white p-6 shadow-xs dark:border-[#1E1E1E] dark:bg-[#111111]">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-[#00D084]">
                            <Building2 class="h-3.5 w-3.5" />
                            <span>Dinas Perikanan dan Peternakan Kabupaten Banyumas</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-[#F5F5F5]">
                            Dasbor Terpadu Peternakan Banyumas
                        </h1>
                        <p class="max-w-2xl text-sm text-slate-600 dark:text-[#B3B3B3]">
                            Pilih modul operasional di bawah untuk menavigasi peta geospasial peternakan, dasbor analitik eksekutif, maupun pengelolaan basis data komoditas Kabupaten Banyumas.
                        </p>
                    </div>
                </div>

                <!-- Quick Summary Stats -->
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-[#E0E4DD] bg-slate-50/70 p-4 dark:border-[#1E1E1E] dark:bg-[#181818]">
                        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-[#B3B3B3]">
                            <span>Cakupan Wilayah</span>
                            <Map class="h-4 w-4 text-emerald-600 dark:text-[#00D084]" />
                        </div>
                        <div class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-900 dark:text-[#F5F5F5]">
                            {{ totalKecamatan }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-[#B3B3B3]">
                            Kecamatan Terpetakan Resmi BIG
                        </div>
                    </div>

                    <div class="rounded-xl border border-[#E0E4DD] bg-slate-50/70 p-4 dark:border-[#1E1E1E] dark:bg-[#181818]">
                        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-[#B3B3B3]">
                            <span>Simpul Administrasi</span>
                            <MapPin class="h-4 w-4 text-emerald-600 dark:text-[#00D084]" />
                        </div>
                        <div class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-900 dark:text-[#F5F5F5]">
                            331
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-[#B3B3B3]">
                            Titik Balai Desa &amp; Kelurahan
                        </div>
                    </div>

                    <div class="rounded-xl border border-[#E0E4DD] bg-slate-50/70 p-4 dark:border-[#1E1E1E] dark:bg-[#181818]">
                        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-[#B3B3B3]">
                            <span>Total Populasi Ternak</span>
                            <Activity class="h-4 w-4 text-emerald-600 dark:text-[#00D084]" />
                        </div>
                        <div class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-900 dark:text-[#F5F5F5]">
                            {{ totalPopulasiTernak.toLocaleString('id-ID') }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-[#B3B3B3]">
                            Ekor Ruminansia &amp; Unggas
                        </div>
                    </div>

                    <div class="rounded-xl border border-[#E0E4DD] bg-slate-50/70 p-4 dark:border-[#1E1E1E] dark:bg-[#181818]">
                        <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-[#B3B3B3]">
                            <span>Infrastruktur Layanan</span>
                            <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-[#00D084]" />
                        </div>
                        <div class="mt-2 font-mono text-2xl font-bold tabular-nums text-slate-900 dark:text-[#F5F5F5]">
                            {{ totalPoskeswan }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-[#B3B3B3]">
                            Unit Poskeswan &amp; RPH Aktif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Navigation Bento Grid -->
            <div>
                <h2 class="mb-3 text-xs font-semibold text-slate-500 dark:text-[#858585] tracking-normal">
                    Direktori modul sistem
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="mod in modules"
                        :key="mod.href"
                        :href="mod.href"
                        class="group flex flex-col justify-between rounded-2xl border border-[#E0E4DD] bg-white p-5 shadow-xs transition-colors duration-150 hover:border-emerald-500/60 hover:shadow-md focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none dark:border-[#1E1E1E] dark:bg-[#111111] dark:hover:border-emerald-500/60"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/15 dark:text-[#00D084]">
                                    <component :is="mod.icon" class="h-5 w-5" />
                                </div>
                                <span class="rounded-md border border-[#E0E4DD] bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:border-[#282828] dark:bg-[#181818] dark:text-[#B3B3B3]">
                                    {{ mod.badge }}
                                </span>
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900 group-hover:text-emerald-600 dark:text-[#F5F5F5] dark:group-hover:text-[#00D084]">
                                {{ mod.title }}
                            </h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-slate-600 dark:text-[#B3B3B3]">
                                {{ mod.description }}
                            </p>
                        </div>

                        <div class="mt-5 flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-[#00D084]">
                            <span>Buka Modul</span>
                            <ArrowUpRight class="h-3.5 w-3.5" />
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Chatbot Drawer FAB -->
            <ChatbotDrawer />
        </div>
    </AppLayout>
</template>
