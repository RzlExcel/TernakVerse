<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Parameter Ambang Batas Zonasi EWS per Jenis Penyakit.
     * Ambang Batas: 0 = Hijau, 1 s/d ambang_kuning_maks = Kuning, > ambang_kuning_maks = Merah.
     */
    public function up(): void
    {
        Schema::create('parameter_zonasi', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('penyakit_id');
            $table->foreign('penyakit_id')
                ->references('id')
                ->on('penyakit')
                ->onDelete('cascade');
            $table->smallInteger('jendela_hari_aktif')->default(14); // Jumlah hari analisis riwayat kasus
            $table->integer('ambang_kuning_maks')->default(5);      // Batas atas kasus untuk status Kuning
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameter_zonasi');
    }
};
