<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Transaksional Populasi Ternak (Format KAK Hal 5: Jantan, Betina, Total).
     * Kolom jumlah_total dihitung otomatis oleh PostgreSQL melalui STORED GENERATED COLUMN.
     */
    public function up(): void
    {
        Schema::create('populasi_ternak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')
                ->constrained('desa')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->smallInteger('komoditas_id');
            $table->foreign('komoditas_id')
                ->references('id')
                ->on('komoditas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->smallInteger('tahun');
            $table->smallInteger('triwulan');
            $table->integer('jumlah_jantan')->default(0);
            $table->integer('jumlah_betina')->default(0);
            
            // GENERATED ALWAYS AS (jumlah_jantan + jumlah_betina) STORED
            $table->integer('jumlah_total')->storedAs('jumlah_jantan + jumlah_betina');

            $table->timestampsTz();

            // Batasan unik komposit: 1 desa hanya memiliki 1 catatan per komoditas dalam 1 triwulan
            $table->unique(['desa_id', 'komoditas_id', 'tahun', 'triwulan'], 'uq_populasi_desa_komoditas_periode');

            // Indeks pencarian performa tinggi
            $table->index(['tahun', 'triwulan'], 'idx_populasi_periode');
            $table->index('komoditas_id', 'idx_populasi_komoditas');
        });

        // Validasi integritas data numerik
        DB::statement('ALTER TABLE populasi_ternak ADD CONSTRAINT chk_populasi_tahun CHECK (tahun >= 2020 AND tahun <= 2035);');
        DB::statement('ALTER TABLE populasi_ternak ADD CONSTRAINT chk_populasi_triwulan CHECK (triwulan BETWEEN 1 AND 4);');
        DB::statement('ALTER TABLE populasi_ternak ADD CONSTRAINT chk_populasi_jantan CHECK (jumlah_jantan >= 0);');
        DB::statement('ALTER TABLE populasi_ternak ADD CONSTRAINT chk_populasi_betina CHECK (jumlah_betina >= 0);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('populasi_ternak');
    }
};
