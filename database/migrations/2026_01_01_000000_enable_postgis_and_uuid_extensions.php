<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengaktifkan ekstensi geospasial PostGIS dan UUID generator di PostgreSQL.
     */
    public function up(): void
    {
        // 1. Ekstensi UUID generator
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // 2. Ekstensi Geospasial PostGIS (dengan deteksi ketersediaan modul binary di PostgreSQL server)
        $available = DB::select("SELECT 1 FROM pg_available_extensions WHERE name = 'postgis'");
        
        if (!empty($available)) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
        } else {
            echo "\n\033[33m[GIS ARCHITECT NOTICE]\033[0m Modul binary 'postgis' belum terpasang di PostgreSQL.\n";
            echo "-> Migrasi akan menggunakan mode adaptif (JSONB GeoJSON) untuk lingkungan dev lokal.\n";
            echo "-> Setelah menginstal PostGIS di server, jalankan migrate:fresh untuk spatial index GiST.\n\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp";');
        try {
            DB::statement('DROP EXTENSION IF EXISTS postgis CASCADE;');
        } catch (\Throwable $e) {}
    }
};
