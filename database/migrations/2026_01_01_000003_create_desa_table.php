<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Master Wilayah Desa / Kelurahan (Point SRID 4326).
     * Titik koordinat Balai Desa berfungsi sebagai titik referensi privasi peternak (UU PDP).
     */
    public function up(): void
    {
        $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));

        Schema::create('desa', function (Blueprint $table) use ($hasPostgis) {
            $table->id();
            $table->foreignId('kecamatan_id')
                ->constrained('kecamatan')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->string('kode_desa', 20)->unique();
            $table->string('nama_desa', 100);
            $table->string('jenis', 20)->default('Desa');

            if ($hasPostgis) {
                $table->geometry('titik_balai_desa', subtype: 'point', srid: 4326);
            } else {
                $table->jsonb('titik_balai_desa');
            }

            $table->string('fcode', 10)->default('BA0020');
            $table->text('remark')->nullable();
            $table->timestampsTz();

            $table->index('kecamatan_id', 'idx_desa_kecamatan');
        });

        // Check constraint untuk kolom jenis (Desa / Kelurahan)
        DB::statement("ALTER TABLE desa ADD CONSTRAINT chk_desa_jenis CHECK (jenis IN ('Desa', 'Kelurahan'));");

        // Indeks Spasial
        if ($hasPostgis) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_desa_geom ON desa USING GIST (titik_balai_desa);');
        } else {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_desa_geom_gin ON desa USING GIN (titik_balai_desa);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desa');
    }
};
