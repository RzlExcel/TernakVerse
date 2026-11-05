<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FasilitasRph;
use App\Models\KategoriProduksi;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\Penyakit;
use App\Models\User;
use App\Services\CarryingCapacityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class OverhaulConsolidationVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Compatibility fallback for environments with PostgreSQL where PostGIS extension is not installed
        if (DB::getDriverName() === 'pgsql') {
            $hasPostgis = ! empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));
            if (! $hasPostgis) {
                DB::statement("
                    CREATE OR REPLACE FUNCTION ST_MakePoint(x numeric, y numeric) RETURNS jsonb AS \$\$
                    BEGIN
                        RETURN json_build_object('type', 'Point', 'coordinates', json_build_array(x, y));
                    END;
                    \$\$ LANGUAGE plpgsql;
                ");
                DB::statement("
                    CREATE OR REPLACE FUNCTION ST_MakePoint(x double precision, y double precision) RETURNS jsonb AS \$\$
                    BEGIN
                        RETURN json_build_object('type', 'Point', 'coordinates', json_build_array(x, y));
                    END;
                    \$\$ LANGUAGE plpgsql;
                ");
                DB::statement('
                    CREATE OR REPLACE FUNCTION ST_SetSRID(geom jsonb, srid integer) RETURNS jsonb AS $$
                    BEGIN
                        RETURN geom;
                    END;
                    $$ LANGUAGE plpgsql;
                ');
            }
        }

        // Ensure database has baseline seeded data if running on clean environment
        if (Kecamatan::count() < 27 || FasilitasRph::count() < 10) {
            $this->artisan('db:seed');
        }
    }

    /**
     * 1. RBAC & Role Isolation: User model helpers
     */
    public function test_user_model_role_helpers_identify_admin_and_superadmin_accurately(): void
    {
        $superadmin = new User(['role' => 'superadmin', 'email' => 'sa@banyumaskab.go.id']);
        $adminDinas = new User(['role' => 'admin_dinas', 'email' => 'ad@banyumaskab.go.id']);
        $admin = new User(['role' => 'admin', 'email' => 'admin@banyumaskab.go.id']);
        $stakeholder = new User(['role' => 'stakeholder', 'email' => 'stakeholder@banyumaskab.go.id']);
        $guest = new User(['role' => 'guest', 'email' => 'guest@example.com']);

        // isAdmin() checks
        $this->assertTrue($superadmin->isAdmin(), 'superadmin role must be recognized as admin');
        $this->assertTrue($adminDinas->isAdmin(), 'admin_dinas role must be recognized as admin');
        $this->assertTrue($admin->isAdmin(), 'admin role must be recognized as admin');
        $this->assertFalse($stakeholder->isAdmin(), 'stakeholder role must NOT be recognized as admin');
        $this->assertFalse($guest->isAdmin(), 'guest role must NOT be recognized as admin');

        // isSuperAdmin() checks
        $this->assertTrue($superadmin->isSuperAdmin(), 'superadmin role must be recognized as superadmin');
        $this->assertFalse($adminDinas->isSuperAdmin(), 'admin_dinas role must NOT be recognized as superadmin');
        $this->assertFalse($admin->isSuperAdmin(), 'admin role must NOT be recognized as superadmin');
        $this->assertFalse($stakeholder->isSuperAdmin(), 'stakeholder role must NOT be recognized as superadmin');
    }

    /**
     * 1. RBAC & Role Isolation: EnsureUserRole middleware enforcement
     */
    public function test_ensure_user_role_middleware_allows_admin_roles_and_blocks_unauthorized_roles(): void
    {
        $superadmin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $adminDinas = User::where('role', 'admin_dinas')->first()
            ?? new User(['id' => 2, 'name' => 'Admin Dinas', 'email' => 'admin.diskanak@banyumaskab.go.id', 'role' => 'admin_dinas', 'aktif' => true]);

        $admin = new User(['id' => 3, 'name' => 'Administrator', 'email' => 'admin@banyumaskab.go.id', 'role' => 'admin', 'aktif' => true]);

        $stakeholder = User::where('role', 'stakeholder')->first()
            ?? new User(['id' => 4, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);

        // Superadmin is allowed on admin panel
        $this->actingAs($superadmin)
            ->get('/admin/panel')
            ->assertOk();

        // Admin Dinas is allowed on internal admin portal
        $this->actingAs($adminDinas)
            ->get('/internal/admin')
            ->assertOk();

        // Admin is allowed on admin panel
        $this->actingAs($admin)
            ->get('/admin/panel')
            ->assertOk();

        // Stakeholder is blocked from admin routes (HTTP 403)
        $this->actingAs($stakeholder)
            ->get('/admin/panel')
            ->assertStatus(403);

        $this->actingAs($stakeholder)
            ->get('/internal/admin')
            ->assertStatus(403);
    }

    /**
     * 1. RBAC & Role Isolation: Anonymous requests are rejected with HTTP 401
     */
    public function test_anonymous_requests_to_admin_master_data_are_rejected_with_401(): void
    {
        $this->getJson('/admin/api/master-data')
            ->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'UNAUTHENTICATED',
            ]);
    }

    /**
     * 2. Database Schema & New Entities: Columns in fasilitas_rph and kecamatan
     */
    public function test_database_schema_contains_overhaul_entity_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('fasilitas_rph', [
                'tipe_fasilitas',
                'hari_pasaran',
                'wilayah_binaan',
                'penanggung_jawab',
            ]),
            'fasilitas_rph table must contain tipe_fasilitas, hari_pasaran, wilayah_binaan, and penanggung_jawab columns'
        );

        $this->assertTrue(
            Schema::hasColumn('kecamatan', 'luas_hmt_ha'),
            'kecamatan table must contain luas_hmt_ha column'
        );
    }

    /**
     * 2. Database Schema & New Entities: Commodity KAMBING_PERAH exists under 'Ternak Kecil'
     */
    public function test_komoditas_table_contains_kambing_perah_entity(): void
    {
        $kambingPerah = Komoditas::where('kode', 'KAMBING_PERAH')->first();

        $this->assertNotNull($kambingPerah, 'Komoditas with kode KAMBING_PERAH must exist in database');
        $this->assertSame('Ternak Kecil', $kambingPerah->kelompok, 'KAMBING_PERAH must belong to kelompok "Ternak Kecil"');
        $this->assertSame('Kambing Perah', $kambingPerah->nama, 'KAMBING_PERAH display name must be "Kambing Perah"');
        $this->assertTrue((bool) $kambingPerah->aktif, 'KAMBING_PERAH must be active');
    }

    /**
     * 3. Facilities & Seeder Data: 11 official facilities (6 RPH, 2 Puskeswan, 3 Pasar Hewan)
     */
    public function test_seeded_facilities_contain_11_official_facilities_with_correct_distribution(): void
    {
        $allFacilities = FasilitasRph::all();
        $this->assertCount(11, $allFacilities, 'There must be exactly 11 official facilities in the database');

        $rphFacilities = FasilitasRph::rph()->get();
        $this->assertCount(6, $rphFacilities, 'There must be exactly 6 RPH facilities');

        $puskeswanFacilities = FasilitasRph::puskeswan()->get();
        $this->assertCount(2, $puskeswanFacilities, 'There must be exactly 2 Puskeswan facilities');

        $puskeswanNames = $puskeswanFacilities->pluck('nama_rph')->implode(', ');
        $this->assertStringContainsString('Banyumas', $puskeswanNames, 'Puskeswan Banyumas must exist');
        $this->assertStringContainsString('Cilongok', $puskeswanNames, 'Puskeswan Cilongok must exist');

        foreach ($puskeswanFacilities as $pkw) {
            $this->assertNotEmpty($pkw->wilayah_binaan, "Puskeswan {$pkw->nama_rph} must have non-empty wilayah_binaan");
            $this->assertNotEmpty($pkw->penanggung_jawab, "Puskeswan {$pkw->nama_rph} must have non-empty penanggung_jawab");
        }

        $pasarHewanFacilities = FasilitasRph::pasarHewan()->get();
        $this->assertCount(3, $pasarHewanFacilities, 'There must be exactly 3 Pasar Hewan facilities');

        $pasarNames = $pasarHewanFacilities->pluck('nama_rph')->implode(', ');
        $this->assertStringContainsString('Ajibarang', $pasarNames, 'Pasar Hewan Ajibarang must exist');
        $this->assertStringContainsString('Sokaraja', $pasarNames, 'Pasar Hewan Sokaraja must exist');
        $this->assertStringContainsString('Cilongok', $pasarNames, 'Pasar Hewan Cilongok must exist');

        foreach ($pasarHewanFacilities as $ph) {
            $this->assertNotEmpty($ph->hari_pasaran, "Pasar Hewan {$ph->nama_rph} must have non-empty hari_pasaran");
        }

        // Validate valid geographic coordinates for all 11 official facilities via Master Data API
        $superadmin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $masterResponse = $this->actingAs($superadmin)->getJson('/admin/api/master-data');
        $masterResponse->assertOk();
        $apiFacilities = $masterResponse->json('fasilitas');
        $this->assertCount(11, $apiFacilities, 'Master data API must list exactly 11 facilities');

        foreach ($apiFacilities as $fac) {
            $this->assertNotNull($fac['lat'], "Facility {$fac['nama_rph']} must have latitude");
            $this->assertNotNull($fac['lng'], "Facility {$fac['nama_rph']} must have longitude");
            $this->assertLessThan(-7.0, (float) $fac['lat'], "Facility {$fac['nama_rph']} latitude must be < -7.0");
            $this->assertGreaterThan(-8.0, (float) $fac['lat'], "Facility {$fac['nama_rph']} latitude must be > -8.0");
            $this->assertGreaterThan(108.5, (float) $fac['lng'], "Facility {$fac['nama_rph']} longitude must be > 108.5");
            $this->assertLessThan(110.0, (float) $fac['lng'], "Facility {$fac['nama_rph']} longitude must be < 110.0");
        }

        $cilongokPh = collect($apiFacilities)->firstWhere('nama_rph', 'Pasar Hewan Cilongok');
        $this->assertNotNull($cilongokPh, 'Pasar Hewan Cilongok must be present in official facilities');
        $this->assertSame('Pasar Hewan', $cilongokPh['tipe_fasilitas']);
        $this->assertEqualsWithDelta(-7.4089, (float) $cilongokPh['lat'], 0.05);
        $this->assertEqualsWithDelta(109.1362, (float) $cilongokPh['lng'], 0.05);
    }

    /**
     * 3. Facilities & Seeder Data: All 27 kecamatan have luas_hmt_ha > 0
     */
    public function test_all_27_kecamatan_have_positive_forage_area_hmt(): void
    {
        $kecamatans = Kecamatan::all();
        $this->assertCount(27, $kecamatans, 'There must be exactly 27 kecamatan in Banyumas');

        $positiveHmtCount = Kecamatan::where('luas_hmt_ha', '>', 0)->count();
        $this->assertSame(27, $positiveHmtCount, 'All 27 kecamatan must have luas_hmt_ha > 0');

        $totalHmt = (float) Kecamatan::sum('luas_hmt_ha');
        $this->assertGreaterThan(0, $totalHmt, 'Total HMT area across Banyumas must be greater than 0');
    }

    /**
     * 4. Carrying Capacity Engine: Conversion factors (ST)
     */
    public function test_carrying_capacity_engine_conversion_factors(): void
    {
        $service = new CarryingCapacityService;

        // Sapi Potong / Perah = 1.0 ST
        $this->assertSame(1.0, $service->getConversionFactor('SAPI_POTONG'));
        $this->assertSame(1.0, $service->getConversionFactor('SAPI_PERAH'));
        $this->assertSame(1.0, $service->getConversionFactor('SAPI'));

        // Kambing / Domba / Kambing Perah = 0.14 ST
        $this->assertSame(0.14, $service->getConversionFactor('KAMBING'));
        $this->assertSame(0.14, $service->getConversionFactor('DOMBA'));
        $this->assertSame(0.14, $service->getConversionFactor('KAMBING_PERAH'));

        // Unggas / Ayam = 0.01 ST
        $this->assertSame(0.01, $service->getConversionFactor('AYAM_POTONG'));
        $this->assertSame(0.01, $service->getConversionFactor('AYAM_TELUR'));
        $this->assertSame(0.01, $service->getConversionFactor('AYAM_KAMPUNG'));
        $this->assertSame(0.01, $service->getConversionFactor('ITIK'));
    }

    /**
     * 4. Carrying Capacity Engine: Status computation (SURPLUS, WASPADA, DEFISIT)
     */
    public function test_carrying_capacity_engine_daya_dukung_status_and_aggregations(): void
    {
        $service = new CarryingCapacityService;

        $kabupatenResult = $service->calculateKabupaten(2026, 3);

        $this->assertArrayHasKey('total_st', $kabupatenResult);
        $this->assertArrayHasKey('luas_hmt_ha', $kabupatenResult);
        $this->assertArrayHasKey('kapasitas_tampung_st', $kabupatenResult);
        $this->assertArrayHasKey('indeks', $kabupatenResult);
        $this->assertArrayHasKey('status', $kabupatenResult);
        $this->assertArrayHasKey('status_label', $kabupatenResult);
        $this->assertArrayHasKey('by_kecamatan', $kabupatenResult);

        $this->assertContains($kabupatenResult['status'], ['SURPLUS', 'WASPADA', 'DEFISIT']);
        $this->assertCount(27, $kabupatenResult['by_kecamatan'], 'by_kecamatan must contain metrics for all 27 kecamatan');

        // Test single kecamatan computation
        $firstKecamatan = Kecamatan::firstOrFail();
        $kecamatanResult = $service->calculateKecamatan((int) $firstKecamatan->id, 2026, 3);

        $this->assertSame((int) $firstKecamatan->id, $kecamatanResult['kecamatan_id']);
        $this->assertSame($firstKecamatan->nama_kecamatan, $kecamatanResult['nama_kecamatan']);
        $this->assertContains($kecamatanResult['status'], ['SURPLUS', 'WASPADA', 'DEFISIT']);

        // Verify Kementan standard: Kapasitas Tampung = luas_hmt_ha * 6.0
        $expectedCapacity = round((float) $kecamatanResult['luas_hmt_ha'] * 6.0, 2);
        $this->assertEqualsWithDelta($expectedCapacity, $kecamatanResult['kapasitas_tampung_st'], 0.05);
    }

    /**
     * 5. Live Dinas Dashboard Controller: GET /dinas/dashboard
     */
    public function test_dinas_dashboard_controller_returns_200_with_carrying_capacity_and_kpi_cards(): void
    {
        $stakeholder = User::where('role', 'stakeholder')->first()
            ?? new User(['id' => 2, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);

        $this->actingAs($stakeholder)
            ->get('/dinas/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dinas/Dashboard', false)
                ->has('filterState')
                ->has('kpiCards', fn (Assert $kpi) => $kpi
                    ->has('total_populasi_ternak')
                    ->has('total_produksi_daging_kg')
                    ->has('total_produksi_susu_liter')
                    ->has('total_produksi_telur_butir')
                    ->has('total_kasus_aktif')
                    ->has('total_rph_aktif')
                    ->has('carrying_capacity', fn (Assert $cc) => $cc
                        ->has('total_st')
                        ->has('luas_hmt_ha')
                        ->has('kapasitas_tampung_st')
                        ->has('indeks')
                        ->has('status')
                        ->etc()
                    )
                    ->etc()
                )
                ->has('chartData')
                ->has('geoChoropleth')
            );
    }

    /**
     * 5. Live Dinas Dashboard Controller: Dynamic multi-period query (2024 vs 2026)
     */
    public function test_dinas_dashboard_returns_dynamic_multi_period_data_for_2024_vs_2026(): void
    {
        $stakeholder = User::where('role', 'stakeholder')->first()
            ?? new User(['id' => 2, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);

        // Query Dinas Dashboard for tahun 2024
        $populasi2024 = null;
        $response2024 = $this->actingAs($stakeholder)
            ->get('/dinas/dashboard?tahun=2024');

        $response2024->assertOk()
            ->assertInertia(function (Assert $page) use (&$populasi2024) {
                $page->component('Dinas/Dashboard', false)
                    ->where('filterState.tahun', 2024)
                    ->has('kpiCards.total_populasi_ternak')
                    ->has('kpiCards.total_kasus_aktif')
                    ->has('chartData');
                $populasi2024 = $page->toArray()['props']['kpiCards']['total_populasi_ternak'];
            });

        // Query Dinas Dashboard for tahun 2026
        $populasi2026 = null;
        $response2026 = $this->actingAs($stakeholder)
            ->get('/dinas/dashboard?tahun=2026');

        $response2026->assertOk()
            ->assertInertia(function (Assert $page) use (&$populasi2026) {
                $page->component('Dinas/Dashboard', false)
                    ->where('filterState.tahun', 2026)
                    ->has('kpiCards.total_populasi_ternak')
                    ->has('kpiCards.total_kasus_aktif')
                    ->has('chartData');
                $populasi2026 = $page->toArray()['props']['kpiCards']['total_populasi_ternak'];
            });

        $this->assertNotNull($populasi2024, 'Total populasi 2024 must not be null');
        $this->assertNotNull($populasi2026, 'Total populasi 2026 must not be null');
        $this->assertGreaterThan(0, $populasi2024, 'Total populasi 2024 must be > 0');
        $this->assertGreaterThan(0, $populasi2026, 'Total populasi 2026 must be > 0');
        $this->assertNotEquals($populasi2024, $populasi2026, 'Dinas dashboard data must dynamically differ between tahun 2024 and 2026');
    }

    /**
     * 6. Super Admin Master Data API: GET /admin/api/master-data
     */
    public function test_super_admin_master_data_api_lists_all_master_entities(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $response = $this->actingAs($admin)
            ->getJson('/admin/api/master-data');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'komoditas',
                'fasilitas',
                'kecamatan',
                'data' => [
                    'komoditas',
                    'fasilitas',
                    'kecamatan',
                ],
            ]);

        $responseData = $response->json();
        $this->assertNotEmpty($responseData['komoditas'], 'komoditas list must not be empty');
        $this->assertNotEmpty($responseData['fasilitas'], 'fasilitas list must not be empty');
        $this->assertCount(27, $responseData['kecamatan'], 'kecamatan list must contain 27 kecamatan');

        // Check coordinates formatting on first facility
        $firstFacility = $responseData['fasilitas'][0];
        $this->assertArrayHasKey('lat', $firstFacility);
        $this->assertArrayHasKey('lng', $firstFacility);
    }

    /**
     * 6. Super Admin Master Data API: POST /admin/api/commodities
     */
    public function test_super_admin_can_create_new_commodity(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $payload = [
            'kode' => 'KUDA_PACU_TEST',
            'nama' => 'Kuda Pacu Sumbawa',
            'kelompok' => 'Ternak Besar',
            'aktif' => true,
        ];

        $response = $this->actingAs($admin)
            ->postJson('/admin/api/commodities', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.kode', 'KUDA_PACU_TEST');

        $this->assertDatabaseHas('komoditas', [
            'kode' => 'KUDA_PACU_TEST',
            'nama' => 'Kuda Pacu Sumbawa',
            'kelompok' => 'Ternak Besar',
        ]);
    }

    /**
     * 6. Super Admin Master Data API: POST /admin/api/facilities
     */
    public function test_super_admin_can_create_new_facility_with_coordinates(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $kecamatan = Kecamatan::firstOrFail();

        $payload = [
            'kecamatan_id' => $kecamatan->id,
            'nama_rph' => 'Puskeswan Tambaksari Satelit',
            'tipe_fasilitas' => 'Puskeswan',
            'alamat_lengkap' => 'Jl. Tambaksari Km 4, Banyumas',
            'status_operasional' => 'Aktif',
            'lat' => -7.4321,
            'lng' => 109.2543,
            'wilayah_binaan' => 'Banyumas Timur',
            'penanggung_jawab' => 'drh. Tri Wahyuni',
            'nomor_kontak' => '081234567890',
        ];

        $response = $this->actingAs($admin)
            ->postJson('/admin/api/facilities', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.nama_rph', 'Puskeswan Tambaksari Satelit')
            ->assertJsonPath('data.tipe_fasilitas', 'Puskeswan');

        $this->assertDatabaseHas('fasilitas_rph', [
            'nama_rph' => 'Puskeswan Tambaksari Satelit',
            'tipe_fasilitas' => 'Puskeswan',
        ]);
    }

    /**
     * 6. Super Admin Master Data API: PUT /admin/api/kecamatan/{id}/hmt
     */
    public function test_super_admin_can_update_kecamatan_forage_hectares(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $kecamatan = Kecamatan::firstOrFail();

        $payload = [
            'luas_hmt_ha' => 245.50,
        ];

        $response = $this->actingAs($admin)
            ->putJson("/admin/api/kecamatan/{$kecamatan->id}/hmt", $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $kecamatan->id);

        $this->assertDatabaseHas('kecamatan', [
            'id' => $kecamatan->id,
            'luas_hmt_ha' => 245.50,
        ]);
    }

    /**
     * 6. Super Admin Master Data API: GET & POST /admin/api/diseases
     */
    public function test_super_admin_can_list_and_create_diseases_via_master_data_api(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        // GET /admin/api/diseases
        $listResponse = $this->actingAs($admin)
            ->getJson('/admin/api/diseases');

        $listResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'kode',
                        'nama',
                        'tingkat_bahaya',
                    ],
                ],
            ]);

        $diseases = $listResponse->json('data');
        $this->assertNotEmpty($diseases, 'Diseases list must not be empty');

        // POST /admin/api/diseases
        $payload = [
            'kode' => 'BRUCELLOSIS_TEST',
            'nama' => 'Brucellosis Bovis',
            'nama_latin' => 'Brucella abortus',
            'tingkat_bahaya' => 'Tinggi',
            'fcode' => 'CA0020',
            'ambang_kuning_maks' => 4,
            'jendela_hari_aktif' => 21,
        ];

        $createResponse = $this->actingAs($admin)
            ->postJson('/admin/api/diseases', $payload);

        $createResponse->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.kode', 'BRUCELLOSIS_TEST')
            ->assertJsonPath('data.nama', 'Brucellosis Bovis')
            ->assertJsonPath('data.parameter_zonasi.ambang_kuning_maks', 4);

        $this->assertDatabaseHas('penyakit', [
            'kode' => 'BRUCELLOSIS_TEST',
            'nama' => 'Brucellosis Bovis',
            'tingkat_bahaya' => 'Tinggi',
        ]);

        $createdDisease = Penyakit::where('kode', 'BRUCELLOSIS_TEST')->first();
        $this->assertNotNull($createdDisease, 'Created disease entity must be retrievable via Penyakit Eloquent model');
        $this->assertSame('Brucellosis Bovis', $createdDisease->nama);
    }

    /**
     * 6. Super Admin Master Data API: GET & POST /admin/api/production-categories
     */
    public function test_super_admin_can_list_and_create_production_categories_via_master_data_api(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        // GET /admin/api/production-categories
        $listResponse = $this->actingAs($admin)
            ->getJson('/admin/api/production-categories');

        $listResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'nama',
                        'satuan',
                    ],
                ],
            ]);

        $categories = $listResponse->json('data');
        $this->assertNotEmpty($categories, 'Production categories list must not be empty');

        // POST /admin/api/production-categories
        $payload = [
            'nama' => 'Madu Hutan Test',
            'satuan' => 'Liter',
        ];

        $createResponse = $this->actingAs($admin)
            ->postJson('/admin/api/production-categories', $payload);

        $createResponse->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.nama', 'Madu Hutan Test')
            ->assertJsonPath('data.satuan', 'Liter');

        $this->assertDatabaseHas('kategori_produksi', [
            'nama' => 'Madu Hutan Test',
            'satuan' => 'Liter',
        ]);

        $createdCategory = KategoriProduksi::where('nama', 'Madu Hutan Test')->first();
        $this->assertNotNull($createdCategory, 'Created category must be retrievable via KategoriProduksi Eloquent model');
        $this->assertSame('Liter', $createdCategory->satuan);
    }

    /**
     * 7. System Maintenance & Operational Status API: GET /admin/api/maintenance/status
     */
    public function test_system_maintenance_endpoint_returns_operational_status_for_super_admin(): void
    {
        $admin = User::where('role', 'superadmin')->first()
            ?? new User(['id' => 1, 'name' => 'Super Admin', 'email' => 'superadmin@banyumaskab.go.id', 'role' => 'superadmin', 'aktif' => true]);

        $response = $this->actingAs($admin)
            ->getJson('/admin/api/maintenance/status');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('database', 'connected')
            ->assertJsonPath('cache_status', 'active')
            ->assertJsonStructure([
                'status',
                'database',
                'driver',
                'server_time',
                'cache_status',
                'cache_driver',
            ]);
    }

    /**
     * 6. Super Admin Master Data API: Non-admin users receive HTTP 403
     */
    public function test_master_data_api_authorization_blocks_non_admin_users(): void
    {
        $stakeholder = User::where('role', 'stakeholder')->first()
            ?? new User(['id' => 2, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);

        $kecamatan = Kecamatan::firstOrFail();

        // GET /admin/api/master-data is blocked
        $this->actingAs($stakeholder)
            ->getJson('/admin/api/master-data')
            ->assertStatus(403);

        // POST /admin/api/commodities is blocked
        $this->actingAs($stakeholder)
            ->postJson('/admin/api/commodities', [
                'kode' => 'ILEGAL_TEST',
                'nama' => 'Ilegal Test',
                'kelompok' => 'Ternak Kecil',
            ])
            ->assertStatus(403);

        // POST /admin/api/facilities is blocked
        $this->actingAs($stakeholder)
            ->postJson('/admin/api/facilities', [
                'kecamatan_id' => $kecamatan->id,
                'nama_rph' => 'Fasilitas Ilegal',
                'tipe_fasilitas' => 'RPH',
                'alamat_lengkap' => 'Jl. Ilegal',
                'status_operasional' => 'Aktif',
                'lat' => -7.4,
                'lng' => 109.2,
            ])
            ->assertStatus(403);

        // PUT /admin/api/kecamatan/{id}/hmt is blocked
        $this->actingAs($stakeholder)
            ->putJson("/admin/api/kecamatan/{$kecamatan->id}/hmt", [
                'luas_hmt_ha' => 999.99,
            ])
            ->assertStatus(403);

        // GET & POST /admin/api/diseases is blocked
        $this->actingAs($stakeholder)
            ->getJson('/admin/api/diseases')
            ->assertStatus(403);

        $this->actingAs($stakeholder)
            ->postJson('/admin/api/diseases', [
                'kode' => 'ILLEGAL_DISEASE',
                'nama' => 'Penyakit Ilegal',
                'tingkat_bahaya' => 'Rendah',
            ])
            ->assertStatus(403);

        // GET & POST /admin/api/production-categories is blocked
        $this->actingAs($stakeholder)
            ->getJson('/admin/api/production-categories')
            ->assertStatus(403);

        $this->actingAs($stakeholder)
            ->postJson('/admin/api/production-categories', [
                'nama' => 'Kategori Ilegal',
                'satuan' => 'Kg',
            ])
            ->assertStatus(403);

        // GET /admin/api/maintenance/status is blocked
        $this->actingAs($stakeholder)
            ->getJson('/admin/api/maintenance/status')
            ->assertStatus(403);
    }

    /**
     * 8. Dinas Dashboard Commodity Filtering: SEMUA vs Specific Commodity
     */
    public function test_dinas_dashboard_supports_semua_komoditas_and_specific_commodity_filtering(): void
    {
        $stakeholder = User::where('role', 'stakeholder')->first()
            ?? new User(['id' => 2, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);

        $populasiSemua = null;
        $responseSemua = $this->actingAs($stakeholder)
            ->get('/dinas/dashboard?tahun=2026&komoditas=SEMUA');

        $responseSemua->assertOk()
            ->assertInertia(function (Assert $page) use (&$populasiSemua) {
                $page->component('Dinas/Dashboard', false)
                    ->where('filterState.komoditas', 'SEMUA')
                    ->has('kpiCards.total_populasi_ternak');
                $populasiSemua = (int) $page->toArray()['props']['kpiCards']['total_populasi_ternak'];
            });

        $populasiSapi = null;
        $responseSapi = $this->actingAs($stakeholder)
            ->get('/dinas/dashboard?tahun=2026&komoditas=SAPI_POTONG');

        $responseSapi->assertOk()
            ->assertInertia(function (Assert $page) use (&$populasiSapi) {
                $page->component('Dinas/Dashboard', false)
                    ->where('filterState.komoditas', 'SAPI_POTONG')
                    ->has('kpiCards.total_populasi_ternak');
                $populasiSapi = (int) $page->toArray()['props']['kpiCards']['total_populasi_ternak'];
            });

        $this->assertNotNull($populasiSemua, 'Total populasi for SEMUA must not be null');
        $this->assertNotNull($populasiSapi, 'Total populasi for SAPI_POTONG must not be null');
        $this->assertGreaterThan(0, $populasiSemua, 'Total populasi for SEMUA must be > 0');
        $this->assertGreaterThan(0, $populasiSapi, 'Total populasi for SAPI_POTONG must be > 0');
        $this->assertLessThan($populasiSemua, $populasiSapi, 'Total populasi for SAPI_POTONG must be less than aggregate total for SEMUA');
    }

    /**
     * 9. Official Executive PDF Report Export: GET /internal/laporan/ekspor-pdf
     */
    public function test_official_pdf_report_export_returns_valid_pdf_stream(): void
    {
        $stakeholder = User::where('role', 'stakeholder')->first()
            ?? new User(['id' => 2, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);

        $response = $this->actingAs($stakeholder)
            ->get('/internal/laporan/ekspor-pdf?tahun=2026');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
