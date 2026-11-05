<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('populasi_ternak') && ! Schema::hasIndex('populasi_ternak', 'idx_populasi_perf')) {
            Schema::table('populasi_ternak', function (Blueprint $table) {
                $table->index(['tahun', 'triwulan', 'komoditas_id'], 'idx_populasi_perf');
            });
        }

        if (Schema::hasTable('produksi_ternak') && ! Schema::hasIndex('produksi_ternak', 'idx_produksi_perf')) {
            Schema::table('produksi_ternak', function (Blueprint $table) {
                $table->index(['tahun', 'triwulan', 'kategori_produksi_id'], 'idx_produksi_perf');
            });
        }

        if (Schema::hasTable('kasus_penyakit') && ! Schema::hasIndex('kasus_penyakit', 'idx_kasus_perf')) {
            Schema::table('kasus_penyakit', function (Blueprint $table) {
                $table->index(['tahun', 'triwulan', 'kasus_aktif'], 'idx_kasus_perf');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('populasi_ternak') && Schema::hasIndex('populasi_ternak', 'idx_populasi_perf')) {
            Schema::table('populasi_ternak', function (Blueprint $table) {
                $table->dropIndex('idx_populasi_perf');
            });
        }

        if (Schema::hasTable('produksi_ternak') && Schema::hasIndex('produksi_ternak', 'idx_produksi_perf')) {
            Schema::table('produksi_ternak', function (Blueprint $table) {
                $table->dropIndex('idx_produksi_perf');
            });
        }

        if (Schema::hasTable('kasus_penyakit') && Schema::hasIndex('kasus_penyakit', 'idx_kasus_perf')) {
            Schema::table('kasus_penyakit', function (Blueprint $table) {
                $table->dropIndex('idx_kasus_perf');
            });
        }
    }
};
