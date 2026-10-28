<script setup lang="ts">
import { ref } from 'vue';
import { Compass, BookOpen, Layers, ShieldCheck, UserCheck, Eye, Search, AlertCircle } from 'lucide-vue-next';

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
    <header class="h-16 w-full border-b border-[#1E293B] bg-[#0B0F17] px-4 md:px-6 flex items-center justify-between sticky top-0 z-40 select-none">
        <!-- Brand Kiri (BBPTUHPT Style) -->
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
                T
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-display font-black text-base tracking-tight text-white">TERNAKVERSE</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-600/15 text-blue-400 border border-blue-500/30">BANYUMAS</span>
                </div>
                <p class="text-[11px] text-slate-400 hidden sm:block leading-none mt-0.5">Kabupaten Banyumas • Ditjen PKH RI</p>
            </div>
        </div>

        <!-- Tengah: Badge Status Peran Tunggal + Interactive Role Switcher Demo Preview -->
        <div class="hidden md:flex items-center gap-2.5">
            <!-- Badge Status Peran Tunggal Aktif -->
            <div v-if="currentRole === 'publik'" class="px-3 py-1 rounded-lg border border-[#1E293B] bg-[#111827] text-slate-300 text-xs font-medium flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <span>Portal Publik (Tamu)</span>
            </div>
            <div v-else-if="currentRole === 'dinas'" class="px-3 py-1 rounded-lg border border-blue-500/40 bg-blue-950/30 text-blue-300 text-xs font-semibold flex items-center gap-2 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                <span>Petugas Dinas Aktif</span>
            </div>
            <div v-else-if="currentRole === 'admin'" class="px-3 py-1 rounded-lg border border-blue-500/40 bg-blue-950/30 text-blue-300 text-xs font-semibold flex items-center gap-2 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                <span>Super Admin Balai</span>
            </div>

            <!-- Demo Preview Switcher Pill -->
            <div class="flex items-center bg-[#111827] p-1 rounded-lg border border-[#1E293B] ml-2 text-xs">
                <button
                    @click="$emit('update:currentRole', 'publik'); $emit('update:activeTab', 'map')"
                    class="px-3 py-1 rounded-md transition-all font-medium cursor-pointer"
                    :class="currentRole === 'publik' ? 'bg-blue-600 text-white shadow-sm font-semibold' : 'text-slate-400 hover:text-slate-200'"
                    title="Simulasi Tampilan Portal Publik"
                >
                    Publik
                </button>
                <button
                    @click="$emit('update:currentRole', 'dinas'); $emit('update:activeTab', 'map')"
                    class="px-3 py-1 rounded-md transition-all font-medium cursor-pointer"
                    :class="currentRole === 'dinas' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-blue-400'"
                    title="Simulasi Tampilan Petugas Dinas Kuantitatif"
                >
                    Dinas
                </button>
                <button
                    @click="$emit('update:currentRole', 'admin'); $emit('update:activeTab', 'admin')"
                    class="px-3 py-1 rounded-md transition-all font-medium cursor-pointer"
                    :class="currentRole === 'admin' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-blue-400'"
                    title="Simulasi Tampilan Admin Panel CRUD"
                >
                    Admin
                </button>
            </div>
        </div>

        <!-- Kanan: Tombol Navigasi Cepat & Design Rule Guide -->
        <div class="flex items-center gap-2.5">
            <!-- Tab Switcher (Peta vs Admin Panel vs Aturan Desain) -->
            <div class="flex items-center bg-[#111827] rounded-lg p-1 border border-[#1E293B]">
                <button
                    @click="$emit('update:activeTab', 'map')"
                    class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    :class="activeTab === 'map' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                >
                    <Layers class="w-3.5 h-3.5" />
                    <span>WebGIS Peta</span>
                </button>
                <button
                    @click="$emit('update:activeTab', 'admin')"
                    class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    :class="activeTab === 'admin' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                >
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>Admin Data</span>
                </button>
                <button
                    @click="$emit('update:activeTab', 'design-rule')"
                    class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                    :class="activeTab === 'design-rule' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    title="Buka Dokumen &amp; Token Aturan Desain Interaktif"
                >
                    <BookOpen class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Aturan Desain</span>
                </button>
            </div>

            <!-- Tombol Bantuan -->
            <button
                @click="$emit('openHelp')"
                class="h-8 px-3 rounded-lg border border-[#1E293B] bg-[#111827] text-slate-300 hover:text-white text-xs font-semibold transition-all cursor-pointer"
                title="Panduan Pemakaian"
            >
                Bantuan
            </button>
        </div>
    </header>
</template>
