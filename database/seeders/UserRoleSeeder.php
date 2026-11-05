<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi akun administratif & pimpinan dinas untuk pengamanan RBAC WebGIS.
     */
    public function run(): void
    {
        // 1. Pastikan kolom role ada pada tabel users jika belum
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function ($table) {
                $table->string('role', 30)->default('stakeholder');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role;');
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('superadmin', 'admin_dinas', 'admin', 'stakeholder'));");
        }

        // 2. Sinkronisasi Dynamic RBAC (roles, permissions, user_roles) jika tabel tersedia
        $roleModels = [];
        if (Schema::hasTable('roles')) {
            $systemRoles = [
                [
                    'slug' => 'superadmin',
                    'name' => 'Super Administrator',
                    'description' => 'Akses penuh ke seluruh modul sistem, master data, konfigurasi, dan manajemen pengguna',
                    'is_system' => true,
                    'is_active' => true,
                ],
                [
                    'slug' => 'admin_dinas',
                    'name' => 'Administrator Dinas Teknis',
                    'description' => 'Akses operasional dinas teknis, pembaruan fasilitas peternakan, perizinan, dan pelaporan',
                    'is_system' => true,
                    'is_active' => true,
                ],
                [
                    'slug' => 'admin',
                    'name' => 'Administrator Sistem',
                    'description' => 'Akses administratif operasional sistem',
                    'is_system' => true,
                    'is_active' => true,
                ],
                [
                    'slug' => 'stakeholder',
                    'name' => 'Pimpinan & Pemangku Kepentingan',
                    'description' => 'Akses eksekutif read-only untuk pemantauan data, analitik peta, dan laporan resmi',
                    'is_system' => true,
                    'is_active' => true,
                ],
            ];

            foreach ($systemRoles as $r) {
                $roleModels[$r['slug']] = Role::updateOrCreate(
                    ['slug' => $r['slug']],
                    $r
                );
            }

            if (Schema::hasTable('permissions') && Schema::hasTable('role_permissions')) {
                $allPerms = Permission::pluck('id')->all();
                if (! empty($allPerms)) {
                    $roleModels['superadmin']?->permissions()->sync($allPerms);
                    $roleModels['admin']?->permissions()->sync($allPerms);
                    $roleModels['admin_dinas']?->permissions()->sync($allPerms);
                }

                $stakeholderSlugs = [
                    'dashboard.view',
                    'dashboard.analytics',
                    'population.view',
                    'production.view',
                    'disease.view',
                    'webgis.view',
                    'ews.view',
                    'reports.view',
                    'reports.export_pdf',
                    'reports.export_excel',
                    'reports.export_kugi',
                    'reports.export_iso_xml',
                ];
                $stakeholderPerms = Permission::whereIn('slug', $stakeholderSlugs)->pluck('id')->all();
                if (! empty($stakeholderPerms)) {
                    $roleModels['stakeholder']?->permissions()->sync($stakeholderPerms);
                }
            }
        }

        // 3. Akun Super Administrator Resmi (Satrio Wibowo) & Alias
        $userSuperAdmin = User::updateOrCreate(
            ['email' => 'superadmin@banyumaskab.go.id'],
            [
                'nama' => 'Satrio Wibowo',
                'password' => Hash::make('password'),
                'nip_pegawai' => '198901012015021001',
                'nomor_telepon' => '081234567890',
                'aktif' => true,
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        $userAdminAlias = User::updateOrCreate(
            ['email' => 'admin@banyumaskab.go.id'],
            [
                'nama' => 'Administrator WebGIS Banyumas',
                'password' => Hash::make('password'),
                'nip_pegawai' => '198901012015021001',
                'nomor_telepon' => '081234567890',
                'aktif' => true,
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        // 4. Akun Administrator Dinas Teknis (Operator Dinas Teknis)
        $userAdminDinas = User::updateOrCreate(
            ['email' => 'admin.diskanak@banyumaskab.go.id'],
            [
                'nama' => 'Operator Dinas Teknis',
                'password' => Hash::make('password'),
                'nip_pegawai' => '199002022018011002',
                'nomor_telepon' => '081234567891',
                'aktif' => true,
                'role' => 'admin_dinas',
                'email_verified_at' => now(),
            ]
        );

        // 5. Akun Stakeholder / Petugas Dinas Resmi (drh. Sulistyo) & Alias
        $userStakeholder = User::updateOrCreate(
            ['email' => 'drh.sulistyo@banyumaskab.go.id'],
            [
                'nama' => 'drh. Sulistyo',
                'password' => Hash::make('password'),
                'nip_pegawai' => '198003152006041008',
                'nomor_telepon' => '081122334455',
                'aktif' => true,
                'role' => 'stakeholder',
                'email_verified_at' => now(),
            ]
        );

        $userDinasAlias = User::updateOrCreate(
            ['email' => 'dinas@banyumaskab.go.id'],
            [
                'nama' => 'Kusbiono Jumadi Rahman, S.Pt., M.Pt.',
                'password' => Hash::make('password'),
                'nip_pegawai' => '197805052010011021',
                'nomor_telepon' => '081122334455',
                'aktif' => true,
                'role' => 'stakeholder',
                'email_verified_at' => now(),
            ]
        );

        // 6. Hubungkan ke user_roles pivot table jika ada
        if (Schema::hasTable('user_roles') && ! empty($roleModels)) {
            $syncPivot = function (User $u, string $slug) use ($roleModels): void {
                if (isset($roleModels[$slug])) {
                    DB::table('user_roles')->updateOrInsert(
                        ['user_id' => $u->id],
                        [
                            'role_id' => $roleModels[$slug]->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            };

            $syncPivot($userSuperAdmin, 'superadmin');
            $syncPivot($userAdminAlias, 'superadmin');
            $syncPivot($userAdminDinas, 'admin_dinas');
            $syncPivot($userStakeholder, 'stakeholder');
            $syncPivot($userDinasAlias, 'stakeholder');
        }
    }
}
