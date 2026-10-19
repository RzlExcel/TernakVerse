<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Master Kategori Hasil Produksi Peternakan (Daging, Susu, Telur).
     */
    public function up(): void
    {
        Schema::create('kategori_produksi', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nama', 50)->unique();
            $table->string('satuan', 20); // kg, liter, butir
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_produksi');
    }
};
