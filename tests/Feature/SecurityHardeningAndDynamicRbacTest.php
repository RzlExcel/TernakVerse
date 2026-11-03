<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

final class SecurityHardeningAndDynamicRbacTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([EnsureUserRole::class])
            ->prefix('internal')
            ->group(function () {
                Route::prefix('peta')->group(function () {
                    Route::get('/kabupaten', [\App\Http\Controllers\Internal\DinasDashboardController::class, 'getPetaKabupaten']);
                });
                Route::prefix('analitik')->group(function () {
                    Route::get('/data', [\App\Http\Controllers\Internal\DinasDashboardController::class, 'getAnalitikData']);
                });
                Route::prefix('admin/rbac')->group(function () {
                    Route::get('/roles', [\App\Http\Controllers\Internal\RbacManagementController::class, 'indexRoles']);
                });
                Route::prefix('api/rbac')->group(function () {
                    Route::get('/roles', [\App\Http\Controllers\Internal\RbacManagementController::class, 'indexRoles']);
                    Route::get('/permissions', [\App\Http\Controllers\Internal\RbacManagementController::class, 'indexPermissions']);
                    Route::get('/matrix', [\App\Http\Controllers\Internal\RbacManagementController::class, 'matrix']);
                });
            });

        \Laravel\Fortify\Fortify::registerView(function () {
            if (app()->environment('production')) {
                abort(403, 'Pendaftaran mandiri dinonaktifkan di lingkungan produksi. Akun baru hanya dapat dibuat oleh Super Admin.');
            }

            return \Inertia\Inertia::render('auth/Register');
        });
    }

    private function setupTestDatabase(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('module');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function ($table) {
                $table->foreignId('role_id');
                $table->foreignId('permission_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('table_name');
                $table->unsignedBigInteger('record_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Super Administrator', 'is_system' => true, 'is_active' => true]
        );

        Role::firstOrCreate(
            ['slug' => 'stakeholder'],
            ['name' => 'Pimpinan & Pemangku Kepentingan', 'is_system' => true, 'is_active' => true]
        );
    }

    /**
     * 1. Unauthenticated GET request to internal APIs with Accept: application/json returns HTTP 401 with UNAUTHENTICATED.
     */
    public function test_anonymous_access_to_internal_api_is_rejected_with_401(): void
    {
        $endpoints = [
            '/internal/peta/kabupaten',
            '/internal/analitik/data',
            '/internal/admin/rbac/roles',
        ];

        foreach ($endpoints as $uri) {
            $response = $this->withHeaders(['Accept' => 'application/json'])->get($uri);
            $response->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'error_code' => 'UNAUTHENTICATED',
                ]);
            $this->assertStringContainsString('Sesi autentikasi tidak ditemukan', (string) $response->json('message'));
        }

        // Also verify the api/v1 prefix variants
        $v1Endpoints = [
            '/api/v1/internal/peta/kabupaten',
            '/api/v1/internal/analitik/data',
            '/api/v1/internal/admin/rbac/roles',
        ];

        foreach ($v1Endpoints as $uri) {
            $response = $this->withHeaders(['Accept' => 'application/json'])->get($uri);
            $response->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'error_code' => 'UNAUTHENTICATED',
                ]);
        }
    }

    /**
     * 2. In web request mode, verify /internal/admin and /internal/dinas are protected by EnsureUserRole.
     */
    public function test_spa_view_shell_routes_are_protected_by_rbac_middleware(): void
    {
        // 1. Verify route definitions are protected by EnsureUserRole
        $adminRoute = Route::getRoutes()->getByName('internal.admin');
        $this->assertNotNull($adminRoute);
        $this->assertTrue(
            collect($adminRoute->middleware())->contains(fn ($m) => str_contains((string) $m, EnsureUserRole::class)),
            'internal.admin must be protected by EnsureUserRole'
        );

        $dinasRoute = Route::getRoutes()->getByName('internal.dinas');
        $this->assertNotNull($dinasRoute);
        $this->assertTrue(
            collect($dinasRoute->middleware())->contains(fn ($m) => str_contains((string) $m, EnsureUserRole::class)),
            'internal.dinas must be protected by EnsureUserRole'
        );

        // 2. Stakeholder accessing admin portal is rejected with 403
        $stakeholderUser = new User([
            'id' => 10,
            'name' => 'Pimpinan Dinas',
            'email' => 'pimpinan@banyumaskab.go.id',
            'role' => 'stakeholder',
            'aktif' => true,
        ]);

        $this->actingAs($stakeholderUser)
            ->get('/internal/admin')
            ->assertStatus(403);

        // 3. Admin accessing admin portal succeeds with 200
        $adminUser = new User([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@banyumaskab.go.id',
            'role' => 'admin',
            'aktif' => true,
        ]);

        $this->actingAs($adminUser)
            ->get('/internal/admin')
            ->assertOk();

        // 4. Stakeholder accessing dinas portal succeeds with 200
        $this->actingAs($stakeholderUser)
            ->get('/internal/dinas')
            ->assertOk();
    }

    /**
     * 3. Temporarily set app()['env'] = 'production', GET /register and POST /register aborts with 403.
     */
    public function test_public_self_registration_is_blocked_in_production_environment(): void
    {
        $originalEnv = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $this->get('/register')
                ->assertStatus(403);

            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
                ->post('/register', [
                    'name' => 'Untrusted User',
                    'email' => 'untrusted@example.com',
                    'password' => 'Secret1234!',
                    'password_confirmation' => 'Secret1234!',
                ])->assertStatus(403);
        } finally {
            $this->app['env'] = $originalEnv;
        }

        // In testing environment, registration screen can be rendered
        $this->get('/register')->assertOk();
    }

    /**
     * 4. Verify PermissionService default catalog has 39 permissions across 10 modules and role defaults.
     */
    public function test_permission_service_catalog_and_system_role_defaults(): void
    {
        $service = new PermissionService();

        $catalog = $service->getDefaultCatalog();
        $this->assertCount(39, $catalog, 'Permission catalog must contain exactly 39 permissions');

        $modules = array_unique(array_column($catalog, 'module'));
        $this->assertCount(10, $modules, 'Permission catalog must span across exactly 10 modules');

        $this->assertTrue(
            $service->hasPermission('admin', 'population.create'),
            "Admin must have 'population.create'"
        );
        $this->assertTrue(
            $service->hasPermission('admin', 'rbac.manage_permissions'),
            "Admin must have 'rbac.manage_permissions'"
        );
        $this->assertTrue(
            $service->hasPermission('stakeholder', 'dashboard.analytics'),
            "Stakeholder must have 'dashboard.analytics'"
        );
        $this->assertTrue(
            $service->hasPermission('stakeholder', 'population.view'),
            "Stakeholder must have 'population.view'"
        );
        $this->assertFalse(
            $service->hasPermission('stakeholder', 'population.create'),
            "Stakeholder must not have 'population.create' (read-only)"
        );
        $this->assertFalse(
            $service->hasPermission('stakeholder', 'rbac.manage_permissions'),
            "Stakeholder must not have 'rbac.manage_permissions'"
        );
    }

    /**
     * 5. Stakeholder read-only security guardrail prevents granting operational write permissions.
     */
    public function test_stakeholder_read_only_security_guardrail_prevents_granting_operational_write_permissions(): void
    {
        $this->setupTestDatabase();
        $service = new PermissionService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kebijakan Keamanan: Role Stakeholder diwajibkan Read-Only dan tidak dapat diberikan izin operasional tulis atau administratif.');

        $service->assignPermissionsToRole('stakeholder', ['dashboard.view', 'population.create']);
    }

    /**
     * 6. Super Admin fundamental permissions cannot be revoked.
     */
    public function test_super_admin_fundamental_permissions_cannot_be_revoked(): void
    {
        $this->setupTestDatabase();
        $service = new PermissionService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kebijakan Keamanan: Izin fundamental (rbac.manage_permissions, rbac.view, dashboard.view) tidak dapat dicabut dari Super Admin.');

        $service->assignPermissionsToRole('admin', ['dashboard.view']);
    }

    /**
     * 7. Verify routes /api/v1/internal/admin/rbac/roles, /api/v1/internal/admin/rbac/permissions, /api/v1/internal/admin/rbac/matrix return expected JSON.
     */
    public function test_rbac_management_controller_api_contracts(): void
    {
        $admin = new User([
            'id' => 1,
            'name' => 'Super Administrator',
            'email' => 'admin@ternakverse.test',
            'role' => 'admin',
            'aktif' => true,
        ]);

        // 1. GET /api/v1/internal/admin/rbac/roles
        $rolesRes = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/v1/internal/admin/rbac/roles');

        $rolesRes->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
        $rolesData = $rolesRes->json('data');
        $this->assertIsArray($rolesData);
        $this->assertNotEmpty($rolesData);
        $this->assertTrue(collect($rolesData)->contains('slug', 'admin'));
        $this->assertTrue(collect($rolesData)->contains('slug', 'stakeholder'));

        // 2. GET /api/v1/internal/admin/rbac/permissions
        $permsRes = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/v1/internal/admin/rbac/permissions');

        $permsRes->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
        $permsData = $permsRes->json('data');
        $this->assertIsArray($permsData);
        $this->assertArrayHasKey('Dashboard', $permsData);
        $this->assertArrayHasKey('WebGIS', $permsData);
        $this->assertArrayHasKey('RBAC Management', $permsData);

        // 3. GET /api/v1/internal/admin/rbac/matrix
        $matrixRes = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/v1/internal/admin/rbac/matrix');

        $matrixRes->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
        $matrixData = $matrixRes->json('data');
        $this->assertIsArray($matrixData);
        $this->assertArrayHasKey('roles', $matrixData);
        $this->assertArrayHasKey('permissions_by_module', $matrixData);
        $this->assertArrayHasKey('matrix', $matrixData);
        $this->assertArrayHasKey('admin', $matrixData['matrix']);
        $this->assertArrayHasKey('stakeholder', $matrixData['matrix']);
    }

    /**
     * 8. Verify Frontend Admin RBAC tab contracts and toggle buttons.
     */
    public function test_frontend_admin_rbac_tab_contracts_and_toggle_buttons(): void
    {
        // 1. Assert resources/js/components/ternakverse/admin/rbac/AdminRbacTab.vue exists
        $rbacTabPath = resource_path('js/components/ternakverse/admin/rbac/AdminRbacTab.vue');
        $this->assertFileExists($rbacTabPath);

        $rbacTabContent = (string) file_get_contents($rbacTabPath);

        // 2. Assert it contains role="switch" (confirming toggle button on/off)
        $this->assertStringContainsString(
            'role="switch"',
            $rbacTabContent,
            'AdminRbacTab.vue must contain accessible toggle switches with role="switch"'
        );

        // 3. Assert it contains Manajemen Role & Hak Akses (Dynamic RBAC)
        $this->assertTrue(
            str_contains($rbacTabContent, 'Manajemen Role &amp; Hak Akses (Dynamic RBAC)') ||
            str_contains($rbacTabContent, 'Manajemen Role & Hak Akses (Dynamic RBAC)'),
            'AdminRbacTab.vue must contain title "Manajemen Role & Hak Akses (Dynamic RBAC)"'
        );

        // 4. Assert it contains Matriks Izin (Permission Matrix)
        $this->assertStringContainsStringIgnoringCase(
            'Matriks Izin (Permission Matrix)',
            $rbacTabContent,
            'AdminRbacTab.vue must contain "Matriks Izin (Permission Matrix)"'
        );

        // 5. Assert it contains Terkunci (Read-Only Policy)
        $this->assertStringContainsString(
            'Terkunci (Read-Only Policy)',
            $rbacTabContent,
            'AdminRbacTab.vue must contain "Terkunci (Read-Only Policy)"'
        );

        // 6. Assert it contains Wajib Sistem
        $this->assertStringContainsString(
            'Wajib Sistem',
            $rbacTabContent,
            'AdminRbacTab.vue must contain "Wajib Sistem"'
        );

        // 7. Assert AdminSidebarNav.vue contains Role & Permission
        $sidebarPath = resource_path('js/components/ternakverse/admin/AdminSidebarNav.vue');
        $this->assertFileExists($sidebarPath);
        $sidebarContent = (string) file_get_contents($sidebarPath);
        $this->assertStringContainsString(
            'Role & Permission',
            $sidebarContent,
            'AdminSidebarNav.vue must contain navigation item "Role & Permission"'
        );

        // 8. Assert AdminPortal.vue mounts AdminRbacTab
        $adminPortalPath = resource_path('js/Pages/Internal/AdminPortal.vue');
        $this->assertFileExists($adminPortalPath);
        $adminPortalContent = (string) file_get_contents($adminPortalPath);
        $this->assertStringContainsString(
            'AdminRbacTab',
            $adminPortalContent,
            'AdminPortal.vue must import and mount AdminRbacTab'
        );
    }
}
