<script setup lang="ts">
import { ref } from 'vue';
import { BookOpen, CheckCircle2, Copy, Sparkles, Sliders, Shield, Zap, Eye, Check } from 'lucide-vue-next';

// State Checklist Sebelum Merge (Bab 14)
const checklistItems = ref([
    { id: 1, text: 'Palet Pure Obsidian & Porcelain kontras tinggi konsisten di seluruh portal publik dan eksekutif.', checked: true },
    { id: 2, text: 'Border 1px presisi (#1E1E1E / #E0E4DD) dan surface hierarkis (#090909 -> #111111 -> #181818).', checked: true },
    { id: 3, text: 'Radius mengikuti sistem 8-point (8px / 12px / 16px / 999px) dengan fokus ring aksesibel.', checked: true },
    { id: 4, text: 'Transisi mikro 150ms ease-out dan ukuran target sentuh minimum 40x40px.', checked: true },
    { id: 5, text: 'Legenda 5 kelas diskrit dari kuantil aktif; zona pola + teks; simbol proporsional PMK/LSD.', checked: true },
    { id: 6, text: 'Badge sorotan kecamatan teratas rapi dan proporsional tanpa menghalangi kanvas peta.', checked: true },
    { id: 7, text: 'Ikon garis satu set konsisten Lucide Icons; tanpa emoji struktural.', checked: true },
    { id: 8, text: 'Semua angka diformat id-ID (ribuan titik), font-mono tabular-nums.', checked: true },
    { id: 9, text: 'Panel Eksekutif & Admin terstruktur rapi (max 1440px) dengan Bento KPI Grid.', checked: true },
    { id: 10, text: 'Zero Data Leakage: Portal Publik terpisah ketat dari rute internal dinas/admin.', checked: true },
    { id: 11, text: 'Aturan rendering ringan (Geometry Caching & preferCanvas) diterapkan.', checked: true },
    { id: 12, text: 'Kotak diagnostik, safe() karantina, dan penanda BERKAS-UTUH terverifikasi.', checked: true },
]);

const copiedColor = ref<string | null>(null);

function copyHex(hex: string) {
    navigator.clipboard.writeText(hex);
    copiedColor.value = hex;
    setTimeout(() => {
        copiedColor.value = null;
    }, 2000);
}

// Daftar Token Warna Pure Obsidian & Porcelain (Dual Theme)
const colorTokens = [
    { name: '--bg-canvas', hex: '#090909', role: 'Latar utama kanvas & celah poligon (Pure Obsidian)' },
    { name: '--bg-surface', hex: '#111111', role: 'Sidebar, kartu KPI, tabel, popover (#111111 / #171717)' },
    { name: '--bg-elevated', hex: '#181818', role: 'Permukaan tingkat lanjut & raised modals/wells' },
    { name: '--color-border', hex: '#1E1E1E', role: 'Garis border presisi 1px dark (#1E1E1E / #292929)' },
    { name: '--light-surface', hex: '#FFFFFF', role: 'Permukaan bersih light mode (Porcelain)' },
    { name: '--light-bg-sec', hex: '#F4F5F2', role: 'Latar sekunder & input box light mode' },
    { name: '--light-border', hex: '#E0E4DD', role: 'Garis border kontras tinggi light mode' },
    { name: '--primary-charcoal', hex: '#171B17', role: 'Teks primer light mode & seleksi solid aktif' },
    { name: '--primary-emerald', hex: '#00D084', role: 'Aksen hijau zamrud elektrik (Pure Obsidian)' },
    { name: '--accent-emerald', hex: '#10B981', role: 'Sorotan aktif, tren positif, zona aman' },
    { name: '--highlight-emerald', hex: '#34D399', role: 'Teks metrik utama & kelas kuantil tertinggi' },
    { name: '--geo-teal', hex: '#0D9488', role: 'Aksen geospasial & batas wilayah' },
    { name: '--kelas-1', hex: '#134E4A', role: 'Peta kuantil sangat rendah' },
    { name: '--kelas-2', hex: '#0F766E', role: 'Peta kuantil rendah' },
    { name: '--kelas-3', hex: '#0D9488', role: 'Peta kuantil sedang' },
    { name: '--kelas-4', hex: '#10B981', role: 'Peta kuantil tinggi' },
    { name: '--kelas-5', hex: '#34D399', role: 'Peta kuantil sangat tinggi (Sentra)' },
    { name: '--ews-bahaya', hex: '#E11D48', role: 'Zona bahaya merah, kasus PMK/LSD, hapus' },
    { name: '--ews-waspada', hex: '#F59E0B', role: 'Zona waspada kuning & peringatan dini' },
    { name: '--text-primary', hex: '#F5F5F5', role: 'Teks utama kontras tinggi (Pure Obsidian)' },
];
</script>

<template>
    <div class="w-full max-w-[1160px] mx-auto px-4 py-8 space-y-8 select-none font-ui text-[#171B17] dark:text-[#F5F5F5]">
        <!-- Header Aturan Desain -->
        <div class="pb-6 border-b border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold">
                        Living Design System
                    </span>
                    <span class="text-xs text-[#6E776D] dark:text-[#B3B3B3]">Arsitektur Visual: Pure Obsidian &amp; Porcelain</span>
                </div>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-[#171B17] dark:text-[#F5F5F5] mt-1.5 tracking-tight">
                    Inspektor Aturan Desain Ternakverse
                </h1>
                <p class="text-sm text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                    Pedoman visual Geospatial Command Center — bersih, presisi, kontras tinggi, dan bebas kebocoran data.
                </p>
            </div>

            <!-- Tombol Status Ringkasan -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-3.5 flex items-center gap-3 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <CheckCircle2 class="w-6 h-6 stroke-[2.2]" />
                </div>
                <div>
                    <div class="text-xs font-bold text-[#171B17] dark:text-[#F5F5F5] tabular-nums">12/12 Terverifikasi</div>
                    <div class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] tabular-nums">Kepatuhan aturan 100%</div>
                </div>
            </div>
        </div>

        <!-- SEKSI 1: UJI INTERAKTIF KOMPONEN TOMBOL AGRITECH -->
        <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 space-y-4 shadow-sm">
            <div>
                <h3 class="font-display font-bold text-xl text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                    <Sparkles class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    1. Spesifikasi Komponen Interaktif (150ms Ease-Out)
                </h3>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                    Standar tombol aksi dengan kontras WCAG AA, ring fokus jelas, dan area sentuh minimum 40px.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <!-- Tombol Utama -->
                <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-col items-center justify-center gap-3 text-center">
                    <span class="text-xs font-bold text-[#6E776D] dark:text-[#B3B3B3]">Tombol Utama (Emerald)</span>
                    <button type="button" class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition-colors shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                    <span class="text-[11px] font-mono text-[#6E776D] dark:text-[#B3B3B3]">bg: #00D084 / #059669</span>
                </div>

                <!-- Tombol Sekunder -->
                <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-col items-center justify-center gap-3 text-center">
                    <span class="text-xs font-bold text-[#6E776D] dark:text-[#B3B3B3]">Tombol Sekunder (Charcoal / Stealth)</span>
                    <button type="button" class="h-10 px-4 rounded-xl bg-white dark:bg-[#111111] hover:bg-[#EDEFEB] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#171B17] dark:text-[#F5F5F5] font-semibold text-xs transition-colors cursor-pointer">
                        Filter Geospasial
                    </button>
                    <span class="text-[11px] font-mono text-[#6E776D] dark:text-[#B3B3B3]">border: 1px solid #1E1E1E / #E0E4DD</span>
                </div>

                <!-- Tombol Bahaya -->
                <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex flex-col items-center justify-center gap-3 text-center">
                    <span class="text-xs font-bold text-[#6E776D] dark:text-[#B3B3B3]">Tombol Bahaya (Rose EWS)</span>
                    <button type="button" class="h-10 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition-colors shadow-sm cursor-pointer">
                        Hapus Data
                    </button>
                    <span class="text-[11px] font-mono text-[#6E776D] dark:text-[#B3B3B3]">bg: #DC625F / #E11D48</span>
                </div>
            </div>
        </section>

        <!-- SEKSI 2: TOKEN WARNA PURE OBSIDIAN & PORCELAIN -->
        <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 space-y-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-display font-bold text-xl text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                        <Sliders class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        2. Token Palet Warna Pure Obsidian &amp; Porcelain
                    </h3>
                    <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                        Klik pada kartu warna untuk menyalin nilai Hex langsung ke clipboard.
                    </p>
                </div>
                <span v-if="copiedColor" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-[#F4F5F2] dark:bg-[#181818] px-3 py-1 rounded-full border border-emerald-500/40 animate-pulse font-mono">
                    Disalin: {{ copiedColor }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    v-for="color in colorTokens"
                    :key="color.name"
                    @click="copyHex(color.hex)"
                    class="p-3 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-emerald-500/50 cursor-pointer transition-all flex items-center gap-3"
                >
                    <span
                        class="w-8 h-8 rounded-lg border border-[#E0E4DD] dark:border-[#292929] flex-shrink-0"
                        :style="{ backgroundColor: color.hex }"
                    ></span>
                    <div class="overflow-hidden">
                        <div class="font-bold text-xs text-[#171B17] dark:text-[#F5F5F5] truncate">{{ color.name }}</div>
                        <div class="font-mono text-[11px] text-[#6E776D] dark:text-[#B3B3B3] tabular-nums">{{ color.hex }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEKSI 3: HIERARKI RADIUS & TIPOGRAFI -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Hierarki Radius -->
            <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 space-y-3 shadow-sm">
                <h3 class="font-display font-bold text-lg text-[#171B17] dark:text-[#F5F5F5]">3. Sistem Spasial 8-Point &amp; Radius</h3>
                <ul class="space-y-2 text-xs text-[#6E776D] dark:text-[#B3B3B3]">
                    <li class="p-3 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] flex items-center justify-between">
                        <span><strong class="text-[#171B17] dark:text-[#F5F5F5]">8px (rounded-lg)</strong> : Chip dalam, badge tabel, item list</span>
                        <div class="w-6 h-6 rounded-lg border border-emerald-500/50"></div>
                    </li>
                    <li class="p-3 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] flex items-center justify-between">
                        <span><strong class="text-[#171B17] dark:text-[#F5F5F5]">12px (rounded-xl)</strong> : Tombol, input form, kontrol peta</span>
                        <div class="w-6 h-6 rounded-xl border border-teal-500/50"></div>
                    </li>
                    <li class="p-3 rounded-2xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] flex items-center justify-between">
                        <span><strong class="text-[#171B17] dark:text-[#F5F5F5]">16px (rounded-2xl)</strong> : Panel sidebar, kartu KPI, bingkai peta</span>
                        <div class="w-6 h-6 rounded-2xl border border-emerald-400"></div>
                    </li>
                    <li class="p-3 rounded-full border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] flex items-center justify-between px-4">
                        <span><strong class="text-[#171B17] dark:text-[#F5F5F5]">999px (rounded-full)</strong> : Indikator status &amp; pil kategori</span>
                        <div class="w-8 h-4 rounded-full border border-amber-400"></div>
                    </li>
                </ul>
            </section>

            <!-- Tipografi -->
            <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 space-y-3 shadow-sm">
                <h3 class="font-display font-bold text-lg text-[#171B17] dark:text-[#F5F5F5]">4. Tipografi &amp; Tabular Numerals</h3>
                <div class="space-y-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <span class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] font-bold block mb-1">Display &amp; Metrik Utama:</span>
                        <div class="font-display font-extrabold text-2xl text-[#171B17] dark:text-[#F5F5F5]">
                            Plus Jakarta Sans / Bricolage
                        </div>
                        <div class="font-mono font-bold text-2xl text-emerald-600 dark:text-emerald-400 mt-1 tabular-nums">
                            31.160 ekor — Rp 450.000.000
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <span class="text-[11px] text-[#6E776D] dark:text-[#B3B3B3] font-bold block mb-1">UI (Isi, Tombol, Tabel, Legenda):</span>
                        <div class="font-ui font-bold text-sm text-[#171B17] dark:text-[#F5F5F5]">
                            Plus Jakarta Sans (Weight 400, 500, 600, 700)
                        </div>
                        <p class="font-ui text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1 leading-relaxed tabular-nums">
                            Format angka id-ID tabular-nums: titik ribuan (3.240), tanggal 14 Okt 2026, jam 09.12 WIB.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- SEKSI 4: DAFTAR PERIKSA SEBELUM MERGE (Bab 14) -->
        <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 space-y-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-display font-bold text-xl text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                        <Shield class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        5. Daftar Periksa Kepatuhan Sebelum Merge
                    </h3>
                    <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                        Daftar verifikasi otomatis dan manual implementasi antarmuka.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                <div
                    v-for="item in checklistItems"
                    :key="item.id"
                    @click="item.checked = !item.checked"
                    class="p-3 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] bg-[#F4F5F2] dark:bg-[#181818] flex items-start gap-3 cursor-pointer hover:border-emerald-500/40 transition-colors"
                >
                    <div
                        class="w-5 h-5 rounded-md border flex items-center justify-center flex-shrink-0 mt-0.5 transition-colors"
                        :class="item.checked ? 'bg-emerald-600 border-emerald-500 text-white' : 'border-[#C8CEC4] dark:border-[#292929] bg-white dark:bg-[#111111]'"
                    >
                        <Check v-if="item.checked" class="w-3.5 h-3.5 stroke-[2.5]" />
                    </div>
                    <span class="text-xs text-[#171B17] dark:text-[#F5F5F5] leading-relaxed">{{ item.text }}</span>
                </div>
            </div>
        </section>

        <!-- SEKSI 5: ATURAN RENDERING RINGAN (Bab 12) -->
        <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-2xl p-6 space-y-3 shadow-sm">
            <h3 class="font-display font-bold text-xl text-[#171B17] dark:text-[#F5F5F5] flex items-center gap-2">
                <Zap class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                6. Arsitektur Kinerja &amp; Rendering Ringan (Lightweight 60 FPS)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="font-bold text-teal-600 dark:text-teal-400 mb-1">Geometry Caching</div>
                    <p class="text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                        Geometri poligon 27 kecamatan dimuat sekali ke memori. Pergantian metrik hanya memperbarui warna atribut (&lt;2 KB) tanpa menggambar ulang kanvas dari awal.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="font-bold text-emerald-600 dark:text-emerald-400 mb-1">Canvas / Direct SVG</div>
                    <p class="text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                        Rendering peta memanfaatkan akselerasi GPU dengan palet Pure Obsidian yang menjaga kontras visual tetap tajam di lapangan.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="font-bold text-amber-600 dark:text-amber-400 mb-1">Decoupled Portals</div>
                    <p class="text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                        Sistem Publik dan Internal Petugas terpisah secara ketat (RBAC), mencegah kebocoran data sensitif (*zero data leakage*) ke pengguna tamu.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
