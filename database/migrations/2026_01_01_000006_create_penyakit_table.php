<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Master Penyakit Menular Hewan (EWS Wabah PMK, LSD, dll).
     */
    public function up(): void
    {
        Schema::create('penyakit', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('kode', 20)->unique(); // PMK, LSD
            $table->string('nama', 150);
            $table->string('nama_latin', 150)->nullable();
            $table->string('tingkat_bahaya', 20)->default('Tinggi');
            $table->string('fcode', 10)->default('CA0010'); // KUGI Unsur Penyakit
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE penyakit ADD CONSTRAINT chk_penyakit_tingkat_bahaya CHECK (tingkat_bahaya IN ('Sangat Tinggi', 'Tinggi', 'Sedang', 'Rendah'));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyakit');
    }
};
