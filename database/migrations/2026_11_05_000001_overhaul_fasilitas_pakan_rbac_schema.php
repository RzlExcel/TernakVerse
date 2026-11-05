<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fasilitas_rph', function (Blueprint $table) {
            if (!Schema::hasColumn('fasilitas_rph', 'tipe_fasilitas')) {
                $table->string('tipe_fasilitas', 30)->default('RPH');
            }
            if (!Schema::hasColumn('fasilitas_rph', 'hari_pasaran')) {
                $table->string('hari_pasaran', 100)->nullable();
            }
            if (!Schema::hasColumn('fasilitas_rph', 'wilayah_binaan')) {
                $table->text('wilayah_binaan')->nullable();
            }
            if (!Schema::hasColumn('fasilitas_rph', 'penanggung_jawab')) {
                $table->string('penanggung_jawab', 150)->nullable();
            }
            $table->string('jenis_hewan_potong', 100)->nullable()->change();

            if (DB::getDriverName() !== 'pgsql') {
                $table->index('tipe_fasilitas', 'idx_fasilitas_tipe');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_fasilitas_tipe ON fasilitas_rph (tipe_fasilitas);');
        }

        if (!Schema::hasColumn('kecamatan', 'luas_hmt_ha')) {
            Schema::table('kecamatan', function (Blueprint $table) {
                $table->decimal('luas_hmt_ha', 10, 2)->default(0.00);
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fasilitas_rph DROP CONSTRAINT IF EXISTS chk_rph_pengelola;');
            DB::statement('ALTER TABLE fasilitas_rph DROP CONSTRAINT IF EXISTS chk_fasilitas_tipe;');
            DB::statement("ALTER TABLE fasilitas_rph ADD CONSTRAINT chk_fasilitas_tipe CHECK (tipe_fasilitas IN ('RPH', 'Puskeswan', 'Pasar Hewan'));");

            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role;');
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('superadmin', 'admin_dinas', 'admin', 'stakeholder'));");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role;');
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('admin', 'stakeholder'));");

            DB::statement('ALTER TABLE fasilitas_rph DROP CONSTRAINT IF EXISTS chk_fasilitas_tipe;');
            DB::statement("ALTER TABLE fasilitas_rph ADD CONSTRAINT chk_rph_pengelola CHECK (jenis_pengelola IN ('Pemerintah', 'Swasta', 'Kelompok Tani'));");
        }

        Schema::table('kecamatan', function (Blueprint $table) {
            $table->dropColumn('luas_hmt_ha');
        });

        Schema::table('fasilitas_rph', function (Blueprint $table) {
            $table->dropIndex('idx_fasilitas_tipe');
            $table->dropColumn([
                'tipe_fasilitas',
                'hari_pasaran',
                'wilayah_binaan',
                'penanggung_jawab',
            ]);
            $table->string('jenis_hewan_potong', 100)->nullable(false)->change();
        });
    }
};
