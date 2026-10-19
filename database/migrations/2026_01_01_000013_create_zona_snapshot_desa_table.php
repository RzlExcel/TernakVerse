<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Snapshot Status Zonasi Tingkat Desa / Kelurahan.
     */
    public function up(): void
    {
        Schema::create('zona_snapshot_desa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')
                ->constrained('desa')
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
                ['desa_id', 'penyakit_id', 'tahun', 'triwulan'],
                'uq_zona_desa_periode'
            );

            $table->index(
                ['desa_id', 'penyakit_id', 'tahun', 'triwulan'],
                'idx_zona_desa_lookup'
            );
        });

        DB::statement("ALTER TABLE zona_snapshot_desa ADD CONSTRAINT chk_zona_desa_status CHECK (status_zona IN ('HIJAU', 'KUNING', 'MERAH'));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zona_snapshot_desa');
    }
};
