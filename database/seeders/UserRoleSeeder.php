<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi akun administratif & pimpinan dinas untuk pengamanan RBAC WebGIS.
     */
    public function run(): void
    {
        // Pastikan kolom role ada pada tabel users jika belum
        $hasRoleCol = DB::select("
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'users' AND column_name = 'role'
        ");

        if (empty($hasRoleCol)) {
            DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(30) DEFAULT 'stakeholder';");
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('admin', 'stakeholder'));");
        }

        // 1. Akun Administrator Sistem (Pengelola Teknis Geospasial)
        User::updateOrCreate(
            ['email' => 'admin@banyumaskab.go.id'],
            [
                'nama' => 'Administrator WebGIS Banyumas',
                'password' => Hash::make('password'),
                'nip_pegawai' => '198901012015021001',
                'nomor_telepon' => '081234567890',
                'aktif' => true,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Stakeholder (Pimpinan Dinas Pertanian & Ketahanan Pangan Kab. Banyumas)
        User::updateOrCreate(
            ['email' => 'dinas@banyumaskab.go.id'],
            [
                'nama' => 'Kusbiono Jumadi Rahman, S.Pt., M.Pt.',
                'password' => Hash::make('password'),
                'nip_pegawai' => '197805052010011021', // Sesuai PRD Bab 8
                'nomor_telepon' => '081122334455',
                'aktif' => true,
                'role' => 'stakeholder',
                'email_verified_at' => now(),
            ]
        );
    }
}
