<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DynamicRbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionService = app(PermissionService::class);
        $catalog = $permissionService->getDefaultCatalog();

        // 1. Seed all 39 permissions
        foreach ($catalog as $permData) {
            Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                [
                    'name' => $permData['name'],
                    'module' => $permData['module'],
                    'description' => $permData['description'],
                ]
            );
        }

        // 2. Seed System Roles (Admin & Stakeholder)
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Administrator',
                'description' => 'Akses penuh ke seluruh modul sistem, konfigurasi, dan manajemen RBAC',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $stakeholderRole = Role::updateOrCreate(
            ['slug' => 'stakeholder'],
            [
                'name' => 'Pimpinan & Pemangku Kepentingan',
                'description' => 'Akses eksekutif read-only untuk pemantauan data, analitik peta, dan laporan resmi',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // 3. Assign all permissions to Admin
        $allPermissionIds = Permission::pluck('id')->all();
        $adminRole->permissions()->sync($allPermissionIds);

        // 4. Assign read-only permissions to Stakeholder
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

        $stakeholderPermissionIds = Permission::whereIn('slug', $stakeholderSlugs)->pluck('id')->all();
        $stakeholderRole->permissions()->sync($stakeholderPermissionIds);

        // Clear RBAC caches
        $permissionService->clearPermissionCache();
    }
}
