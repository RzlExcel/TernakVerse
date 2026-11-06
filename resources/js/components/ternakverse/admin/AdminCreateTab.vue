<script setup lang="ts">
import { watch } from 'vue';
import type { AdminFormData } from '../../../composables/ternakverse/admin/useAdminPortal';
import { KECAMATAN_LIST } from '../../../data/banyumasData';

const props = defineProps<{
    isEditing: boolean;
    formData: AdminFormData;
}>();

const emit = defineEmits<{
    (e: 'save', createAnother: boolean): void;
    (e: 'cancel'): void;
}>();

watch(
    () => props.formData,
    (newData) => {
        if (!newData) return;

        // Normalize legacy kategori_utama values
        if (newData.kategori_utama === 'Kambing / Domba') {
            newData.kategori_utama = 'Kambing Potong';
        } else if (newData.kategori_utama === 'Ayam Broiler') {
            newData.kategori_utama = 'Ayam Potong / Broiler';
        }

        // Auto-populate specific commodity fields when editing if empty
        if (props.isEditing && (newData.kode_kec || newData.nama)) {
            const matched = KECAMATAN_LIST.find(
                (k) =>
                    (newData.kode_kec && k.kode_kec === newData.kode_kec) ||
                    (newData.nama && k.nama.toLowerCase() === newData.nama.toLowerCase())
            );
            if (matched) {
                if (!newData.kambing_perah && matched.kambing_perah) {
                    newData.kambing_perah = matched.kambing_perah;
                }
                if (!newData.ayam_petelur && (matched.ayam_petelur || matched.ayam_layer)) {
                    newData.ayam_petelur = matched.ayam_petelur ?? matched.ayam_layer ?? 0;
                }
                if (!newData.luas_hmt_ha && matched.luas_hmt_ha) {
                    newData.luas_hmt_ha = matched.luas_hmt_ha;
                }
            }
        }
    },
    { immediate: true, deep: true }
);
</script>

<template>
    <div class="w-full space-y-4">
        <!-- Breadcrumbs -->
        <div class="text-xs font-semibold text-[#6E776D] dark:text-[#858585]">
            Data Peternakan &gt; {{ props.isEditing ? 'Edit Data Wilayah' : 'Input Data Baru' }}
        </div>

        <!-- Header -->
        <div>
            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                {{ props.isEditing ? 'Edit Data Peternakan Wilayah' : 'Input Data Peternakan Baru' }}
            </h1>
            <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-0.5">
                Formulir pencatatan dan verifikasi berkala populasi komoditas, produksi, serta kasus penyakit hewan
            </p>
        </div>

        <!-- FORM GRID FULL WIDTH -->
        <div class="w-full bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 md:p-8 space-y-6 shadow-sm">
            <!-- Row 1: 2 Kolom Seimbang (Nama Wilayah & Kode Kemendagri) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                <div class="w-full">
                    <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-2">
                        Nama Wilayah / Kecamatan<span class="text-rose-500 dark:text-rose-400">*</span>
                    </label>
                    <input
                        v-model="props.formData.nama"
                        type="text"
                        placeholder="Contoh: Kecamatan Sumbang"
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084]/30 transition-colors"
                    />
                </div>

                <div class="w-full">
                    <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-2">
                        Kode Kemendagri Wilayah<span class="text-rose-500 dark:text-rose-400">*</span>
                    </label>
                    <input
                        v-model="props.formData.kode_kec"
                        type="text"
                        placeholder="Contoh: 33.02.21"
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084]/30 transition-colors font-mono tabular-nums"
                    />
                </div>
            </div>

            <!-- Periode Data Pencatatan (Tahun & Triwulan) -->
            <div class="border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg p-4 bg-[#F8F9F6] dark:bg-[#151515] space-y-3">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-[#00D084]"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#171B17] dark:text-[#F5F5F5]">
                        Periode Data Pencatatan (Tahun &amp; Triwulan)
                    </h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                    <div>
                        <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-2">
                            Tahun Pencatatan<span class="text-rose-500 dark:text-rose-400">*</span>
                        </label>
                        <select
                            v-model.number="props.formData.tahun"
                            class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] cursor-pointer"
                        >
                            <option :value="2024" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">2024</option>
                            <option :value="2025" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">2025</option>
                            <option :value="2026" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">2026 (Tahun Berjalan)</option>
                            <option :value="2027" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">2027</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-2">
                            Triwulan Pencatatan<span class="text-rose-500 dark:text-rose-400">*</span>
                        </label>
                        <select
                            v-model.number="props.formData.triwulan"
                            class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] cursor-pointer"
                        >
                            <option :value="1" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Triwulan I (Jan - Mar)</option>
                            <option :value="2" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Triwulan II (Apr - Jun)</option>
                            <option :value="3" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Triwulan III (Jul - Sep)</option>
                            <option :value="4" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Triwulan IV (Okt - Des)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 2: 2 Kolom Seimbang (Angka Populasi/Produksi/Kasus di Kiri, Catatan di Kanan) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
                <!-- Left Column -->
                <div class="space-y-4 w-full">
                    <div>
                        <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-2">
                            Komoditas Unggulan
                        </label>
                        <select
                            v-model="props.formData.kategori_utama"
                            class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] cursor-pointer"
                        >
                            <option value="Sapi Potong" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Sapi Potong (Pedaging)</option>
                            <option value="Sapi Perah" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Sapi Perah (Penghasil Susu)</option>
                            <option value="Kambing Potong" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Kambing Potong (Pedaging)</option>
                            <option value="Kambing Perah" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Kambing Perah (Susu Kambing)</option>
                            <option value="Domba" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Domba (Pedaging &amp; Wol)</option>
                            <option value="Ayam Potong / Broiler" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Ayam Potong / Broiler (Pedaging)</option>
                            <option value="Ayam Petelur / Layer" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Ayam Petelur / Layer (Penghasil Telur)</option>
                        </select>
                    </div>

                    <!-- Grid Angka Populasi & Kasus -->
                    <div class="grid grid-cols-2 gap-3 w-full">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Sapi Potong</label>
                            <input
                                v-model.number="props.formData.sapi_potong"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Sapi Perah</label>
                            <input
                                v-model.number="props.formData.sapi_perah"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Kambing Potong</label>
                            <input
                                v-model.number="props.formData.kambing"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Kambing Perah</label>
                            <input
                                v-model.number="props.formData.kambing_perah"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Domba</label>
                            <input
                                v-model.number="props.formData.domba"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Ayam Potong / Broiler</label>
                            <input
                                v-model.number="props.formData.ayam_broiler"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Ayam Petelur / Layer</label>
                            <input
                                v-model.number="props.formData.ayam_petelur"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Luas Lahan HMT (Hektar)</label>
                            <input
                                v-model.number="props.formData.luas_hmt_ha"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-rose-600 dark:text-rose-400 mb-1">Kasus Aktif PMK</label>
                            <input
                                v-model.number="props.formData.pmk"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-rose-300 dark:border-rose-500/30 rounded-lg px-3 text-sm text-rose-600 dark:text-rose-400 font-bold outline-none focus:border-rose-500 font-mono tabular-nums"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-amber-600 dark:text-amber-400 mb-1">Kasus Aktif LSD</label>
                            <input
                                v-model.number="props.formData.lsd"
                                type="number"
                                class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-amber-300 dark:border-amber-500/30 rounded-lg px-3 text-sm text-amber-600 dark:text-amber-400 font-bold outline-none focus:border-amber-500 font-mono tabular-nums"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Status Zonasi EWS</label>
                        <select
                            v-model="props.formData.zona"
                            class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] outline-none focus:border-[#00D084] cursor-pointer"
                        >
                            <option value="aman" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Zona Hijau (Aman / Bebas Wabah)</option>
                            <option value="waspada" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Zona Kuning (Waspada)</option>
                            <option value="bahaya" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Zona Merah (Tertular / Bahaya)</option>
                        </select>
                    </div>
                </div>

                <!-- Right Column: Catatan Verifikasi Lapangan -->
                <div class="flex flex-col w-full">
                    <label class="block text-xs font-semibold text-[#171B17] dark:text-[#F5F5F5] mb-2">
                        Catatan Verifikasi Lapangan<span class="text-rose-500 dark:text-rose-400">*</span>
                    </label>
                    <textarea
                        v-model="props.formData.catatan"
                        rows="9"
                        placeholder="Tuliskan catatan verifikasi lapangan, riwayat vaksinasi, atau anjuran biosekuriti dinas"
                        class="w-full flex-1 min-h-48 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg p-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084]/30 resize-y transition-colors"
                    ></textarea>
                </div>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="flex flex-wrap items-center gap-3 pt-5 border-t border-[#E0E4DD] dark:border-[#1E1E1E] w-full">
                <button
                    type="button"
                    @click="emit('save', false)"
                    class="h-10 px-6 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-colors cursor-pointer"
                >
                    Simpan Perubahan
                </button>

                <button
                    type="button"
                    @click="emit('save', true)"
                    class="h-10 px-4 rounded-lg bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] hover:border-[#00D084] text-[#171B17] dark:text-[#F5F5F5] text-xs font-semibold transition-colors cursor-pointer"
                >
                    Simpan &amp; Tambah Lagi
                </button>

                <button
                    type="button"
                    @click="emit('cancel')"
                    class="h-10 px-4 rounded-lg bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3] hover:bg-[#EDEFEB] dark:hover:bg-[#282828] text-xs font-semibold transition-colors cursor-pointer"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
</template>
