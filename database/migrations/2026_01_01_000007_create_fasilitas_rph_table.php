<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Fasilitas Geospasial Rumah Potong Hewan (RPH).
     * Sesuai mandat KAK Geospasial Peternakan Banyumas 2026.
     */
    public function up(): void
    {
        $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));

        Schema::create('fasilitas_rph', function (Blueprint $table) use ($hasPostgis) {
            $table->id();
            $table->foreignId('kecamatan_id')
                ->constrained('kecamatan')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->string('nama_rph', 150);
            $table->text('alamat_lengkap');
            $table->string('jenis_pengelola', 50)->default('Pemerintah');
            $table->string('jenis_hewan_potong', 100); // Sapi, Kerbau, Kambing, Babi
            $table->integer('kapasitas_potong_ekor_hari')->default(0);
            $table->string('status_operasional', 20)->default('Aktif');

            if ($hasPostgis) {
                $table->geometry('geom_titik', subtype: 'point', srid: 4326);
            } else {
                $table->jsonb('geom_titik');
            }

            $table->string('nomor_kontak', 30)->nullable();
            $table->timestampsTz();

            $table->index('kecamatan_id', 'idx_rph_kecamatan');
        });

        // Check constraints
        DB::statement("ALTER TABLE fasilitas_rph ADD CONSTRAINT chk_rph_pengelola CHECK (jenis_pengelola IN ('Pemerintah', 'Swasta', 'Kelompok Tani'));");
        DB::statement("ALTER TABLE fasilitas_rph ADD CONSTRAINT chk_rph_status CHECK (status_operasional IN ('Aktif', 'Tidak Aktif', 'Renovasi'));");

        // Indeks Spasial
        if ($hasPostgis) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_rph_geom ON fasilitas_rph USING GIST (geom_titik);');
        } else {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_rph_geom_gin ON fasilitas_rph USING GIN (geom_titik);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fasilitas_rph');
    }
};
