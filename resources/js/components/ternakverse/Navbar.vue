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
    <header class="h-16 w-full bg-[#10171A] border-b-2 border-[#243034] px-4 md:px-6 flex items-center justify-between sticky top-0 z-40 select-none">
        <!-- Brand Kiri -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-12 bg-[#0A0D0E] border-2 border-[#19F5D4] flex items-center justify-center text-[#19F5D4] shadow-[3px_3px_0_#19F5D4]">
                <Compass class="w-6 h-6 stroke-[2.5]" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-display font-extrabold text-xl tracking-tight text-[#F4F7F5]">TERNAKVERSE</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#243034] text-[#19F5D4] border border-[#3A4A4F]">BANYUMAS</span>
                </div>
                <p class="text-[12px] text-[#8FA1A0] hidden sm:block leading-none mt-0.5">WebGIS Peternakan Kab. Banyumas</p>
            </div>
        </div>

        <!-- Tengah: Badge Status Peran Tunggal (Sesuai Bab 6 & 11.1) + Interactive Role Switcher Demo Preview -->
        <div class="hidden md:flex items-center gap-2">
            <!-- Badge Status Peran Tunggal Aktif -->
            <div v-if="currentRole === 'publik'" class="px-3.5 py-1 rounded-full border border-[#243034] bg-[#0A0D0E] text-[#8FA1A0] text-xs font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#8FA1A0]"></span>
                <span>Portal Publik (Tamu)</span>
            </div>
            <div v-else-if="currentRole === 'dinas'" class="px-3.5 py-1 rounded-full border-2 border-[#19F5D4] bg-[#10171A] text-[#19F5D4] text-xs font-bold flex items-center gap-1.5 shadow-[2px_2px_0_#19F5D4]">
                <span class="w-2 h-2 rounded-full bg-[#19F5D4] animate-pulse"></span>
                <span>📋 Petugas Dinas Aktif</span>
            </div>
            <div v-else-if="currentRole === 'admin'" class="px-3.5 py-1 rounded-full border-2 border-[#F4F7F5] bg-[#39FF14] text-[#04110A] text-xs font-extrabold flex items-center gap-1.5 shadow-[2px_2px_0_#19F5D4]">
                <span class="w-2 h-2 rounded-full bg-[#04110A]"></span>
                <span>🛡️ Panel Super Admin</span>
            </div>

            <!-- Demo Preview Switcher Pill (Catatan Bab 15: Simulator Switcher untuk presentasi) -->
            <div class="flex items-center bg-[#0A0D0E] p-1 rounded-full border border-[#243034] ml-2 text-xs">
                <button
                    @click="$emit('update:currentRole', 'publik'); $emit('update:activeTab', 'map')"
                    class="px-2.5 py-0.5 rounded-full transition-all font-semibold"
                    :class="currentRole === 'publik' ? 'bg-[#243034] text-[#F4F7F5]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                    title="Simulasi Tampilan Portal Publik"
                >
                    Publik
                </button>
                <button
                    @click="$emit('update:currentRole', 'dinas'); $emit('update:activeTab', 'map')"
                    class="px-2.5 py-0.5 rounded-full transition-all font-semibold"
                    :class="currentRole === 'dinas' ? 'bg-[#19F5D4] text-[#04110A]' : 'text-[#8FA1A0] hover:text-[#19F5D4]'"
                    title="Simulasi Tampilan Petugas Dinas Kuantitatif"
                >
                    Dinas
                </button>
                <button
                    @click="$emit('update:currentRole', 'admin'); $emit('update:activeTab', 'admin')"
                    class="px-2.5 py-0.5 rounded-full transition-all font-semibold"
                    :class="currentRole === 'admin' ? 'bg-[#39FF14] text-[#04110A]' : 'text-[#8FA1A0] hover:text-[#39FF14]'"
                    title="Simulasi Tampilan Admin Panel CRUD"
                >
                    Admin
                </button>
            </div>
        </div>

        <!-- Kanan: Tombol Navigasi Cepat & Design Rule Guide -->
        <div class="flex items-center gap-2.5">
            <!-- Tab Switcher (Peta vs Admin Panel vs Aturan Desain) -->
            <div class="flex items-center bg-[#0A0D0E] rounded-12 p-1 border border-[#243034]">
                <button
                    @click="$emit('update:activeTab', 'map')"
                    class="px-3 py-1.5 rounded-10 text-xs font-bold flex items-center gap-1.5 transition-all"
                    :class="activeTab === 'map' ? 'bg-[#10171A] text-[#19F5D4] border border-[#19F5D4]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                >
                    <Layers class="w-3.5 h-3.5" />
                    <span>WebGIS Peta</span>
                </button>
                <button
                    @click="$emit('update:activeTab', 'admin')"
                    class="px-3 py-1.5 rounded-10 text-xs font-bold flex items-center gap-1.5 transition-all"
                    :class="activeTab === 'admin' ? 'bg-[#10171A] text-[#39FF14] border border-[#39FF14]' : 'text-[#8FA1A0] hover:text-[#F4F7F5]'"
                >
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>Admin Data</span>
                </button>
                <button
                    @click="$emit('update:activeTab', 'design-rule')"
                    class="px-3 py-1.5 rounded-10 text-xs font-bold flex items-center gap-1.5 transition-all"
                    :class="activeTab === 'design-rule' ? 'bg-[#10171A] text-[#FFC53D] border border-[#FFC53D]' : 'text-[#8FA1A0] hover:text-[#FFC53D]'"
                    title="Buka Dokumen & Token Aturan Desain Interaktif"
                >
                    <BookOpen class="w-3.5 h-3.5" />
                    <span>Aturan Desain</span>
                </button>
            </div>

            <!-- Tombol Bantuan -->
            <button
                @click="$emit('openHelp')"
                class="btn-secondary-tactile h-9 px-3 text-xs"
                title="Panduan Pemakaian"
            >
                Bantuan
            </button>
        </div>
    </header>
</template>

<style scoped>
.rounded-10 {
    border-radius: 10px;
}
</style>
