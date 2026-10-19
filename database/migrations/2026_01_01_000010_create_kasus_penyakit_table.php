<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Transaksional Kejadian Kasus Penyakit Hewan Menular (EWS PMK & LSD).
     * Kolom kasus_aktif dihitung secara deterministik dan disimpan otomatis oleh database.
     */
    public function up(): void
    {
        Schema::create('kasus_penyakit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')
                ->constrained('desa')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->smallInteger('penyakit_id');
            $table->foreign('penyakit_id')
                ->references('id')
                ->on('penyakit')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->smallInteger('komoditas_id');
            $table->foreign('komoditas_id')
                ->references('id')
                ->on('komoditas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->date('tanggal_laporan');
            $table->smallInteger('tahun');
            $table->smallInteger('triwulan');
            $table->integer('kasus_suspek')->default(0);
            $table->integer('kasus_positif')->default(0);
            $table->integer('kasus_sembuh')->default(0);
            $table->integer('kasus_mati')->default(0);
            $table->integer('potong_bersyarat')->default(0);
            
            // GENERATED ALWAYS AS GREATEST(0, (kasus_suspek + kasus_positif) - (kasus_sembuh + kasus_mati + potong_bersyarat)) STORED
            $table->integer('kasus_aktif')->storedAs(
                'GREATEST(0, (kasus_suspek + kasus_positif) - (kasus_sembuh + kasus_mati + potong_bersyarat))'
            );

            $table->text('catatan_lapangan')->nullable();
            $table->timestampsTz();

            $table->index('tanggal_laporan', 'idx_kasus_tgl');
            $table->index('penyakit_id', 'idx_kasus_penyakit');
            $table->index(['tahun', 'triwulan'], 'idx_kasus_periode');
        });

        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_tahun CHECK (tahun >= 2020 AND tahun <= 2035);');
        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_triwulan CHECK (triwulan BETWEEN 1 AND 4);');
        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_suspek CHECK (kasus_suspek >= 0);');
        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_positif CHECK (kasus_positif >= 0);');
        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_sembuh CHECK (kasus_sembuh >= 0);');
        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_mati CHECK (kasus_mati >= 0);');
        DB::statement('ALTER TABLE kasus_penyakit ADD CONSTRAINT chk_kasus_potong CHECK (potong_bersyarat >= 0);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kasus_penyakit');
    }
};
