<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DesignRuleInspector from '../components/ternakverse/DesignRuleInspector.vue';
import DiagnosticBox from '../components/ternakverse/DiagnosticBox.vue';
import { Compass, ArrowLeft, Shield, Map } from 'lucide-vue-next';
</script>

<template>
    <Head title="Living Design System - Aturan Desain Ternakverse" />

    <div class="min-h-screen bg-[#0A0D0E] text-[#F4F7F5] flex flex-col font-ui antialiased selection:bg-[#39FF14] selection:text-[#04110A]">
        <!-- Header -->
        <header class="h-16 w-full bg-[#10171A] border-b-2 border-[#243034] px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 select-none">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-12 bg-[#0A0D0E] border-2 border-[#FFC53D] flex items-center justify-center text-[#FFC53D] shadow-[3px_3px_0_#FFC53D]">
                    <Compass class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-extrabold text-xl tracking-tight text-[#F4F7F5]">TERNAKVERSE</span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#FFC53D]/20 text-[#FFC53D] border border-[#FFC53D]">
                            DESIGN SYSTEM
                        </span>
                    </div>
                    <p class="text-[12px] text-[#8FA1A0] hidden sm:block leading-none mt-0.5">Spesifikasi & Kepatuhan Visual Sesuai DESIGN_RULE.md</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="router.visit('/')"
                    class="btn-secondary-tactile h-9 px-3 text-xs gap-1.5 text-[#19F5D4]"
                >
                    <Map class="w-3.5 h-3.5" />
                    <span>Portal Publik</span>
                </button>
                <button
                    @click="router.visit('/internal/login')"
                    class="btn-secondary-tactile h-9 px-3 text-xs gap-1.5 text-[#39FF14]"
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
