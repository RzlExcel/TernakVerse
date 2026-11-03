<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DesignRuleInspector from '../components/ternakverse/DesignRuleInspector.vue';
import DiagnosticBox from '../components/ternakverse/DiagnosticBox.vue';
import ThemeToggle from '../components/ternakverse/ThemeToggle.vue';
import { Compass, Shield, Map } from 'lucide-vue-next';
</script>

<template>
    <Head title="Living Design System - Aturan Desain Ternakverse" />

    <div class="min-h-screen dark:bg-[#090909] bg-[#FFFFFF] dark:text-[#F5F5F5] text-[#171B17] flex flex-col font-ui antialiased selection:bg-emerald-500 selection:text-slate-950">
        <!-- Header -->
        <header class="h-16 w-full dark:bg-[#111111]/95 bg-white/95 backdrop-blur-md border-b dark:border-[#1E1E1E] border-[#E0E4DD] px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 select-none">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-sm">
                    <Compass class="w-5 h-5 stroke-[2.2]" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-extrabold text-xl tracking-tight dark:text-[#F5F5F5] text-[#171B17]">TERNAKVERSE</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 uppercase tracking-wider">
                            DESIGN SYSTEM
                        </span>
                    </div>
                    <p class="text-xs dark:text-[#B3B3B3] text-[#444C43] hidden sm:block leading-none mt-1">Spesifikasi &amp; Kepatuhan Visual Forest Intelligence GIS</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <ThemeToggle size="sm" compact />
                <button
                    type="button"
                    @click="router.visit('/')"
                    class="h-10 px-3.5 rounded-xl dark:bg-[#181818] hover:dark:bg-[#1E1E1E] bg-white hover:bg-[#F4F5F2] border dark:border-[#1E1E1E] border-[#E0E4DD] text-xs font-semibold flex items-center gap-1.5 dark:text-[#00D084] text-[#00A86B] transition-colors cursor-pointer"
                >
                    <Map class="w-3.5 h-3.5" />
                    <span>Portal Publik</span>
                </button>
                <button
                    type="button"
                    @click="router.visit('/internal/login')"
                    class="h-10 px-3.5 rounded-xl bg-[#00D084] hover:bg-[#00B975] text-[#090909] font-bold text-xs flex items-center gap-1.5 transition-colors shadow-xs cursor-pointer"
                >
                    <Shield class="w-3.5 h-3.5" />
                    <span>Portal Internal</span>
                </button>
            </div>
        </header>

        <main class="flex-1 w-full">
            <DesignRuleInspector />
        </main>

        <DiagnosticBox />
    </div>
</template>
