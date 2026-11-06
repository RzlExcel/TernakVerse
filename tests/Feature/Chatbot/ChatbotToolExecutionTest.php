<?php

declare(strict_types=1);

namespace Tests\Feature\Chatbot;

use App\Models\FasilitasRph;
use App\Models\KasusPenyakit;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\Penyakit;
use App\Models\PopulasiTernak;
use App\Models\User;
use App\Models\ZonaSnapshotKecamatan;
use App\Services\Chatbot\ToolExecutionService;
use App\Services\Chatbot\Tools\GeospatialVeterinaryTools;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ChatbotToolExecutionTest extends TestCase
{
    private ToolExecutionService $toolService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();

        $this->createSchema();
        $this->seedTestData();

        $this->toolService = new ToolExecutionService();
    }

    private function createSchema(): void
    {
        Schema::create('kecamatan', function ($table): void {
            $table->id();
            $table->string('nama_kecamatan');
            $table->string('kode_kecamatan')->nullable();
            $table->timestamps();
        });

        Schema::create('penyakit', function ($table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('zona_snapshot_kecamatan', function ($table): void {
            $table->id();
            $table->foreignId('kecamatan_id');
            $table->foreignId('penyakit_id');
            $table->integer('tahun');
            $table->smallInteger('triwulan')->default(1);
            $table->string('status_zona', 20);
            $table->integer('total_kasus_aktif')->default(0);
            $table->timestamp('waktu_kalkulasi')->nullable();
            $table->timestamps();
        });

        Schema::create('desa', function ($table): void {
            $table->id();
            $table->foreignId('kecamatan_id');
            $table->string('nama_desa');
            $table->string('kode_desa')->nullable();
            $table->timestamps();
        });

        Schema::create('kasus_penyakit', function ($table): void {
            $table->id();
            $table->foreignId('desa_id');
            $table->foreignId('penyakit_id');
            $table->integer('tahun');
            $table->date('tanggal_kasus')->nullable();
            $table->integer('kasus_positif')->default(0);
            $table->integer('kasus_sembuh')->default(0);
            $table->integer('kasus_mati')->default(0);
            $table->integer('potong_bersyarat')->default(0);
            $table->integer('kasus_aktif')->default(0);
            $table->timestamps();
        });

        Schema::create('fasilitas_rph', function ($table): void {
            $table->id();
            $table->foreignId('kecamatan_id')->nullable();
            $table->string('nama_rph');
            $table->string('tipe_fasilitas')->default('RPH');
            $table->string('alamat_lengkap')->nullable();
            $table->string('jenis_pengelola')->nullable();
            $table->string('jenis_hewan_potong')->nullable();
            $table->integer('kapasitas_potong_ekor_hari')->default(0);
            $table->string('status_operasional')->default('Aktif');
            $table->string('nomor_kontrol_veteriner')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->string('nomor_kontak')->nullable();
            $table->json('geom_titik')->nullable();
            $table->timestamps();
        });

        Schema::create('komoditas', function ($table): void {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('nama');
            $table->string('kategori')->nullable();
            $table->timestamps();
        });

        Schema::create('populasi_ternak', function ($table): void {
            $table->id();
            $table->foreignId('desa_id');
            $table->foreignId('komoditas_id');
            $table->integer('tahun');
            $table->integer('jumlah_jantan')->default(0);
            $table->integer('jumlah_betina')->default(0);
            $table->integer('jumlah_total')->default(0);
            $table->timestamps();
        });

        Schema::create('users', function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('peternak');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    private function seedTestData(): void
    {
        $cilongok = Kecamatan::create(['nama_kecamatan' => 'Cilongok', 'kode_kecamatan' => '33.02.14']);
        $ajibarang = Kecamatan::create(['nama_kecamatan' => 'Ajibarang', 'kode_kecamatan' => '33.02.15']);

        $pmk = Penyakit::create(['kode' => 'PMK', 'nama' => 'Penyakit Mulut dan Kuku']);
        $lsd = Penyakit::create(['kode' => 'LSD', 'nama' => 'Lumpy Skin Disease']);

        ZonaSnapshotKecamatan::create([
            'kecamatan_id' => $cilongok->id,
            'penyakit_id' => $pmk->id,
            'tahun' => 2026,
            'triwulan' => 1,
            'status_zona' => 'MERAH',
            'total_kasus_aktif' => 24,
            'waktu_kalkulasi' => now(),
        ]);

        ZonaSnapshotKecamatan::create([
            'kecamatan_id' => $ajibarang->id,
            'penyakit_id' => $pmk->id,
            'tahun' => 2026,
            'triwulan' => 1,
            'status_zona' => 'KUNING',
            'total_kasus_aktif' => 7,
            'waktu_kalkulasi' => now(),
        ]);

        $desaPernasidi = DB::table('desa')->insertGetId([
            'kecamatan_id' => $cilongok->id,
            'nama_desa' => 'Pernasidi',
            'kode_desa' => '33.02.14.2001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        KasusPenyakit::create([
            'desa_id' => $desaPernasidi,
            'penyakit_id' => $pmk->id,
            'tahun' => 2026,
            'kasus_positif' => 45,
            'kasus_sembuh' => 25,
            'kasus_mati' => 3,
            'potong_bersyarat' => 1,
            'kasus_aktif' => 16,
        ]);

        FasilitasRph::create([
            'kecamatan_id' => $ajibarang->id,
            'nama_rph' => 'RPH Ajibarang Resmi',
            'tipe_fasilitas' => 'RPH',
            'alamat_lengkap' => 'Jl. Raya Ajibarang No. 12, Banyumas',
            'kapasitas_potong_ekor_hari' => 35,
            'status_operasional' => 'Aktif',
            'penanggung_jawab' => 'Drh. Bambang Susanto',
            'nomor_kontak' => '0812-3456-7892',
            'geom_titik' => [
                'type' => 'Point',
                'coordinates' => [109.0833, -7.4167],
            ],
        ]);

        $sapi = Komoditas::create([
            'kode' => 'SAPI_POTONG',
            'nama' => 'Sapi Potong',
            'kategori' => 'Ruminansia Besar',
        ]);

        PopulasiTernak::create([
            'desa_id' => $desaPernasidi,
            'komoditas_id' => $sapi->id,
            'tahun' => 2026,
            'jumlah_jantan' => 150,
            'jumlah_betina' => 450,
            'jumlah_total' => 600,
        ]);
    }

    /**
     * getStatusZonasi returns valid zoning structure for known Banyumas kecamatan.
     */
    public function test_get_status_zonasi_returns_valid_structure_for_known_kecamatan(): void
    {
        $result = GeospatialVeterinaryTools::getStatusZonasi([
            'nama_kecamatan' => 'Cilongok',
            'penyakit' => 'PMK',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertStringContainsString('Cilongok', $result['wilayah']);
        $this->assertSame('PMK', $result['penyakit']);
        $this->assertSame('MERAH', $result['status_zona']);
        $this->assertSame(24, $result['total_kasus_aktif']);
        $this->assertNotEmpty($result['rekomendasi_tindakan']);
        $this->assertStringContainsString('Pengawasan ketat', $result['rekomendasi_tindakan']);
    }

    /**
     * getStatusZonasi returns safe structured error for invalid kecamatan without leaking SQL.
     */
    public function test_get_status_zonasi_handles_unknown_kecamatan_safely_without_leaking_sql(): void
    {
        $hostileInput = "NonExistentKecamatan' OR 1=1; DROP TABLE users; --";
        $result = GeospatialVeterinaryTools::getStatusZonasi([
            'nama_kecamatan' => $hostileInput,
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('tidak ditemukan di Kabupaten Banyumas', $result['message']);
        $this->assertStringNotContainsString('SQLSTATE', $result['message']);
        $this->assertStringNotContainsString('syntax error', $result['message']);
    }

    /**
     * getStatistikKasus aggregates kasus_positif, sembuh, mati, aktif for PMK/LSD.
     */
    public function test_get_statistik_kasus_aggregates_kasus_metrics(): void
    {
        $result = GeospatialVeterinaryTools::getStatistikKasus([
            'nama_kecamatan' => 'Cilongok',
            'penyakit' => 'PMK',
            'tahun' => 2026,
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(45, $result['total_positif']);
        $this->assertSame(25, $result['total_sembuh']);
        $this->assertSame(3, $result['total_mati']);
        $this->assertSame(1, $result['total_potong_bersyarat']);
        $this->assertSame(16, $result['total_aktif']);
    }

    /**
     * getFasilitasRph returns official RPH facilities and capacity details.
     */
    public function test_get_fasilitas_rph_returns_official_facilities(): void
    {
        $result = GeospatialVeterinaryTools::getFasilitasRph([
            'nama_kecamatan' => 'Ajibarang',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertGreaterThanOrEqual(1, $result['total_ditemukan']);
        $facility = $result['fasilitas'][0];

        $this->assertStringContainsString('RPH Ajibarang', $facility['nama_rph']);
        $this->assertSame(35, $facility['kapasitas_potong_harian']);
        $this->assertSame('Aktif', $facility['status_operasional']);
        $this->assertStringContainsString('Ajibarang', $facility['alamat_lengkap']);
    }

    /**
     * getPopulasiTernak returns livestock population (total_jantan, total_betina, grand_total).
     */
    public function test_get_populasi_ternak_returns_livestock_population(): void
    {
        $result = GeospatialVeterinaryTools::getPopulasiTernak([
            'komoditas' => 'Sapi Potong',
            'nama_kecamatan' => 'Cilongok',
            'tahun' => 2026,
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(150, $result['total_jantan']);
        $this->assertSame(450, $result['total_betina']);
        $this->assertSame(600, $result['grand_total']);
    }

    /**
     * ToolExecutionService validates missing required parameters gracefully.
     */
    public function test_tool_execution_service_validates_missing_required_parameters(): void
    {
        $result = $this->toolService->executeTool('getPopulasiTernak', [
            'nama_kecamatan' => 'Cilongok',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString("Parameter wajib tidak lengkap untuk 'getPopulasiTernak': komoditas", $result['message']);
    }

    /**
     * ToolExecutionService masks sensitive PII for unauthenticated public requests.
     */
    public function test_tool_execution_masks_pii_for_public_users(): void
    {
        $result = $this->toolService->executeTool('getFasilitasRph', [
            'nama_kecamatan' => 'Ajibarang',
        ], null);

        $this->assertSame('success', $result['status']);
        $facility = $result['fasilitas'][0];

        $this->assertSame(
            '[DISEMBUNYIKAN - HAK AKSES KHUSUS PETUGAS/DINAS]',
            $facility['penanggung_jawab'],
            'Expected personal contact name to be masked for public users'
        );
        $this->assertSame(
            '[DISEMBUNYIKAN - HAK AKSES KHUSUS PETUGAS/DINAS]',
            $facility['nomor_kontak'],
            'Expected telephone number to be masked for public users'
        );
    }

    /**
     * ToolExecutionService allows unmasked PII for authorized Super Admin and Admin users.
     */
    public function test_tool_execution_preserves_unmasked_pii_for_admin_users(): void
    {
        $admin = new User([
            'id' => 1,
            'name' => 'Petugas Medik Dinas',
            'email' => 'admin@banyumaskab.go.id',
            'role' => 'admin',
            'aktif' => true,
        ]);

        $result = $this->toolService->executeTool('getFasilitasRph', [
            'nama_kecamatan' => 'Ajibarang',
        ], $admin);

        $this->assertSame('success', $result['status']);
        $facility = $result['fasilitas'][0];

        $this->assertSame('Drh. Bambang Susanto', $facility['penanggung_jawab']);
        $this->assertSame('0812-3456-7892', $facility['nomor_kontak']);
    }

    /**
     * ToolExecutionService generates valid schema formats for OpenAI and Gemini function calling.
     */
    public function test_tool_execution_service_generates_valid_schema_catalogue(): void
    {
        $openaiSchemas = $this->toolService->getToolsSchema('openai');
        $this->assertCount(4, $openaiSchemas);
        $this->assertSame('function', $openaiSchemas[0]['type']);
        $this->assertArrayHasKey('name', $openaiSchemas[0]['function']);
        $this->assertArrayHasKey('parameters', $openaiSchemas[0]['function']);

        $geminiSchemas = $this->toolService->getToolsSchema('gemini');
        $this->assertCount(4, $geminiSchemas);
        $this->assertArrayHasKey('name', $geminiSchemas[0]);
        $this->assertArrayHasKey('parameters', $geminiSchemas[0]);
    }
}
