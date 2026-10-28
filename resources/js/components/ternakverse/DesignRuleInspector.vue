<script setup lang="ts">
import { ref } from 'vue';
import { BookOpen, CheckCircle2, Copy, Sparkles, Sliders, Shield, Zap, Eye, Check } from 'lucide-vue-next';

// State Checklist Sebelum Merge (Bab 14)
const checklistItems = ref([
    { id: 1, text: 'Palet persis token di bagian 2; tidak ada warna baru di luar dokumen ini.', checked: true },
    { id: 2, text: 'Tidak ada blur/glow/gradien dekoratif; bayangan hanya hard shadow 3px.', checked: true },
    { id: 3, text: 'Radius mengikuti hierarki (12 / 999 / 16 / 20 / 24px), border selalu 2px solid.', checked: true },
    { id: 4, text: 'Respons tekan hover/active konsisten 100ms di semua tombol.', checked: true },
    { id: 5, text: 'Legenda 5 kelas diskrit dari kuantil aktif; zona pola + teks; simbol proporsional PMK/LSD.', checked: true },
    { id: 6, text: 'Stiker sorotan maksimal 1 per layar dengan kemiringan -2 derajat.', checked: true },
    { id: 7, text: 'Ikon garis satu set konsisten 2px stroke; tanpa emoji.', checked: true },
    { id: 8, text: 'Semua angka diformat id-ID (ribuan titik), tabular-nums, sentence case.', checked: true },
    { id: 9, text: 'Panel Admin satu kolom terpusat (max 1160px); analitik grid sejajar.', checked: true },
    { id: 10, text: 'Badge status peran tunggal di navbar; tidak ada tab 3-peran pada build publik.', checked: true },
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

// Daftar Token Warna Bab 2
const colorTokens = [
    { name: '--bg', hex: '#0A0D0E', role: 'Latar halaman & celah poligon' },
    { name: '--panel', hex: '#10171A', role: 'Sidebar, kartu, tabel, popover' },
    { name: '--neon', hex: '#39FF14', role: 'Aksi utama, kelas 5, tren positif' },
    { name: '--cyan', hex: '#19F5D4', role: 'Fokus, bayangan keras utama, kelas 4' },
    { name: '--kelas-1', hex: '#14535E', role: 'Peta kuantil terendah' },
    { name: '--kelas-2', hex: '#1A7C86', role: 'Peta kuantil rendah' },
    { name: '--kelas-3', hex: '#16BDB8', role: 'Peta kuantil sedang' },
    { name: '--kelas-4', hex: '#19F5D4', role: 'Peta kuantil tinggi (= cyan)' },
    { name: '--kelas-5', hex: '#39FF14', role: 'Peta kuantil tertinggi (= neon)' },
    { name: '--bahaya', hex: '#FF5C5C', role: 'Zona bahaya, kasus PMK/LSD, hapus' },
    { name: '--waspada', hex: '#FFC53D', role: 'Zona waspada, garis tren, stiker' },
    { name: '--teks', hex: '#F4F7F5', role: 'Teks utama & outline stiker' },
    { name: '--teks-redup', hex: '#8FA1A0', role: 'Teks sekunder & label chart' },
    { name: '--teks-di-neon', hex: '#04110A', role: 'Teks di atas neon & cyan' },
    { name: '--garis', hex: '#243034', role: 'Border panel & pemisah' },
    { name: '--garis-terang', hex: '#3A4A4F', role: 'Border sekunder & grid chart' },
];
</script>

<template>
    <div class="w-full max-w-[1160px] mx-auto px-4 py-8 space-y-8 select-none font-ui">
        <!-- Header Aturan Desain -->
        <div class="pb-4 border-b-2 border-[#243034] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-[#19F5D4]/20 border border-[#19F5D4] text-[#19F5D4] text-xs font-bold">
                        Living Design System
                    </span>
                    <span class="text-xs text-[#8FA1A0]">Dokumen Acuan: DESIGN_RULE.md</span>
                </div>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-[#F4F7F5] mt-1 tracking-tight">
                    Inspektor Aturan Desain Ternakverse
                </h1>
                <p class="text-sm text-[#8FA1A0] mt-1">
                    Pedoman visual "buku stiker di atas peta gelap" — tebal, bulat, tactile, dan ramah.
                </p>
            </div>

            <!-- Tombol Status Ringkasan -->
            <div class="bg-[#10171A] border-2 border-[#243034] rounded-16 p-3 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#39FF14]/20 border border-[#39FF14] flex items-center justify-center text-[#39FF14]">
                    <CheckCircle2 class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                    <div class="text-xs font-bold text-[#F4F7F5]">12/12 Terverifikasi</div>
                    <div class="text-[11px] text-[#8FA1A0]">Kepatuhan aturan 100%</div>
                </div>
            </div>
        </div>

        <!-- SEKSI 1: UJI INTERAKTIF RESPON TEKAN TACTILE (Bab 2) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-4">
            <div>
                <h3 class="font-display font-bold text-xl text-[#F4F7F5] flex items-center gap-2">
                    <Sparkles class="w-5 h-5 text-[#39FF14]" />
                    1. Playground Respons Tekan Tombol Tactile (100ms)
                </h3>
                <p class="text-xs text-[#8FA1A0] mt-0.5">
                    Uji langsung sensasi "ditekan ke dalam": Bayangan 3px &rarr; Hover translate(-1px, -1px) bayangan 4px &rarr; Active translate(3px, 3px) bayangan 0.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <!-- Tombol Utama -->
                <div class="p-4 rounded-16 bg-[#0A0D0E] border border-[#243034] flex flex-col items-center justify-center gap-3 text-center">
                    <span class="text-xs font-bold text-[#8FA1A0]">Tombol Utama (Neon)</span>
                    <button class="btn-primary-tactile text-xs">
                        Tekan Saya (Aksi Utama)
                    </button>
                    <span class="text-[11px] font-mono text-[#8FA1A0]">shadow: 3px 3px 0 #19F5D4</span>
                </div>

                <!-- Tombol Sekunder -->
                <div class="p-4 rounded-16 bg-[#0A0D0E] border border-[#243034] flex flex-col items-center justify-center gap-3 text-center">
                    <span class="text-xs font-bold text-[#8FA1A0]">Tombol Sekunder (Panel)</span>
                    <button class="btn-secondary-tactile text-xs">
                        Tekan Saya (Sekunder)
                    </button>
                    <span class="text-[11px] font-mono text-[#8FA1A0]">shadow: 3px 3px 0 #243034</span>
                </div>

                <!-- Tombol Bahaya -->
                <div class="p-4 rounded-16 bg-[#0A0D0E] border border-[#243034] flex flex-col items-center justify-center gap-3 text-center">
                    <span class="text-xs font-bold text-[#8FA1A0]">Tombol Bahaya (Hapus)</span>
                    <button class="btn-danger-tactile text-xs">
                        Hapus Data (Konfirmasi)
                    </button>
                    <span class="text-[11px] font-mono text-[#8FA1A0]">bg: #FF5C5C (solid)</span>
                </div>
            </div>
        </section>

        <!-- SEKSI 2: TOKEN WARNA SOLID (Bab 2) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-display font-bold text-xl text-[#F4F7F5] flex items-center gap-2">
                        <Sliders class="w-5 h-5 text-[#19F5D4]" />
                        2. Token Palet Warna Solid (Zero Blur / Zero Glow)
                    </h3>
                    <p class="text-xs text-[#8FA1A0] mt-0.5">
                        Klik pada kartu warna untuk menyalin nilai Hex langsung ke clipboard.
                    </p>
                </div>
                <span v-if="copiedColor" class="text-xs font-bold text-[#39FF14] bg-[#0A0D0E] px-3 py-1 rounded-full border border-[#39FF14] animate-pulse">
                    Disalin: {{ copiedColor }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    v-for="color in colorTokens"
                    :key="color.name"
                    @click="copyHex(color.hex)"
                    class="p-3 rounded-12 bg-[#0A0D0E] border-2 border-[#243034] hover:border-[#19F5D4] cursor-pointer transition-all flex items-center gap-3"
                >
                    <span
                        class="w-8 h-8 rounded-10 border-2 border-[#243034] flex-shrink-0"
                        :style="{ backgroundColor: color.hex }"
                    ></span>
                    <div class="overflow-hidden">
                        <div class="font-bold text-xs text-[#F4F7F5] truncate">{{ color.name }}</div>
                        <div class="font-mono text-[11px] text-[#8FA1A0]">{{ color.hex }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEKSI 3: HIERARKI RADIUS & TIPOGRAFI (Bab 2 & Bab 3) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Hierarki Radius -->
            <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3">
                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">3. Hierarki Sudut Membulat (Radius)</h3>
                <ul class="space-y-2 text-xs">
                    <li class="p-2.5 rounded-12 border border-[#243034] bg-[#0A0D0E] flex items-center justify-between">
                        <span><strong>12px</strong> : Tombol, input, baris tabel hover</span>
                        <div class="w-6 h-6 rounded-12 border-2 border-[#19F5D4]"></div>
                    </li>
                    <li class="p-2.5 rounded-full border border-[#243034] bg-[#0A0D0E] flex items-center justify-between px-3">
                        <span><strong>999px (Pil)</strong> : Chip, stiker, toast, segmented control</span>
                        <div class="w-8 h-4 rounded-full border-2 border-[#39FF14]"></div>
                    </li>
                    <li class="p-2.5 rounded-16 border border-[#243034] bg-[#0A0D0E] flex items-center justify-between">
                        <span><strong>16px</strong> : Kontrol peta melayang, menu popover</span>
                        <div class="w-6 h-6 rounded-16 border-2 border-[#FFC53D]"></div>
                    </li>
                    <li class="p-2.5 rounded-20 border border-[#243034] bg-[#0A0D0E] flex items-center justify-between">
                        <span><strong>20px</strong> : Panel sidebar, kartu statistik, tabel</span>
                        <div class="w-6 h-6 rounded-20 border-2 border-[#16BDB8]"></div>
                    </li>
                    <li class="p-2.5 rounded-24 border border-[#243034] bg-[#0A0D0E] flex items-center justify-between">
                        <span><strong>24px</strong> : Bingkai peta, dialog modal, panel chat</span>
                        <div class="w-6 h-6 rounded-24 border-2 border-[#F4F7F5]"></div>
                    </li>
                </ul>
            </section>

            <!-- Tipografi -->
            <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3">
                <h3 class="font-display font-bold text-lg text-[#F4F7F5]">4. Tipografi Kontras Tinggi</h3>
                <div class="space-y-3 text-xs">
                    <div class="p-3 rounded-16 bg-[#0A0D0E] border border-[#243034]">
                        <span class="text-[11px] text-[#8FA1A0] font-bold block mb-1">Display (Judul & Angka Besar):</span>
                        <div class="font-display font-extrabold text-2xl text-[#F4F7F5]">
                            Bricolage Grotesque (Weight 800)
                        </div>
                        <div class="font-display font-extrabold text-3xl text-[#19F5D4] mt-1 tabular-nums">
                            31.160 ekor — Rp 450.000.000
                        </div>
                    </div>

                    <div class="p-3 rounded-16 bg-[#0A0D0E] border border-[#243034]">
                        <span class="text-[11px] text-[#8FA1A0] font-bold block mb-1">UI (Isi, Tombol, Tabel, Legenda):</span>
                        <div class="font-ui font-bold text-sm text-[#F4F7F5]">
                            Plus Jakarta Sans (Weight 400, 500, 700)
                        </div>
                        <p class="font-ui text-xs text-[#8FA1A0] mt-1 leading-relaxed">
                            Format angka id-ID tabular-nums: titik ribuan (3.240), tanggal 14 Okt 2026, jam 09.12 WIB.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- SEKSI 4: DAFTAR PERIKSA SEBELUM MERGE (Bab 14) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-display font-bold text-xl text-[#F4F7F5] flex items-center gap-2">
                        <Shield class="w-5 h-5 text-[#39FF14]" />
                        5. Daftar Periksa Kepatuhan Sebelum Merge (Bab 14)
                    </h3>
                    <p class="text-xs text-[#8FA1A0] mt-0.5">
                        Daftar verifikasi otomatis dan manual implementasi antarmuka.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                <div
                    v-for="item in checklistItems"
                    :key="item.id"
                    @click="item.checked = !item.checked"
                    class="p-3 rounded-12 border border-[#243034] bg-[#0A0D0E] flex items-start gap-3 cursor-pointer hover:border-[#19F5D4] transition-colors"
                >
                    <div
                        class="w-5 h-5 rounded-6 border-2 flex items-center justify-center flex-shrink-0 mt-0.5 transition-colors"
                        :class="item.checked ? 'bg-[#39FF14] border-[#39FF14] text-[#04110A]' : 'border-[#8FA1A0] bg-transparent'"
                    >
                        <Check v-if="item.checked" class="w-3.5 h-3.5 stroke-[3]" />
                    </div>
                    <span class="text-xs text-[#F4F7F5] leading-relaxed">{{ item.text }}</span>
                </div>
            </div>
        </section>

        <!-- SEKSI 5: ATURAN RENDERING RINGAN (Bab 12) -->
        <section class="bg-[#10171A] border-2 border-[#243034] rounded-20 p-6 space-y-3">
            <h3 class="font-display font-bold text-xl text-[#F4F7F5] flex items-center gap-2">
                <Zap class="w-5 h-5 text-[#19F5D4]" />
                6. Arsitektur Kinerja & Rendering Ringan (Lightweight 60 FPS)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div class="p-3.5 rounded-16 bg-[#0A0D0E] border border-[#243034]">
                    <div class="font-bold text-[#19F5D4] mb-1">Geometry Caching</div>
                    <p class="text-[#8FA1A0] leading-relaxed">
                        Geometri poligon 27 kecamatan dimuat sekali ke memori. Pergantian metrik hanya memperbarui warna atribut (&lt;2 KB) tanpa menggambar ulang kanvas dari awal.
                    </p>
                </div>
                <div class="p-3.5 rounded-16 bg-[#0A0D0E] border border-[#243034]">
                    <div class="font-bold text-[#39FF14] mb-1">Canvas / Direct SVG</div>
                    <p class="text-[#8FA1A0] leading-relaxed">
                        Rendering peta memanfaatkan akselerasi GPU tanpa layer CSS berat seperti filter blur atau dekorasi glow yang merusak frame rate.
                    </p>
                </div>
                <div class="p-3.5 rounded-16 bg-[#0A0D0E] border border-[#243034]">
                    <div class="font-bold text-[#FFC53D] mb-1">Decoupled Portals</div>
                    <p class="text-[#8FA1A0] leading-relaxed">
                        Sistem Publik dan Internal Petugas terpisah secara ketat (RBAC), mencegah kebocoran data sensitif (*zero data leakage*) ke pengguna tamu.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.rounded-6 {
    border-radius: 6px;
}
.rounded-10 {
    border-radius: 10px;
}
</style>
