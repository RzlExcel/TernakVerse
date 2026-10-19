<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Snapshot Status Zonasi Tingkat Kecamatan (EWS PMK & LSD).
     * Melayani endpoint cepat untuk peta publik tanpa perhitungan realtime berat.
     */
    public function up(): void
    {
        Schema::create('zona_snapshot_kecamatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')
                ->constrained('kecamatan')
                ->onDelete('cascade');
            $table->smallInteger('penyakit_id');
            $table->foreign('penyakit_id')
                ->references('id')
                ->on('penyakit')
                ->onDelete('cascade');
            $table->smallInteger('tahun');
            $table->smallInteger('triwulan');
            $table->string('status_zona', 10);
            $table->integer('total_kasus_aktif')->default(0);
            $table->timestampTz('waktu_kalkulasi')->useCurrent();

            $table->unique(
                ['kecamatan_id', 'penyakit_id', 'tahun', 'triwulan'],
                'uq_zona_kecamatan_periode'
            );

            $table->index(
                ['kecamatan_id', 'penyakit_id', 'tahun', 'triwulan'],
                'idx_zona_kecamatan_lookup'
            );
        });

        DB::statement("ALTER TABLE zona_snapshot_kecamatan ADD CONSTRAINT chk_zona_kecamatan_status CHECK (status_zona IN ('HIJAU', 'KUNING', 'MERAH'));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona_snapshot_kecamatan');
    }
};
