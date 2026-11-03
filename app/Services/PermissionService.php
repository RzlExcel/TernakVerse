<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class PermissionService
{
    /**
     * In-memory default catalog of 39 permissions across 10 modules.
     *
     * @var list<array{slug: string, name: string, module: string, description: string}>
     */
    protected const DEFAULT_CATALOG = [
        // 1. Dashboard (2)
        [
            'slug' => 'dashboard.view',
            'name' => 'Lihat Dashboard Eksekutif',
            'module' => 'Dashboard',
            'description' => 'Akses pemantauan visual metrik utama peternakan Banyumas',
        ],
        [
            'slug' => 'dashboard.analytics',
            'name' => 'Analisis Metrik & ECharts',
            'module' => 'Dashboard',
            'description' => 'Akses drilldown grafik interaktif, statistik tren, dan visualisasi spasial',
        ],

        // 2. Population (5)
        [
            'slug' => 'population.view',
            'name' => 'Lihat Populasi Ternak',
            'module' => 'Population',
            'description' => 'Melihat data sensus populasi ternak per kecamatan dan desa',
        ],
        [
            'slug' => 'population.create',
            'name' => 'Tambah Data Populasi',
            'module' => 'Population',
            'description' => 'Menambahkan data sensus populasi ternak baru',
        ],
        [
            'slug' => 'population.update',
            'name' => 'Edit Data Populasi',
            'module' => 'Population',
            'description' => 'Mengubah data populasi ternak yang telah tercatat',
        ],
        [
            'slug' => 'population.delete',
            'name' => 'Hapus Data Populasi',
            'module' => 'Population',
            'description' => 'Menghapus catatan data populasi ternak',
        ],
        [
            'slug' => 'population.export',
            'name' => 'Ekspor Data Populasi',
            'module' => 'Population',
            'description' => 'Mengekspor data tabular populasi ternak ke file spreadsheet/CSV',
        ],

        // 3. Production (5)
        [
            'slug' => 'production.view',
            'name' => 'Lihat Produksi Ternak',
            'module' => 'Production',
            'description' => 'Melihat data hasil produksi ternak (daging, susu, telur)',
        ],
        [
            'slug' => 'production.create',
            'name' => 'Tambah Data Produksi',
            'module' => 'Production',
            'description' => 'Mencatat hasil produksi peternakan baru',
        ],
        [
            'slug' => 'production.update',
            'name' => 'Edit Data Produksi',
            'module' => 'Production',
            'description' => 'Memperbarui data hasil produksi peternakan',
        ],
        [
            'slug' => 'production.delete',
            'name' => 'Hapus Data Produksi',
            'module' => 'Production',
            'description' => 'Menghapus data hasil produksi peternakan',
        ],
        [
            'slug' => 'production.export',
            'name' => 'Ekspor Data Produksi',
            'module' => 'Production',
            'description' => 'Mengekspor data produksi komoditas peternakan',
        ],

        // 4. Disease Cases (5)
        [
            'slug' => 'disease.view',
            'name' => 'Lihat Kasus Penyakit',
            'module' => 'Disease Cases',
            'description' => 'Melihat rekaman wabah penyakit hewan ternak (PMK, LSD, dll)',
        ],
        [
            'slug' => 'disease.create',
            'name' => 'Tambah Kasus Penyakit',
            'module' => 'Disease Cases',
            'description' => 'Melaporkan dan mencatat kejadian kasus penyakit baru',
        ],
        [
            'slug' => 'disease.update',
            'name' => 'Edit Kasus Penyakit',
            'module' => 'Disease Cases',
            'description' => 'Memperbarui status penanganan kasus penyakit hewan',
        ],
        [
            'slug' => 'disease.delete',
            'name' => 'Hapus Kasus Penyakit',
            'module' => 'Disease Cases',
            'description' => 'Menghapus rekaman kasus penyakit hewan',
        ],
        [
            'slug' => 'disease.export',
            'name' => 'Ekspor Data Penyakit',
            'module' => 'Disease Cases',
            'description' => 'Mengekspor rekapitulasi epidemiologi penyakit hewan',
        ],

        // 5. WebGIS (3)
        [
            'slug' => 'webgis.view',
            'name' => 'Akses Peta WebGIS',
            'module' => 'WebGIS',
            'description' => 'Melihat peta interaktif tematik geospasial peternakan',
        ],
        [
            'slug' => 'webgis.manage_layers',
            'name' => 'Kelola Layer Geospasial',
            'module' => 'WebGIS',
            'description' => 'Mengatur layer batas administrasi, choropleth, dan styling peta',
        ],
        [
            'slug' => 'webgis.manage_points',
            'name' => 'Kelola Titik Fasilitas Geospasial',
            'module' => 'WebGIS',
            'description' => 'Menambah, mengubah, dan menghapus titik spasial fasilitas RPH & pasar',
        ],

        // 6. Early Warning System (3)
        [
            'slug' => 'ews.view',
            'name' => 'Lihat Status EWS',
            'module' => 'Early Warning System',
            'description' => 'Melihat status peringatan dini dan zonasi kerentanan wilayah',
        ],
        [
            'slug' => 'ews.manage_threshold',
            'name' => 'Kelola Ambang Batas EWS',
            'module' => 'Early Warning System',
            'description' => 'Mengubah bobot scoring dan threshold status zonasi (Hijau/Kuning/Merah)',
        ],
        [
            'slug' => 'ews.recalculate',
            'name' => 'Rekalkulasi Status EWS',
            'module' => 'Early Warning System',
            'description' => 'Memicu kalkulasi ulang algoritma zonasi epidemiologi wilayah',
        ],

        // 7. Reports (5)
        [
            'slug' => 'reports.view',
            'name' => 'Akses Laporan Resmi',
            'module' => 'Reports',
            'description' => 'Melihat rekapitulasi data laporan berkala dinas',
        ],
        [
            'slug' => 'reports.export_pdf',
            'name' => 'Ekspor Laporan PDF',
            'module' => 'Reports',
            'description' => 'Mengunduh laporan resmi dinas berformat PDF',
        ],
        [
            'slug' => 'reports.export_excel',
            'name' => 'Ekspor Laporan Excel',
            'module' => 'Reports',
            'description' => 'Mengunduh laporan resmi dinas berformat Excel (XLSX)',
        ],
        [
            'slug' => 'reports.export_kugi',
            'name' => 'Ekspor Katalog KUGI',
            'module' => 'Reports',
            'description' => 'Mengekspor data geospasial sesuai standar KUGI BIG',
        ],
        [
            'slug' => 'reports.export_iso_xml',
            'name' => 'Ekspor Metadata ISO 19115',
            'module' => 'Reports',
            'description' => 'Mengekspor file metadata geospasial standar ISO 19115 XML',
        ],

        // 8. Audit Trail (2)
        [
            'slug' => 'audit.view',
            'name' => 'Akses Log Audit',
            'module' => 'Audit Trail',
            'description' => 'Melihat catatan log aktivitas forensik operator dan sistem',
        ],
        [
            'slug' => 'audit.export',
            'name' => 'Ekspor Log Audit',
            'module' => 'Audit Trail',
            'description' => 'Mengekspor riwayat log audit forensik',
        ],

        // 9. User Management (5)
        [
            'slug' => 'users.view',
            'name' => 'Lihat Daftar Pengguna',
            'module' => 'User Management',
            'description' => 'Melihat daftar akun petugas dinas dan pimpinan',
        ],
        [
            'slug' => 'users.create',
            'name' => 'Tambah Pengguna Baru',
            'module' => 'User Management',
            'description' => 'Mendaftarkan akun petugas dinas baru',
        ],
        [
            'slug' => 'users.update',
            'name' => 'Perbarui Data Pengguna',
            'module' => 'User Management',
            'description' => 'Mengubah identitas, email, dan status aktif pengguna',
        ],
        [
            'slug' => 'users.delete',
            'name' => 'Hapus Pengguna',
            'module' => 'User Management',
            'description' => 'Menghapus atau menonaktifkan akun pengguna',
        ],
        [
            'slug' => 'users.assign_role',
            'name' => 'Tetapkan Peran Pengguna',
            'module' => 'User Management',
            'description' => 'Mengubah penugasan peran (role) kepada akun pengguna',
        ],

        // 10. RBAC Management (4)
        [
            'slug' => 'rbac.view',
            'name' => 'Lihat Konfigurasi RBAC',
            'module' => 'RBAC Management',
            'description' => 'Melihat daftar peran dinamis dan matriks hak akses',
        ],
        [
            'slug' => 'rbac.manage_roles',
            'name' => 'Kelola Peran (Roles)',
            'module' => 'RBAC Management',
            'description' => 'Membuat, mengubah, dan menghapus peran kustom',
        ],
        [
            'slug' => 'rbac.manage_permissions',
            'name' => 'Kelola Master Izin Akses',
            'module' => 'RBAC Management',
            'description' => 'Mengonfigurasi deskripsi dan master hak akses sistem',
        ],
        [
            'slug' => 'rbac.assign_permissions',
            'name' => 'Tetapkan Hak Akses ke Peran',
            'module' => 'RBAC Management',
            'description' => 'Menyesuaikan matriks hak akses untuk setiap peran',
        ],
    ];

    /**
     * Default read-only permission slugs for the 'stakeholder' role.
     *
     * @var list<string>
     */
    protected const STAKEHOLDER_PERMISSIONS = [
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

    /**
     * Fundamental permissions that must not be revoked from Super Admin.
     *
     * @var list<string>
     */
    protected const ADMIN_FUNDAMENTAL_PERMISSIONS = [
        'rbac.manage_permissions',
        'rbac.view',
        'dashboard.view',
    ];

    /**
     * Get the full default catalog of 39 permissions.
     *
     * @return list<array{slug: string, name: string, module: string, description: string}>
     */
    public function getDefaultCatalog(): array
    {
        return self::DEFAULT_CATALOG;
    }

    /**
     * Get all catalog permission slugs.
     *
     * @return list<string>
     */
    public function getAllPermissionSlugs(): array
    {
        return array_column(self::DEFAULT_CATALOG, 'slug');
    }

    /**
     * Get permissions grouped by module.
     *
     * @return array<string, list<array{slug: string, name: string, module: string, description: string}>>
     */
    public function getGroupedPermissions(): array
    {
        try {
            if ($this->hasRbacTables()) {
                $dbPermissions = Permission::orderBy('id')->get();
                if ($dbPermissions->isNotEmpty()) {
                    return $dbPermissions->groupBy('module')->toArray();
                }
            }
        } catch (Throwable) {
            // Fallback to in-memory catalog
        }

        $grouped = [];
        foreach (self::DEFAULT_CATALOG as $perm) {
            $grouped[$perm['module']][] = $perm;
        }

        return $grouped;
    }

    /**
     * Check if user or role has a specific permission slug.
     */
    public function hasPermission(User|Role|string $userOrRole, string $permissionSlug): bool
    {
        $roleSlug = $this->resolveRoleSlug($userOrRole);

        // Super Admin has all permissions unconditionally
        if ($roleSlug === 'admin') {
            return true;
        }

        // Stakeholder role is strictly read-only
        if ($roleSlug === 'stakeholder') {
            return in_array($permissionSlug, self::STAKEHOLDER_PERMISSIONS, true);
        }

        // Custom role: check database assigned permissions with caching
        $assigned = $this->getPermissionsForRoleSlug($roleSlug);

        return in_array($permissionSlug, $assigned, true);
    }

    /**
     * Get all permission slugs available to a given user.
     *
     * @return list<string>
     */
    public function getPermissionsForUser(User $user): array
    {
        $roleSlug = strtolower((string) $user->role);

        if ($roleSlug === 'admin') {
            return $this->getAllPermissionSlugs();
        }

        if ($roleSlug === 'stakeholder') {
            return self::STAKEHOLDER_PERMISSIONS;
        }

        return $this->getPermissionsForRoleSlug($roleSlug);
    }

    /**
     * Get all permission slugs for a role slug (with cache and DB/in-memory fallback).
     *
     * @return list<string>
     */
    public function getPermissionsForRoleSlug(string $roleSlug): array
    {
        $cacheKey = "rbac_role_permissions_{$roleSlug}";

        return Cache::remember($cacheKey, 3600, function () use ($roleSlug): array {
            if ($roleSlug === 'admin') {
                return $this->getAllPermissionSlugs();
            }

            if ($roleSlug === 'stakeholder') {
                return self::STAKEHOLDER_PERMISSIONS;
            }

            try {
                if ($this->hasRbacTables()) {
                    $role = Role::where('slug', $roleSlug)->first();
                    if ($role) {
                        return $role->permissions()->pluck('slug')->all();
                    }
                }
            } catch (Throwable) {
                // Fallback to empty if DB query fails for unknown custom role
            }

            return [];
        });
    }

    /**
     * Assign permissions to a role with mandatory security guardrails.
     *
     * @param Role|string $role
     * @param array<int|string> $permissionSlugsOrIds
     * @throws InvalidArgumentException
     */
    public function assignPermissionsToRole(Role|string $role, array $permissionSlugsOrIds): void
    {
        $roleModel = is_string($role) ? Role::where('slug', $role)->firstOrFail() : $role;
        $roleSlug = strtolower($roleModel->slug);

        // Resolve requested permissions to array of slugs
        $requestedSlugs = $this->normalizeToSlugs($permissionSlugsOrIds);

        // SECURITY GUARDRAIL (Prompt §5.C): Stakeholder is strictly Read-Only
        if ($roleSlug === 'stakeholder') {
            foreach ($requestedSlugs as $slug) {
                if ($this->isOperationalOrAdministrative($slug)) {
                    throw new InvalidArgumentException(
                        'Kebijakan Keamanan: Role Stakeholder diwajibkan Read-Only dan tidak dapat diberikan izin operasional tulis atau administratif.'
                    );
                }
            }
        }

        // SECURITY GUARDRAIL (Prompt §5.B): Super Admin fundamental permissions cannot be revoked
        if ($roleSlug === 'admin') {
            foreach (self::ADMIN_FUNDAMENTAL_PERMISSIONS as $fundamental) {
                if (!in_array($fundamental, $requestedSlugs, true)) {
                    throw new InvalidArgumentException(
                        'Kebijakan Keamanan: Izin fundamental (rbac.manage_permissions, rbac.view, dashboard.view) tidak dapat dicabut dari Super Admin.'
                    );
                }
            }
        }

        // Resolve permission IDs from DB
        $permissionIds = Permission::whereIn('slug', $requestedSlugs)->pluck('id')->all();

        $oldSlugs = $roleModel->permissions()->pluck('slug')->all();

        // Perform assignment inside transaction
        DB::transaction(function () use ($roleModel, $permissionIds): void {
            $roleModel->permissions()->sync($permissionIds);
        });

        // Audit Trail Logging
        AuditLog::catat(
            'UPDATE',
            'role_permissions',
            (int) $roleModel->id,
            ['permissions' => $oldSlugs],
            ['permissions' => $requestedSlugs]
        );

        // Invalidate Cache
        $this->clearPermissionCache($roleSlug);
    }

    /**
     * Check if a permission slug represents operational write or administrative power.
     */
    public function isOperationalOrAdministrative(string $slug): bool
    {
        if (
            str_contains($slug, '.create') ||
            str_contains($slug, '.update') ||
            str_contains($slug, '.delete')
        ) {
            return true;
        }

        if (
            str_starts_with($slug, 'users.') ||
            str_starts_with($slug, 'rbac.') ||
            str_starts_with($slug, 'audit.')
        ) {
            return true;
        }

        if (in_array($slug, [
            'ews.manage_threshold',
            'ews.recalculate',
            'webgis.manage_layers',
            'webgis.manage_points',
        ], true)) {
            return true;
        }

        return false;
    }

    /**
     * Clear permission cache for a role or all roles.
     */
    public function clearPermissionCache(?string $roleSlug = null): void
    {
        if ($roleSlug) {
            Cache::forget("rbac_role_permissions_{$roleSlug}");
        } else {
            foreach (Role::pluck('slug') as $slug) {
                Cache::forget("rbac_role_permissions_{$slug}");
            }
        }
    }

    /**
     * Check whether dynamic RBAC database tables exist.
     */
    public function hasRbacTables(): bool
    {
        try {
            return Schema::hasTable('roles') && Schema::hasTable('permissions') && Schema::hasTable('role_permissions');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Normalize permission IDs or slugs into a list of slugs.
     *
     * @param array<int|string> $permissionSlugsOrIds
     * @return list<string>
     */
    protected function normalizeToSlugs(array $permissionSlugsOrIds): array
    {
        if (empty($permissionSlugsOrIds)) {
            return [];
        }

        $allSlugs = [];
        $ids = [];

        foreach ($permissionSlugsOrIds as $item) {
            if (is_numeric($item)) {
                $ids[] = (int) $item;
            } elseif (is_string($item)) {
                $allSlugs[] = $item;
            }
        }

        if (!empty($ids)) {
            $fromIds = Permission::whereIn('id', $ids)->pluck('slug')->all();
            $allSlugs = array_merge($allSlugs, $fromIds);
        }

        return array_values(array_unique($allSlugs));
    }

    /**
     * Resolve role slug string from User, Role, or string.
     */
    protected function resolveRoleSlug(User|Role|string $userOrRole): string
    {
        if ($userOrRole instanceof User) {
            return strtolower((string) $userOrRole->role);
        }

        if ($userOrRole instanceof Role) {
            return strtolower((string) $userOrRole->slug);
        }

        return strtolower($userOrRole);
    }
}
