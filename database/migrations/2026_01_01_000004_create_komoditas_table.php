<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Master Jenis Komoditas Peternakan.
     */
    public function up(): void
    {
        Schema::create('komoditas', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->string('kelompok', 30);
            $table->boolean('aktif')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE komoditas ADD CONSTRAINT chk_komoditas_kelompok CHECK (kelompok IN ('Ternak Besar', 'Ternak Kecil', 'Unggas'));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komoditas');
    }
};
