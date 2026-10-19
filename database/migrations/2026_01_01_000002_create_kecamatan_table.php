<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Master Wilayah Kecamatan (MultiPolygon SRID 4326).
     * Sesuai Standar KUGI Unsur ADMINISTRASI_AR_PETERNAKAN.
     */
    public function up(): void
    {
        $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));

        Schema::create('kecamatan', function (Blueprint $table) use ($hasPostgis) {
            $table->id();
            $table->string('kode_kecamatan', 8)->unique(); // KDCPUM Standar Kemendagri / BPS
            $table->string('nama_kecamatan', 100);          // WADMKC
            
            // Kolom Geometri: MultiPolygon SRID 4326 (PostGIS) atau JSONB (Fallback Lokal)
            if ($hasPostgis) {
                $table->geometry('geom', subtype: 'multipolygon', srid: 4326);
            } else {
                $table->jsonb('geom');
            }

            $table->decimal('luas_wilayah_km2', 10, 2)->nullable();
            $table->string('fcode', 10)->default('BA0010'); // Kode Unsur Geografi Indonesia
            $table->string('metadata_id', 100)->default('ISO-19115-DINPERTAN-BMS-KEC');
            $table->string('srs_id', 10)->default('EPSG:4326');
            $table->text('remark')->nullable();
            $table->timestampsTz();

            // Indeks B-Tree untuk pencarian kode
            $table->index('kode_kecamatan', 'idx_kecamatan_kode');
        });

        // Indeks Spasial
        if ($hasPostgis) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_kecamatan_geom ON kecamatan USING GIST (geom);');
        } else {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_kecamatan_geom_gin ON kecamatan USING GIN (geom);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kecamatan');
    }
};
