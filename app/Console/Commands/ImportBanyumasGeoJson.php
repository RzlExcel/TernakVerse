<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\Desa;

class ImportBanyumasGeoJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo:import-banyumas 
                            {--kecamatan= : Jalur file GeoJSON kecamatan} 
                            {--desa= : Jalur file GeoJSON desa}
                            {--fresh : Hapus data lama wilayah sebelum impor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor data geospasial batas wilayah 27 Kecamatan dan Balai Desa Kabupaten Banyumas ke PostgreSQL/PostGIS (SRID 4326)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('===========================================================');
        $this->info('  TERNAKVERSE: WebGIS Ingestion Engine - Kabupaten Banyumas');
        $this->info('===========================================================');

        $candidateKecamatan = [
            $this->option('kecamatan'),
            database_path('data/banyumas_kecamatan.geojson'),
            database_path('data/banyumas_kecamatan_web.geojson'),
            public_path('data/banyumas_kecamatan.geojson'),
            public_path('data/banyumas_kecamatan_web.geojson'),
            base_path('../data/data/banyumas_kecamatan.geojson'),
            base_path('../data/data/banyumas_kecamatan_web.geojson'),
        ];
        $kecamatanFile = null;
        foreach ($candidateKecamatan as $cand) {
            if ($cand && file_exists($cand)) {
                $kecamatanFile = $cand;
                break;
            }
        }

        $candidateDesa = [
            $this->option('desa'),
            database_path('data/banyumas_desa.geojson'),
            database_path('data/banyumas_desa_web.geojson'),
            public_path('data/banyumas_desa.geojson'),
            public_path('data/banyumas_desa_web.geojson'),
            base_path('../data/data/banyumas_desa.geojson'),
            base_path('../data/data/banyumas_desa_web.geojson'),
        ];
        $desaFile = null;
        foreach ($candidateDesa as $cand) {
            if ($cand && file_exists($cand)) {
                $desaFile = $cand;
                break;
            }
        }

        if (!$kecamatanFile) {
            $this->error("Berkas GeoJSON Kecamatan tidak ditemukan di jalur pencarian.");
            return Command::FAILURE;
        }

        if (!$desaFile) {
            $this->error("Berkas GeoJSON Desa tidak ditemukan di jalur pencarian.");
            return Command::FAILURE;
        }

        $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));
        $this->info("Status PostGIS Database: " . ($hasPostgis ? "AKTIF (Native Geometry & GiST)" : "FALLBACK (JSONB)"));

        // Step 1: Import Kecamatan
        $this->importKecamatan($kecamatanFile, $hasPostgis);

        // Step 2: Import Desa
        $this->importDesa($desaFile, $hasPostgis);

        $this->info("\n[BERHASIL] Seluruh data geospasial wilayah Kabupaten Banyumas berhasil diimpor.");
        $this->table(
            ['Entitas Wilayah', 'Jumlah Tercatat di DB', 'SRID Proyeksi'],
            [
                ['Kecamatan', Kecamatan::count(), 'EPSG:4326 (WGS 84)'],
                ['Desa / Kelurahan', Desa::count(), 'EPSG:4326 (Point Balai Desa)'],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Ingest Batas Administrasi 27 Kecamatan (MultiPolygon)
     */
    protected function importKecamatan(string $filePath, bool $hasPostgis): void
    {
        $this->info("\n[1/2] Membaca GeoJSON Kecamatan: {$filePath}...");
        $raw = file_get_contents($filePath);
        $data = json_decode($raw, true);

        if (!isset($data['features']) || !is_array($data['features'])) {
            $this->error("Struktur GeoJSON kecamatan tidak valid (tidak ada properti 'features').");
            return;
        }

        $features = $data['features'];
        $count = count($features);
        $this->info("Ditemukan {$count} fitur kecamatan. Memulai proses ingesti...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $inserted = 0;
        $updated = 0;

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            if (!$geometry) {
                $bar->advance();
                continue;
            }

            // Ekstrak kode dan nama kecamatan
            $kodeKec = $props['kode_kec'] ?? $props['fid'] ?? $props['kdcpum'] ?? null;
            $namaKec = $props['kecamatan'] ?? $props['nama'] ?? $props['wadmkc'] ?? 'Tanpa Nama';
            
            // Bersihkan format "Kecamatan ..." jika ada
            $cleanNama = trim(preg_replace('/^Kecamatan\s+/i', '', $namaKec));

            if (!$kodeKec) {
                $bar->advance();
                continue;
            }

            $geomJson = json_encode($geometry);

            // Hitung luas wilayah otomatis via PostGIS jika tersedia
            $luasKm2 = null;
            if ($hasPostgis) {
                try {
                    $calc = DB::select("SELECT ROUND((ST_Area(ST_GeomFromGeoJSON(?)::geography) / 1000000.0)::numeric, 2) AS luas_km2", [$geomJson]);
                    $luasKm2 = $calc[0]->luas_km2 ?? null;
                } catch (\Throwable $e) {
                    $luasKm2 = null;
                }
            }

            // Upsert kecamatan
            $existing = Kecamatan::where('kode_kecamatan', $kodeKec)->first();

            if ($hasPostgis) {
                if ($existing) {
                    DB::statement("
                        UPDATE kecamatan 
                        SET nama_kecamatan = ?,
                            geom = ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)),
                            luas_wilayah_km2 = ?,
                            fcode = 'BA0010',
                            metadata_id = 'ISO-19115-DINPERTAN-BMS-KEC',
                            srs_id = 'EPSG:4326',
                            remark = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$cleanNama, $geomJson, $luasKm2, $props['label'] ?? null, $existing->id]);
                    $updated++;
                } else {
                    DB::statement("
                        INSERT INTO kecamatan (
                            kode_kecamatan, nama_kecamatan, geom, luas_wilayah_km2,
                            fcode, metadata_id, srs_id, remark, created_at, updated_at
                        ) VALUES (
                            ?, ?, ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)), ?,
                            'BA0010', 'ISO-19115-DINPERTAN-BMS-KEC', 'EPSG:4326', ?, NOW(), NOW()
                        )
                    ", [$kodeKec, $cleanNama, $geomJson, $luasKm2, $props['label'] ?? null]);
                    $inserted++;
                }
            } else {
                Kecamatan::updateOrCreate(
                    ['kode_kecamatan' => $kodeKec],
                    [
                        'nama_kecamatan' => $cleanNama,
                        'geom' => $geometry,
                        'luas_wilayah_km2' => $luasKm2,
                        'fcode' => 'BA0010',
                        'metadata_id' => 'ISO-19115-DINPERTAN-BMS-KEC',
                        'srs_id' => 'EPSG:4326',
                        'remark' => $props['label'] ?? null,
                    ]
                );
                $inserted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("\n  -> Kecamatan: {$inserted} baru ditambahkan, {$updated} diperbarui.");
    }

    /**
     * Ingest Batas Desa & Generate Titik Koordinat Balai Desa (Point SRID 4326)
     */
    protected function importDesa(string $filePath, bool $hasPostgis): void
    {
        $this->info("\n[2/2] Membaca GeoJSON Desa: {$filePath}...");
        $raw = file_get_contents($filePath);
        $data = json_decode($raw, true);

        if (!isset($data['features']) || !is_array($data['features'])) {
            $this->error("Struktur GeoJSON desa tidak valid (tidak ada properti 'features').");
            return;
        }

        $features = $data['features'];
        $count = count($features);
        $this->info("Ditemukan {$count} fitur desa. Menghubungkan ke ID Kecamatan & membuat titik balai desa...");

        // Peta lookup kecamatan kode -> id dan nama -> id
        $kecLookup = Kecamatan::pluck('id', 'kode_kecamatan')->toArray();
        $kecNamaLookup = Kecamatan::all()->mapWithKeys(function ($k) {
            return [strtoupper(trim($k->nama_kecamatan)) => $k->id];
        })->toArray();

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            if (!$geometry) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $kodeKd = $props['kode_kd'] ?? $props['fid'] ?? null;
            $namaDesa = $props['kel_desa'] ?? $props['ori_name'] ?? $props['nama'] ?? 'Tanpa Nama';
            $namaDesaClean = trim(preg_replace('/^(Desa|Kelurahan)\s+/i', '', $namaDesa));

            $rawJenis = $props['jenis_kd'] ?? 'Desa';
            $jenis = (stripos($rawJenis, 'kelurahan') !== false || stripos($namaDesa, 'kelurahan') !== false) 
                ? 'Kelurahan' 
                : 'Desa';

            $kodeKec = $props['kode_kec'] ?? null;
            $namaKec = $props['kecamatan'] ?? null;

            // Cari relasi kecamatan_id
            $kecamatanId = null;
            if ($kodeKec && isset($kecLookup[$kodeKec])) {
                $kecamatanId = $kecLookup[$kodeKec];
            } elseif ($namaKec) {
                $key = strtoupper(trim(preg_replace('/^Kecamatan\s+/i', '', $namaKec)));
                if (isset($kecNamaLookup[$key])) {
                    $kecamatanId = $kecNamaLookup[$key];
                }
            }

            if (!$kecamatanId || !$kodeKd) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $geomJson = json_encode($geometry);
            $existing = Desa::where('kode_desa', $kodeKd)->first();

            if ($hasPostgis) {
                // PostGIS: Titik Balai Desa diambil dari ST_PointOnSurface agar presisi berada di dalam batas desa
                if ($existing) {
                    DB::statement("
                        UPDATE desa 
                        SET kecamatan_id = ?,
                            nama_desa = ?,
                            jenis = ?,
                            titik_balai_desa = ST_SetSRID(ST_PointOnSurface(ST_GeomFromGeoJSON(?)), 4326),
                            fcode = 'BA0020',
                            remark = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$kecamatanId, $namaDesaClean, $jenis, $geomJson, $props['label'] ?? null, $existing->id]);
                    $updated++;
                } else {
                    DB::statement("
                        INSERT INTO desa (
                            kecamatan_id, kode_desa, nama_desa, jenis,
                            titik_balai_desa, fcode, remark, created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?,
                            ST_SetSRID(ST_PointOnSurface(ST_GeomFromGeoJSON(?)), 4326),
                            'BA0020', ?, NOW(), NOW()
                        )
                    ", [$kecamatanId, $kodeKd, $namaDesaClean, $jenis, $geomJson, $props['label'] ?? null]);
                    $inserted++;
                }
            } else {
                // Fallback kalkulasi titik tengah kasar untuk koordinat
                $coords = $geometry['coordinates'][0][0][0] ?? [109.2302, -7.4243];
                $pointFallback = [
                    'type' => 'Point',
                    'coordinates' => [$coords[0], $coords[1]]
                ];

                Desa::updateOrCreate(
                    ['kode_desa' => $kodeKd],
                    [
                        'kecamatan_id' => $kecamatanId,
                        'nama_desa' => $namaDesaClean,
                        'jenis' => $jenis,
                        'titik_balai_desa' => $pointFallback,
                        'fcode' => 'BA0020',
                        'remark' => $props['label'] ?? null,
                    ]
                );
                $inserted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("\n  -> Desa: {$inserted} baru ditambahkan, {$updated} diperbarui, {$skipped} dilewati.");
    }
}
