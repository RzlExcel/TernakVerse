<script setup lang="ts">
import { ref } from 'vue';
import { Compass, BookOpen, Layers, ShieldCheck, UserCheck, Eye, Search, AlertCircle } from 'lucide-vue-next';
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';

const props = defineProps<{
    currentRole: 'publik' | 'dinas' | 'admin';
    activeTab: 'map' | 'admin' | 'design-rule';
}>();

const emit = defineEmits<{
    (e: 'update:currentRole', role: 'publik' | 'dinas' | 'admin'): void;
    (e: 'update:activeTab', tab: 'map' | 'admin' | 'design-rule'): void;
    (e: 'openHelp'): void;
}>();

const searchQuery = ref('');
</script>

<template>
    <header class="h-16 w-full border-b dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#090909]/95 bg-white/95 backdrop-blur-md px-4 md:px-6 flex items-center justify-between sticky top-0 z-40 select-none transition-colors">
        <!-- Brand Kiri (AgriTech Command Center Style) -->
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#00D084] flex items-center justify-center text-[#062117] font-black text-lg shadow-sm">
                T
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-display font-black text-base tracking-tight dark:text-[#F5F5F5] text-[#171B17]">TERNAKVERSE</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md dark:bg-[#00D084]/15 dark:text-[#00D084] dark:border-[#00D084]/30 bg-[#E8F8F1] text-[#00A86B] border-[#00A86B]/30">BANYUMAS</span>
                </div>
                <p class="text-[11px] dark:text-[#B3B3B3] text-[#6E776D] hidden sm:block leading-none mt-0.5">Kabupaten Banyumas • Ditjen PKH RI</p>
            </div>
        </div>

        <!-- Tengah: Badge Status Peran Tunggal + Interactive Role Switcher Demo Preview -->
        <div class="hidden md:flex items-center gap-2.5">
            <!-- Badge Status Peran Tunggal Aktif -->
            <div v-if="currentRole === 'publik'" class="px-3 py-1 rounded-lg border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#F4F5F2] dark:text-[#B3B3B3] text-[#444C43] text-xs font-medium flex items-center gap-2">
                <span class="w-2 h-2 rounded-full dark:bg-[#858585] bg-[#858585]"></span>
                <span>Portal Publik (Tamu)</span>
            </div>
            <div v-else-if="currentRole === 'dinas'" class="px-3 py-1 rounded-lg border dark:border-[#00D084]/40 border-[#00A86B]/40 dark:bg-[#00D084]/15 bg-[#E8F8F1] dark:text-[#00D084] text-[#00A86B] text-xs font-semibold flex items-center gap-2 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-[#00D084] animate-pulse"></span>
                <span>Petugas Dinas Aktif</span>
            </div>
            <div v-else-if="currentRole === 'admin'" class="px-3 py-1 rounded-lg border dark:border-[#00D084]/40 border-[#00A86B]/40 dark:bg-[#00D084]/15 bg-[#E8F8F1] dark:text-[#00D084] text-[#00A86B] text-xs font-semibold flex items-center gap-2 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-[#00D084]"></span>
                <span>Super Admin Balai</span>
            </div>

            <!-- Demo Preview Switcher Pill -->
            <div class="flex items-center dark:bg-[#111111] bg-[#F4F5F2] p-1 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] ml-2 text-xs">
                <button
                    type="button"
                    @click="$emit('update:currentRole', 'publik'); $emit('update:activeTab', 'map')"
                    class="px-3 py-1 rounded-lg transition-all font-medium cursor-pointer"
                    :class="currentRole === 'publik'
                        ? 'bg-[#171B17] text-white dark:bg-[#181818] dark:text-[#F5F5F5] shadow-sm font-bold'
                        : 'text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]'"
                    title="Simulasi Tampilan Portal Publik"
                >
                    Publik
                </button>
                <button
                    type="button"
                    @click="$emit('update:currentRole', 'dinas'); $emit('update:activeTab', 'map')"
                    class="px-3 py-1 rounded-lg transition-all font-medium cursor-pointer"
                    :class="currentRole === 'dinas'
                        ? 'bg-[#171B17] text-white dark:bg-[#181818] dark:text-[#00D084] shadow-sm font-bold'
                        : 'text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#00D084]'"
                    title="Simulasi Tampilan Petugas Dinas Kuantitatif"
                >
                    Dinas
                </button>
                <button
                    type="button"
                    @click="$emit('update:currentRole', 'admin'); $emit('update:activeTab', 'admin')"
                    class="px-3 py-1 rounded-lg transition-all font-medium cursor-pointer"
                    :class="currentRole === 'admin'
                        ? 'bg-[#171B17] text-white dark:bg-[#181818] dark:text-[#00D084] shadow-sm font-bold'
                        : 'text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#00D084]'"
                    title="Simulasi Tampilan Admin Panel CRUD"
                >
                    Admin
                </button>
            </div>
        </div>

        <!-- Kanan: Tombol Navigasi Cepat, Theme Toggle & Bantuan -->
        <div class="flex items-center gap-2.5">
            <!-- Tab Switcher (Peta vs Admin Panel vs Aturan Desain) -->
            <div class="flex items-center dark:bg-[#111111] bg-[#F4F5F2] rounded-xl p-1 border dark:border-[#1E1E1E] border-[#E0E4DD]">
                <button
                    type="button"
                    @click="$emit('update:activeTab', 'map')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    :class="activeTab === 'map'
                        ? 'bg-[#171B17] text-white dark:bg-[#181818] dark:text-[#F5F5F5] shadow-sm font-bold'
                        : 'text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]'"
                >
                    <Layers class="w-3.5 h-3.5" />
                    <span>WebGIS Peta</span>
                </button>
                <button
                    type="button"
                    @click="$emit('update:activeTab', 'admin')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    :class="activeTab === 'admin'
                        ? 'bg-[#171B17] text-white dark:bg-[#181818] dark:text-[#F5F5F5] shadow-sm font-bold'
                        : 'text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]'"
                >
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>Admin Data</span>
                </button>
                <button
                    type="button"
                    @click="$emit('update:activeTab', 'design-rule')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    :class="activeTab === 'design-rule'
                        ? 'bg-[#171B17] text-white dark:bg-[#181818] dark:text-[#F5F5F5] shadow-sm font-bold'
                        : 'text-[#444C43] hover:text-[#171B17] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]'"
                    title="Buka Dokumen &amp; Token Aturan Desain Interaktif"
                >
                    <BookOpen class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Aturan Desain</span>
                </button>
            </div>

            <!-- Theme Toggle Component -->
            <ThemeToggle size="sm" compact />

            <!-- Tombol Bantuan -->
            <button
                type="button"
                @click="$emit('openHelp')"
                class="h-9 px-3 rounded-xl border dark:border-[#1E1E1E] border-[#E0E4DD] dark:bg-[#111111] bg-[#F4F5F2] dark:text-[#B3B3B3] text-[#444C43] dark:hover:text-[#F5F5F5] hover:text-[#171B17] dark:hover:border-[#282828] hover:border-[#CCD2C8] text-xs font-semibold transition-all cursor-pointer"
                title="Panduan Pemakaian"
            >
                Bantuan
            </button>
        </div>
    </header>
</template>
