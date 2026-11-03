<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-slate-50 p-6 md:p-10 dark:bg-[#090909] relative transition-colors">
        <!-- Floating ThemeToggle Top Right -->
        <div class="absolute top-4 right-4 z-20">
            <ThemeToggle size="sm" compact />
        </div>

        <div class="flex w-full max-w-md flex-col gap-6">
            <div class="flex flex-col items-center gap-3 text-center">
                <Link href="/" class="flex items-center gap-2 transition-opacity hover:opacity-90">
                    <AppLogo />
                </Link>
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-[#00D084]"
                >
                    <ShieldCheck class="size-3.5 shrink-0" />
                    <span>Dinas Perikanan dan Peternakan Kabupaten Banyumas</span>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-[#1E1E1E] dark:bg-[#111111]">
                <div v-if="title || description" class="mb-6 space-y-1.5 text-center">
                    <h1 v-if="title" class="text-xl font-bold tracking-tight text-slate-900 dark:text-[#F5F5F5]">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-slate-500 dark:text-[#B3B3B3]">{{ description }}</p>
                </div>

                <slot />
            </div>

            <div class="flex items-center justify-center">
                <Link
                    href="/"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 transition-colors hover:text-emerald-600 dark:text-[#B3B3B3] dark:hover:text-[#00D084]"
                >
                    <ArrowLeft class="size-3.5" />
                    <span>Kembali ke Portal Publik WebGIS</span>
                </Link>
            </div>
        </div>
    </div>
</template>
