<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use App\Models\Kecamatan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Eksekusi seluruh Master Seeder dan Ingesti Spasial KAK Banyumas 2026.
     */
    public function run(): void
    {
        $this->command->info('=== Memulai Database Seeding: TERNAKVERSE ===');

        // 1. Akun Pengguna & Hak Akses RBAC
        $this->call(UserRoleSeeder::class);
        $this->command->info('✓ Akun Pengguna & RBAC terkonfigurasi.');

        // 2. Master Data Komoditas (6 Komoditas)
        $this->call(MasterKomoditasSeeder::class);
        $this->command->info('✓ Master Komoditas (6 Komoditas) terisi.');

        // 3. Master Data Kategori Produksi (Daging, Susu, Telur)
        $this->call(MasterProduksiSeeder::class);
        $this->command->info('✓ Master Kategori Produksi terisi.');

        // 4. Master Data Penyakit & Parameter Zonasi (PMK, LSD)
        $this->call(MasterPenyakitSeeder::class);
        $this->command->info('✓ Master Penyakit & Parameter Zonasi terisi.');

        // 5. Pastikan Batas Wilayah Spasial Terpasang Sebelum Relasi Data
        if (Kecamatan::count() < 27) {
            $this->command->info('Batas wilayah belum lengkap, menjalankan geo:import-banyumas...');
            Artisan::call('geo:import-banyumas');
            $this->command->info(Artisan::output());
        }

        // 6. Fasilitas Rumah Potong Hewan (RPH)
        $this->call(FasilitasRphSeeder::class);
        $this->command->info('✓ Titik Fasilitas RPH terpasang.');

        // 7. Data Tabular Riil KAK Banyumas 2026 & Populasi
        $this->call(BanyumasKakSeeder::class);
        $this->command->info('✓ Data Riil KAK Banyumas (Populasi Sapi, PMK, LSD, Produksi) terpasang.');

        $this->command->info('=== Seluruh Database Seeder Berhasil Dijalankan! ===');
    }
}
