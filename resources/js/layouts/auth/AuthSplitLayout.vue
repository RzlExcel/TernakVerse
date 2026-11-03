<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Map, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

interface QuoteProp {
    message?: string;
    author?: string;
}

const page = usePage();
const quote = computed<QuoteProp | undefined>(() => page.props.quote as QuoteProp | undefined);

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid min-h-dvh flex-col items-center justify-center bg-slate-50 px-6 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0 dark:bg-[#090909]">
        <!-- Theme Toggle Chip in Top Right Header -->
        <div class="absolute top-4 right-4 z-20 flex items-center gap-2">
            <ThemeToggle size="sm" compact />
        </div>

        <div class="relative hidden h-full flex-col justify-between border-r border-slate-800 dark:border-[#1E1E1E] bg-[#111111] p-10 text-white lg:flex">
            <div class="space-y-4">
                <Link href="/" class="inline-flex items-center gap-2 text-lg font-medium">
                    <AppLogo class="!text-white" />
                </Link>
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-400"
                >
                    <ShieldCheck class="size-3.5 shrink-0" />
                    <span>Dinas Perikanan dan Peternakan Kabupaten Banyumas</span>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                    <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-400">
                        <Map class="size-4" />
                        <span>Sistem Informasi Geospasial Peternakan</span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-300">
                        Platform komando spasial, analitik populasi ternak, dan pengawasan ketahanan pangan hewani terpadu di wilayah Kabupaten Banyumas.
                    </p>
                </div>

                <blockquote v-if="quote?.message" class="space-y-2 border-l-2 border-emerald-500 pl-4">
                    <p class="text-sm italic text-slate-200">&ldquo;{{ quote.message }}&rdquo;</p>
                    <footer v-if="quote.author" class="text-xs font-medium text-slate-400">{{ quote.author }}</footer>
                </blockquote>
            </div>
        </div>

        <div class="py-8 lg:p-8">
            <div class="mx-auto flex w-full max-w-md flex-col justify-center space-y-6">
                <div class="flex flex-col items-center gap-3 text-center lg:hidden">
                    <Link href="/" class="flex items-center gap-2">
                        <AppLogo />
                    </Link>
                    <div
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-400"
                    >
                        <ShieldCheck class="size-3.5 shrink-0" />
                        <span>Dinas Perikanan dan Peternakan Kabupaten Banyumas</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-[#1E1E1E] dark:bg-[#111111]">
                    <div v-if="title || description" class="mb-6 flex flex-col space-y-1.5 text-center">
                        <h1 v-if="title" class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-slate-500 dark:text-slate-400">{{ description }}</p>
                    </div>
                    <slot />
                </div>

                <div class="flex items-center justify-center">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 transition-colors hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400"
                    >
                        <ArrowLeft class="size-3.5" />
                        <span>Kembali ke Portal Publik WebGIS</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
