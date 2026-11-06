<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ThematicInterval;
use App\Models\User;
use App\Services\SystemGovernanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SystemGovernanceModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superadmin;

    protected User $adminDinas;

    protected User $admin;

    protected User $stakeholder;

    /**
     * Track backup filepaths created in tests for absolute cleanup.
     *
     * @var list<string>
     */
    protected array $createdBackupFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure sessions table exists in test database for active session governance tests
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload')->nullable();
                $table->integer('last_activity')->index();
            });
        }

        // 2. Prepare test user principals with distinct RBAC roles
        $this->superadmin = User::firstOrCreate(
            ['email' => 'superadmin.govtest@banyumaskab.go.id'],
            [
                'name' => 'Super Admin Governance Test',
                'nama' => 'Super Admin Governance Test',
                'role' => 'superadmin',
                'password' => bcrypt('GovTestPassword123!'),
                'aktif' => true,
            ]
        );

        $this->adminDinas = User::firstOrCreate(
            ['email' => 'admindinas.govtest@banyumaskab.go.id'],
            [
                'name' => 'Admin Dinas Governance Test',
                'nama' => 'Admin Dinas Governance Test',
                'role' => 'admin_dinas',
                'password' => bcrypt('GovTestPassword123!'),
                'aktif' => true,
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.govtest@banyumaskab.go.id'],
            [
                'name' => 'Admin Teknis Governance Test',
                'nama' => 'Admin Teknis Governance Test',
                'role' => 'admin',
                'password' => bcrypt('GovTestPassword123!'),
                'aktif' => true,
            ]
        );

        $this->stakeholder = User::firstOrCreate(
            ['email' => 'stakeholder.govtest@banyumaskab.go.id'],
            [
                'name' => 'Stakeholder Governance Test',
                'nama' => 'Stakeholder Governance Test',
                'role' => 'stakeholder',
                'password' => bcrypt('GovTestPassword123!'),
                'aktif' => true,
            ]
        );

        // Ensure maintenance mode is off at start of each test
        app(SystemGovernanceService::class)->setMaintenance(false);
    }

    protected function tearDown(): void
    {
        // Clean up any test backup files generated on disk
        foreach ($this->createdBackupFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        // Ensure maintenance mode is clean
        app(SystemGovernanceService::class)->setMaintenance(false);

        parent::tearDown();
    }

    // =========================================================================
    // SECTION 1: RBAC AUTHORIZATION GUARDRAILS
    // =========================================================================

    public function test_unauthenticated_requests_to_governance_apis_are_rejected_with_401(): void
    {
        $endpoints = [
            ['GET', '/admin/api/governance/state'],
            ['GET', '/admin/api/governance/thematic-intervals'],
            ['GET', '/admin/api/governance/backups'],
            ['GET', '/admin/api/governance/sessions'],
            ['GET', '/admin/api/governance/audit-logs'],
            ['GET', '/admin/api/governance/integrations'],
            ['POST', '/admin/api/governance/cache/clear'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);

            $response->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'error_code' => 'UNAUTHENTICATED',
                ]);
        }
    }

    public function test_non_superadmin_users_receive_403_forbidden_on_governance_endpoints(): void
    {
        $nonSuperAdmins = [
            'admin_dinas' => $this->adminDinas,
            'admin' => $this->admin,
            'stakeholder' => $this->stakeholder,
        ];

        $probedRoutes = [
            ['GET', '/admin/api/governance/state'],
            ['GET', '/admin/api/governance/thematic-intervals'],
            ['PUT', '/admin/api/governance/thematic-intervals', ['kategori' => 'populasi', 'tier' => 1, 'label' => 'Tes', 'min_value' => 0, 'color_hex' => '#fff']],
            ['POST', '/admin/api/governance/thematic-intervals/recalculate', ['kategori' => 'populasi']],
            ['PUT', '/admin/api/governance/maintenance', ['enabled' => true]],
            ['POST', '/admin/api/governance/cache/clear', ['target' => 'all']],
            ['POST', '/admin/api/governance/backups', ['notes' => 'Unauthorized backup']],
            ['GET', '/admin/api/governance/sessions'],
            ['POST', '/admin/api/governance/ip-rules', ['ip_address' => '1.2.3.4', 'rule_type' => 'whitelist']],
            ['GET', '/admin/api/governance/audit-logs'],
            ['GET', '/admin/api/governance/integrations'],
        ];

        foreach ($nonSuperAdmins as $roleName => $user) {
            foreach ($probedRoutes as $route) {
                $method = $route[0];
                $uri = $route[1];
                $payload = $route[2] ?? [];

                $response = $this->actingAs($user)->json($method, $uri, $payload);

                $this->assertSame(
                    403,
                    $response->getStatusCode(),
                    "Role '{$roleName}' must receive HTTP 403 on {$method} {$uri}, got {$response->getStatusCode()}"
                );

                $response->assertJson([
                    'status' => 'error',
                    'error_code' => 'RBAC_FORBIDDEN',
                ]);
            }
        }
    }

    public function test_superadmin_has_full_authorization_to_governance_endpoints(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/state');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
    }

    // =========================================================================
    // SECTION 2: SUBMODULE 1 - BUSINESS POLICY & THEMATIC INTERVALS
    // =========================================================================

    public function test_submodule1_get_thematic_intervals_returns_structured_payload(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/thematic-intervals');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'kategori',
                        'tier',
                        'label',
                        'min_value',
                        'color_hex',
                    ],
                ],
            ]);
    }

    public function test_submodule1_update_thematic_intervals_batch_mode_persists_data(): void
    {
        $batchPayload = [
            'intervals' => [
                [
                    'kategori' => 'populasi',
                    'tier' => 1,
                    'label' => 'Tier 1 Test Rendah',
                    'min_value' => 0,
                    'max_value' => 250,
                    'color_hex' => '#10B981',
                    'satuan' => 'ekor',
                    'calculation_mode' => 'manual',
                    'is_active' => true,
                ],
                [
                    'kategori' => 'populasi',
                    'tier' => 2,
                    'label' => 'Tier 2 Test Tinggi',
                    'min_value' => 251,
                    'max_value' => null,
                    'color_hex' => '#EF4444',
                    'satuan' => 'ekor',
                    'calculation_mode' => 'manual',
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/thematic-intervals', $batchPayload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Interval tematik berhasil diperbarui.',
            ]);

        $this->assertDatabaseHas('thematic_intervals', [
            'label' => 'Tier 1 Test Rendah',
            'tier' => 1,
            'min_value' => 0,
            'max_value' => 250,
        ]);
        $this->assertDatabaseHas('thematic_intervals', [
            'label' => 'Tier 2 Test Tinggi',
            'tier' => 2,
            'min_value' => 251,
        ]);
    }

    public function test_submodule1_update_thematic_intervals_single_mode_persists_data(): void
    {
        $singlePayload = [
            'kategori' => 'produksi',
            'tier' => 1,
            'label' => 'Produksi Susu Minimal',
            'min_value' => 50,
            'max_value' => 500,
            'color_hex' => '#3B82F6',
            'satuan' => 'liter',
            'calculation_mode' => 'manual',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/thematic-intervals', $singlePayload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('thematic_intervals', [
            'kategori' => 'produksi',
            'label' => 'Produksi Susu Minimal',
            'min_value' => 50,
        ]);
    }

    public function test_submodule1_recalculate_thematic_intervals_quantile_preview_and_apply(): void
    {
        // 1. Preview mode (apply = false)
        $previewResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/thematic-intervals/recalculate', [
            'kategori' => 'populasi',
            'method' => 'quantile',
            'classes' => 4,
            'apply' => false,
        ]);

        $previewResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'kategori' => 'populasi',
                    'method' => 'quantile',
                    'tiers_count' => 4,
                    'applied' => false,
                ],
            ]);

        // 2. Apply mode (apply = true)
        $applyResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/thematic-intervals/recalculate', [
            'kategori' => 'populasi',
            'method' => 'quantile',
            'classes' => 4,
            'apply' => true,
        ]);

        $applyResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'applied' => true,
                ],
            ]);

        $savedCount = ThematicInterval::where('kategori', 'populasi')->count();
        $this->assertSame(4, $savedCount, 'Recalculate with apply=true must persist exactly 4 tiers in database');
    }

    public function test_submodule1_recalculate_thematic_intervals_jenks_breaks_mode(): void
    {
        $response = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/thematic-intervals/recalculate', [
            'kategori' => 'populasi',
            'method' => 'jenks',
            'classes' => 3,
            'apply' => false,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'kategori' => 'populasi',
                    'method' => 'jenks',
                    'tiers_count' => 3,
                ],
            ]);

        $this->assertCount(4, $response->json('data.breaks'));
    }

    public function test_submodule1_update_conversion_factors_updates_settings_and_caches(): void
    {
        $payload = [
            'conversion_st_sapi' => 1.25,
            'conversion_st_kambing' => 0.16,
            'conversion_st_unggas' => 0.015,
            'hmt_multiplier' => 12.0,
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/conversion-factors', $payload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Faktor konversi kebijakan ternak & daya dukung berhasil diperbarui.',
                'data' => [
                    'conversion_st_sapi' => 1.25,
                    'conversion_st_kambing' => 0.16,
                    'conversion_st_unggas' => 0.015,
                    'hmt_multiplier' => 12.0,
                ],
            ]);

        $service = app(SystemGovernanceService::class);
        $this->assertSame(1.25, (float) $service->get('policy.conversion_st_sapi'));
        $this->assertSame(0.16, (float) $service->get('policy.conversion_st_kambing'));
        $this->assertSame(12.0, (float) $service->get('policy.hmt_multiplier'));
    }

    // =========================================================================
    // SECTION 3: SUBMODULE 2 - SYSTEM STATE & AVAILABILITY
    // =========================================================================

    public function test_submodule2_get_system_state_retrieves_indicators(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/state');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'maintenance' => ['active', 'enabled', 'message', 'scheduled_end'],
                    'emergency_banner' => ['enabled', 'message', 'level'],
                    'features',
                    'server_time',
                    'environment',
                ],
            ]);
    }

    public function test_submodule2_maintenance_mode_activation_and_blocking_behavior(): void
    {
        // 1. Super Admin enables maintenance mode
        $enableResponse = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/maintenance', [
            'enabled' => true,
            'message' => 'Pemeliharaan terencana sistem TernakVerse.',
            'scheduled_end' => '2026-12-31 23:59:59',
        ]);

        $enableResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'active' => true,
                    'message' => 'Pemeliharaan terencana sistem TernakVerse.',
                ],
            ]);

        $service = app(SystemGovernanceService::class);
        $this->assertTrue($service->isMaintenanceActive(), 'Maintenance mode must be active in system service');

        // 2. Operational staff hitting an internal portal during maintenance is blocked with 503
        $blockedResponse = $this->actingAs($this->adminDinas)->getJson('/internal/dinas');
        $blockedResponse->assertStatus(503)
            ->assertJson([
                'status' => 'maintenance',
                'error_code' => 'SYSTEM_MAINTENANCE',
            ]);

        // 3. Super Admin bypasses maintenance mode on state endpoint
        $superadminResponse = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/state');
        $superadminResponse->assertOk();

        // 4. Deactivate maintenance mode
        $disableResponse = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/maintenance', [
            'enabled' => false,
        ]);

        $disableResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'active' => false,
                ],
            ]);

        $this->assertFalse($service->isMaintenanceActive(), 'Maintenance mode must be inactive after disabling');
    }

    public function test_submodule2_emergency_banner_configuration(): void
    {
        $payload = [
            'enabled' => true,
            'message' => 'Waspada Wabah PMK di Banyumas Barat.',
            'level' => 'alert',
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/emergency-banner', $payload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'enabled' => true,
                    'message' => 'Waspada Wabah PMK di Banyumas Barat.',
                    'level' => 'alert',
                ],
            ]);

        $service = app(SystemGovernanceService::class);
        $this->assertTrue((bool) $service->get('banner.enabled'));
        $this->assertSame('Waspada Wabah PMK di Banyumas Barat.', $service->get('banner.message'));
        $this->assertSame('alert', $service->get('banner.level'));
    }

    public function test_submodule2_update_feature_flags(): void
    {
        // Batch feature flags update
        $batchPayload = [
            'features' => [
                'ai_chatbot' => false,
                'interactive_gis' => true,
                'spatial_analysis' => false,
            ],
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/features', $batchPayload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'ai_chatbot' => false,
                    'interactive_gis' => true,
                    'spatial_analysis' => false,
                ],
            ]);

        // Single feature flag update
        $singlePayload = [
            'feature' => 'ai_chatbot',
            'enabled' => true,
        ];

        $singleResponse = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/features', $singlePayload);

        $singleResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'ai_chatbot' => true,
                ],
            ]);
    }

    public function test_submodule2_granular_cache_clearing(): void
    {
        $targets = ['gis', 'query', 'all'];

        foreach ($targets as $target) {
            $response = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/cache/clear', [
                'target' => $target,
            ]);

            $response->assertOk()
                ->assertJson([
                    'status' => 'success',
                    'message' => "Cache [{$target}] berhasil dibersihkan.",
                ]);
        }
    }

    // =========================================================================
    // SECTION 4: SUBMODULE 3 - DATA RESILIENCE & BACKUP
    // =========================================================================

    public function test_submodule3_backups_listing_returns_collection(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/backups');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_submodule3_real_backup_creation_download_and_deletion(): void
    {
        // 1. Create a real database backup
        $createResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/backups', [
            'notes' => 'Automated test suite snapshot',
        ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Snapshot cadangan database berhasil dibuat.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'disk_path',
                    'size_bytes',
                    'checksum',
                    'status',
                ],
            ]);

        $backupData = $createResponse->json('data');
        $backupId = (int) $backupData['id'];
        $diskPath = (string) $backupData['disk_path'];

        $this->createdBackupFiles[] = $diskPath;

        $this->assertFileExists($diskPath, 'Backup file must exist on physical disk');
        $this->assertGreaterThan(0, filesize($diskPath), 'Backup file size must be greater than zero');
        $this->assertSame(
            hash_file('sha256', $diskPath),
            $backupData['checksum'],
            'Backup checksum must strictly match sha256 of physical file'
        );

        // 2. Download the created backup file
        $downloadResponse = $this->actingAs($this->superadmin)->get("/admin/api/governance/backups/{$backupId}/download");
        $downloadResponse->assertOk();

        // 3. Restore verification: attempt restore with invalid code (must reject with 422)
        $invalidRestoreResponse = $this->actingAs($this->superadmin)->postJson("/admin/api/governance/backups/{$backupId}/restore", [
            'confirm_code' => 'WRONG_CODE',
        ]);
        $invalidRestoreResponse->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Kode konfirmasi tidak valid. Harap masukkan "RESTORE_DATABASE" untuk mengonfirmasi pemulihan.',
            ]);

        // 4. Delete the created backup file
        $deleteResponse = $this->actingAs($this->superadmin)->deleteJson("/admin/api/governance/backups/{$backupId}");
        $deleteResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => "Cadangan #{$backupId} berhasil dihapus.",
            ]);

        $this->assertFileDoesNotExist($diskPath, 'Backup file must be deleted from disk upon deletion');
        $this->assertDatabaseMissing('system_backups', [
            'id' => $backupId,
        ]);
    }

    public function test_submodule3_download_nonexistent_backup_returns_404(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/backups/999999/download');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_submodule3_queue_status_reports_driver_and_metrics(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/queue-status');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'driver',
                    'pending_jobs',
                    'failed_jobs',
                    'status',
                ],
            ]);
    }

    // =========================================================================
    // SECTION 5: SUBMODULE 4 - SECURITY & ACCESS GOVERNANCE
    // =========================================================================

    public function test_submodule4_sessions_listing_and_revocation(): void
    {
        $dummySessionId = 'sess_test_'.bin2hex(random_bytes(8));

        DB::table('sessions')->insert([
            'id' => $dummySessionId,
            'user_id' => $this->superadmin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test Browser',
            'payload' => serialize(['test' => true]),
            'last_activity' => now()->timestamp,
        ]);

        // 1. List active sessions
        $listResponse = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/sessions');

        $listResponse->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $sessions = $listResponse->json('data');
        $this->assertIsArray($sessions);
        $found = collect($sessions)->firstWhere('id', $dummySessionId);
        $this->assertNotNull($found, 'Dummy session must be present in active sessions list');

        // 2. Revoke the specific session
        $revokeResponse = $this->actingAs($this->superadmin)->deleteJson("/admin/api/governance/sessions/{$dummySessionId}");

        $revokeResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Sesi pengguna berhasil dihentikan.',
            ]);

        $this->assertDatabaseMissing('sessions', [
            'id' => $dummySessionId,
        ]);

        // 3. Terminate all other sessions
        $terminateOthersResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/sessions/terminate-others');

        $terminateOthersResponse->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_submodule4_ip_access_rules_crud_lifecycle(): void
    {
        // 1. List IP rules
        $listResponse = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/ip-rules');
        $listResponse->assertOk()
            ->assertJson(['status' => 'success']);

        // 2. Store new IP rule
        $createResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/ip-rules', [
            'ip_address' => '192.168.100.0/24',
            'rule_type' => 'whitelist',
            'description' => 'Subnet Kantor Diskanak',
            'is_active' => true,
        ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Aturan akses IP berhasil disimpan.',
            ]);

        $ruleId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('ip_access_rules', [
            'id' => $ruleId,
            'ip_address' => '192.168.100.0/24',
            'rule_type' => 'whitelist',
        ]);

        // 3. Delete the IP rule
        $deleteResponse = $this->actingAs($this->superadmin)->deleteJson("/admin/api/governance/ip-rules/{$ruleId}");

        $deleteResponse->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Aturan akses IP berhasil dihapus.',
            ]);

        $this->assertDatabaseMissing('ip_access_rules', [
            'id' => $ruleId,
        ]);
    }

    public function test_submodule4_update_security_policies(): void
    {
        $payload = [
            'max_login_attempts' => 5,
            'session_lifetime_minutes' => 45,
            'two_factor_required' => true,
            'password_min_length' => 12,
            'ip_whitelist_enforced' => false,
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/security-policies', $payload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Kebijakan keamanan sistem berhasil diperbarui.',
                'data' => [
                    'max_login_attempts' => 5,
                    'session_lifetime_minutes' => 45,
                    'two_factor_required' => true,
                    'password_min_length' => 12,
                ],
            ]);

        $service = app(SystemGovernanceService::class);
        $this->assertSame(5, (int) $service->get('security.max_login_attempts'));
        $this->assertSame(45, (int) $service->get('security.session_lifetime_minutes'));
        $this->assertTrue((bool) $service->get('security.two_factor_required'));
    }

    // =========================================================================
    // SECTION 6: SUBMODULE 5 - OBSERVABILITY & AUDITING
    // =========================================================================

    public function test_submodule5_audit_logs_pagination_and_search(): void
    {
        // Seed an audit log entry
        AuditLog::catat(
            'TEST_SUITE_AUDIT_ACTION',
            'system_governance',
            $this->superadmin->id,
            null,
            ['env' => 'testing']
        );

        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/audit-logs?per_page=10&action=TEST_SUITE_AUDIT_ACTION');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $this->assertSame('TEST_SUITE_AUDIT_ACTION', $items[0]['action']);
    }

    public function test_submodule5_system_logs_tail(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/system-logs?limit=30');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data',
                'count',
            ]);
    }

    public function test_submodule5_system_health_diagnostics_report(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'status',
                    'database' => [
                        'status',
                        'latency_ms',
                        'driver',
                    ],
                    'cache' => [
                        'status',
                        'driver',
                    ],
                    'queue' => [
                        'failed_jobs',
                        'pending_jobs',
                    ],
                    'storage' => [
                        'free_bytes',
                        'total_bytes',
                    ],
                    'server' => [
                        'php_version',
                        'laravel_version',
                        'server_time',
                    ],
                ],
            ]);

        $this->assertSame('ok', $response->json('data.database.status'));
        $this->assertSame('ok', $response->json('data.cache.status'));
    }

    // =========================================================================
    // SECTION 7: SUBMODULE 6 - INTEGRATIONS & GATEWAY SETTINGS
    // =========================================================================

    public function test_submodule6_integrations_listing_masks_sensitive_credentials(): void
    {
        $service = app(SystemGovernanceService::class);
        $service->set('integration.whatsapp_key', 'super-secret-whatsapp-token', 'encrypted');
        $service->set('integration.smtp_password', 'secret-smtp-password', 'encrypted');

        $response = $this->actingAs($this->superadmin)->getJson('/admin/api/governance/integrations');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'whatsapp' => ['enabled', 'endpoint_url', 'api_key', 'sender_number'],
                    'smtp' => ['host', 'port', 'username', 'password', 'encryption', 'from_address'],
                    'geoportal' => ['endpoint_url', 'api_token', 'layer_id'],
                    'bps' => ['api_url', 'api_key'],
                ],
            ]);

        $waKey = $response->json('data.whatsapp.api_key');
        $smtpPass = $response->json('data.smtp.password');

        $this->assertSame('••••••••', $waKey, 'Sensitive WhatsApp API key must be masked');
        $this->assertSame('••••••••', $smtpPass, 'Sensitive SMTP password must be masked');
    }

    public function test_submodule6_update_integrations_encrypts_credentials(): void
    {
        $payload = [
            'whatsapp' => [
                'enabled' => true,
                'endpoint_url' => 'https://api.fonnte.com/send',
                'api_key' => 'new-fonnte-secret-key-12345',
                'sender_number' => '081299998888',
            ],
            'smtp' => [
                'host' => 'smtp.mailtrap.io',
                'port' => 2525,
                'username' => 'diskanak_mail',
                'password' => 'secret_smtp_pass_999',
                'encryption' => 'tls',
                'from_address' => 'noreply@banyumaskab.go.id',
            ],
            'geoportal' => [
                'endpoint_url' => 'https://geoportal.banyumaskab.go.id/geoserver/wfs',
                'api_token' => 'geoportal-jwt-token-987',
                'layer_id' => 'banyumas:batas_desa',
            ],
            'bps' => [
                'api_url' => 'https://webapi.bps.go.id/v1/api/data',
                'api_key' => 'bps-partner-key-3302',
            ],
        ];

        $response = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/integrations', $payload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengaturan integrasi gateway berhasil diperbarui.',
            ]);

        $service = app(SystemGovernanceService::class);
        $this->assertSame('new-fonnte-secret-key-12345', $service->get('integration.whatsapp_key'));
        $this->assertSame('secret_smtp_pass_999', $service->get('integration.smtp_password'));

        // Verify that raw database value is encrypted and not stored in plaintext
        $rawRecord = DB::table('system_settings')->where('key', 'integration.whatsapp_key')->first();
        $this->assertNotNull($rawRecord);
        $this->assertNotEquals('new-fonnte-secret-key-12345', $rawRecord->value, 'Raw database setting must be encrypted');
    }

    public function test_submodule6_test_integration_connection_probes(): void
    {
        $gateways = ['whatsapp', 'smtp', 'geoportal', 'bps'];

        foreach ($gateways as $gateway) {
            $response = $this->actingAs($this->superadmin)->postJson("/admin/api/governance/integrations/{$gateway}/test");

            $response->assertOk()
                ->assertJson([
                    'status' => 'success',
                    'gateway' => $gateway,
                    'connected' => true,
                ])
                ->assertJsonStructure([
                    'status',
                    'gateway',
                    'connected',
                    'latency_ms',
                    'message',
                ]);
        }

        // Test unsupported gateway probe returns 400 Bad Request
        $invalidResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/integrations/unsupported_gateway/test');
        $invalidResponse->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gateway [unsupported_gateway] tidak didukung untuk pengujian koneksi.',
            ]);
    }

    // =========================================================================
    // SECTION 8: VALIDATION, BOUNDARIES, AND ERROR HANDLING
    // =========================================================================

    public function test_validation_rejects_malformed_governance_inputs(): void
    {
        // 1. Invalid IP rule type
        $invalidIpResponse = $this->actingAs($this->superadmin)->postJson('/admin/api/governance/ip-rules', [
            'ip_address' => '10.0.0.1',
            'rule_type' => 'malicious_type',
        ]);
        $invalidIpResponse->assertStatus(422)
            ->assertJsonValidationErrors(['rule_type']);

        // 2. Invalid Emergency Banner level
        $invalidBannerResponse = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/emergency-banner', [
            'enabled' => true,
            'message' => 'Peringatan',
            'level' => 'unknown_level',
        ]);
        $invalidBannerResponse->assertStatus(422)
            ->assertJsonValidationErrors(['level']);

        // 3. Invalid Conversion factor (negative or zero)
        $invalidConversionResponse = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/conversion-factors', [
            'conversion_st_sapi' => -5.0,
        ]);
        $invalidConversionResponse->assertStatus(422)
            ->assertJsonValidationErrors(['conversion_st_sapi']);

        // 4. Invalid batch thematic interval tier out of range
        $invalidTierResponse = $this->actingAs($this->superadmin)->putJson('/admin/api/governance/thematic-intervals', [
            'intervals' => [
                [
                    'kategori' => 'populasi',
                    'tier' => 25, // between 1, 10
                    'label' => 'Invalid Tier',
                    'min_value' => 0,
                    'color_hex' => '#000',
                ],
            ],
        ]);
        $invalidTierResponse->assertStatus(422)
            ->assertJsonValidationErrors(['intervals.0.tier']);
    }
}
