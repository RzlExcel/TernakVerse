<script setup lang="ts">
import type { AdminFormData } from '../../../composables/ternakverse/admin/useAdminPortal';

const props = defineProps<{
    isEditing: boolean;
    formData: AdminFormData;
}>();

const emit = defineEmits<{
    (e: 'save', createAnother: boolean): void;
    (e: 'cancel'): void;
}>();
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
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084]/30 transition-all"
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
                        class="w-full h-10 bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg px-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084]/30 transition-all font-mono tabular-nums"
                    />
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
                            <option value="Sapi Potong" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Sapi Potong Pedaging</option>
                            <option value="Sapi Perah" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Sapi Perah Penghasil Susu</option>
                            <option value="Kambing / Domba" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Kambing &amp; Domba</option>
                            <option value="Ayam Broiler" class="bg-white dark:bg-[#181818] text-[#171B17] dark:text-[#F5F5F5]">Unggas / Ayam Broiler</option>
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
                            <label class="block text-[11px] font-semibold text-[#6E776D] dark:text-[#B3B3B3] mb-1">Populasi Kambing</label>
                            <input
                                v-model.number="props.formData.kambing"
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
                        placeholder="Tuliskan catatan verifikasi lapangan, riwayat vaksinasi, atau anjuran biosekuriti dinas..."
                        class="w-full flex-1 min-h-[200px] bg-[#F4F5F2] dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-lg p-3.5 text-sm text-[#171B17] dark:text-[#F5F5F5] placeholder:text-[#858585] outline-none focus:border-[#00D084] focus:ring-1 focus:ring-[#00D084]/30 resize-y transition-all"
                    ></textarea>
                </div>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="flex flex-wrap items-center gap-3 pt-5 border-t border-[#E0E4DD] dark:border-[#1E1E1E] w-full">
                <button
                    type="button"
                    @click="emit('save', false)"
                    class="h-10 px-6 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold tracking-wider transition-colors cursor-pointer"
                >
                    SIMPAN PERUBAHAN
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
                    class="h-10 px-4 rounded-lg bg-white dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[#444C43] dark:text-[#B3B3B3] hover:bg-[#EDEFEB] dark:hover:bg-[#282828] text-xs font-semibold transition-all cursor-pointer"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
</template>
