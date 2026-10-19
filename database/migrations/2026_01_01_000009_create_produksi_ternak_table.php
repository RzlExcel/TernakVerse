<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Transaksional Hasil Produksi Peternakan (Daging, Susu, Telur).
     */
    public function up(): void
    {
        Schema::create('produksi_ternak', function (Blueprint $table) {
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
            $table->smallInteger('kategori_produksi_id');
            $table->foreign('kategori_produksi_id')
                ->references('id')
                ->on('kategori_produksi')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->smallInteger('tahun');
            $table->smallInteger('triwulan');
            $table->decimal('jumlah_produksi', 14, 2)->default(0.00);
            $table->timestampsTz();

            $table->unique(
                ['desa_id', 'komoditas_id', 'kategori_produksi_id', 'tahun', 'triwulan'],
                'uq_produksi_desa_komoditas_kategori_periode'
            );

            $table->index(['tahun', 'triwulan'], 'idx_produksi_periode');
        });

        DB::statement('ALTER TABLE produksi_ternak ADD CONSTRAINT chk_produksi_tahun CHECK (tahun >= 2020 AND tahun <= 2035);');
        DB::statement('ALTER TABLE produksi_ternak ADD CONSTRAINT chk_produksi_triwulan CHECK (triwulan BETWEEN 1 AND 4);');
        DB::statement('ALTER TABLE produksi_ternak ADD CONSTRAINT chk_produksi_jumlah CHECK (jumlah_produksi >= 0);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_ternak');
    }
};
