<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use App\Models\Kecamatan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Eksekusi seluruh Master Seeder, Ingesti Spasial, dan Data Riil Peternakan Kabupaten Banyumas.
     */
    public function run(): void
    {
        $this->command->info('=== Memulai Database Seeding: TERNAKVERSE ===');

        // 1. Akun Pengguna & Hak Akses RBAC
        $this->call(UserRoleSeeder::class);
        $this->command->info('[OK] Akun Pengguna & RBAC terkonfigurasi.');

        // 2. Pastikan Batas Wilayah Spasial Terpasang Sebelum Relasi Data
        if (Kecamatan::count() < 27) {
            $this->command->info('Batas wilayah belum lengkap, menjalankan geo:import-banyumas...');
            Artisan::call('geo:import-banyumas');
            $this->command->info(Artisan::output());
        }

        // 3. Data Riil Peternakan Kabupaten Banyumas (BPS, Dinkannak, & KAK 2026)
        // Membersihkan dummy data lama & mengisi master komoditas, penyakit, fasilitas RPH, populasi, produksi, dan EWS
        $this->call(RealDataPeternakanSeeder::class);
        $this->command->info('[OK] Seluruh Data Asli Peternakan Banyumas berhasil di-ingest.');

        $this->command->info('=== Seluruh Database Seeder Berhasil Dijalankan! ===');
    }
}
