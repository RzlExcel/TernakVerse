<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stored Functions, Otomasi Triggers, dan PostGIS Spatial Views sesuai Standar KUGI:
     * - ADMINISTRASI_AR_PETERNAKAN
     * - ADMINISTRASI_AR_PENYAKITHEWAN
     */
    public function up(): void
    {
        // 1. Function Pembaruan Kolom updated_at Otomatis di PostgreSQL
        DB::statement('
            CREATE OR REPLACE FUNCTION update_timestamp_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Pasang Trigger pada tabel-tabel utama
        DB::statement('CREATE OR REPLACE TRIGGER trg_kecamatan_updated BEFORE UPDATE ON kecamatan FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();');
        DB::statement('CREATE OR REPLACE TRIGGER trg_desa_updated BEFORE UPDATE ON desa FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();');
        DB::statement('CREATE OR REPLACE TRIGGER trg_populasi_updated BEFORE UPDATE ON populasi_ternak FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();');
        DB::statement('CREATE OR REPLACE TRIGGER trg_produksi_updated BEFORE UPDATE ON produksi_ternak FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();');
        DB::statement('CREATE OR REPLACE TRIGGER trg_kasus_updated BEFORE UPDATE ON kasus_penyakit FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();');

        // 2. View Sesuai Standar KUGI Unsur ADMINISTRASI_AR_PETERNAKAN
        DB::statement('
            CREATE OR REPLACE VIEW v_kugi_administrasi_ar_peternakan AS
            SELECT 
                k.id AS objectid,
                k.nama_kecamatan AS wadmkc,
                REPLACE(k.kode_kecamatan, \'.\', \'\')::INTEGER AS kdcpum,
                k.nama_kecamatan AS namobj,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'SAPI_POTONG\'), 0) AS pop_sapipotong,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'SAPI_PERAH\'), 0) AS pop_sapiperah,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kelompok = \'Ternak Besar\'), 0) AS pop_total,
                k.fcode AS fcode,
                k.metadata_id AS metadata,
                \'Polygon\' AS shape,
                k.srs_id AS srs_id,
                k.remark AS remark,
                k.geom AS geom
            FROM kecamatan k
            LEFT JOIN desa d ON d.kecamatan_id = k.id
            LEFT JOIN populasi_ternak p ON p.desa_id = d.id AND p.tahun = EXTRACT(YEAR FROM CURRENT_DATE)::SMALLINT
            LEFT JOIN komoditas kom ON kom.id = p.komoditas_id
            GROUP BY k.id, k.nama_kecamatan, k.kode_kecamatan, k.fcode, k.metadata_id, k.srs_id, k.remark, k.geom;
        ');

        // 3. View Sesuai Standar KUGI Unsur ADMINISTRASI_AR_PENYAKITHEWAN
        DB::statement('
            CREATE OR REPLACE VIEW v_kugi_administrasi_ar_penyakithewan AS
            SELECT 
                k.id AS objectid,
                k.nama_kecamatan AS wadmkc,
                REPLACE(k.kode_kecamatan, \'.\', \'\')::INTEGER AS kdcpum,
                k.nama_kecamatan AS namobj,
                COALESCE(SUM(kp.kasus_positif) FILTER (WHERE pyk.kode = \'PMK\'), 0) AS kasus_pmk,
                COALESCE(SUM(kp.kasus_positif) FILTER (WHERE pyk.kode = \'LSD\'), 0) AS kasus_lsd,
                COALESCE(SUM(kp.kasus_positif), 0) AS kasus_total,
                k.fcode AS fcode,
                k.metadata_id AS metadata,
                \'Polygon\' AS shape,
                k.srs_id AS srs_id,
                k.remark AS remark,
                k.geom AS geom
            FROM kecamatan k
            LEFT JOIN desa d ON d.kecamatan_id = k.id
            LEFT JOIN kasus_penyakit kp ON kp.desa_id = d.id AND kp.tahun = EXTRACT(YEAR FROM CURRENT_DATE)::SMALLINT
            LEFT JOIN penyakit pyk ON pyk.id = kp.penyakit_id
            GROUP BY k.id, k.nama_kecamatan, k.kode_kecamatan, k.fcode, k.metadata_id, k.srs_id, k.remark, k.geom;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_kugi_administrasi_ar_penyakithewan CASCADE;');
        DB::statement('DROP VIEW IF EXISTS v_kugi_administrasi_ar_peternakan CASCADE;');
        DB::statement('DROP TRIGGER IF EXISTS trg_kasus_updated ON kasus_penyakit;');
        DB::statement('DROP TRIGGER IF EXISTS trg_produksi_updated ON produksi_ternak;');
        DB::statement('DROP TRIGGER IF EXISTS trg_populasi_updated ON populasi_ternak;');
        DB::statement('DROP TRIGGER IF EXISTS trg_desa_updated ON desa;');
        DB::statement('DROP TRIGGER IF EXISTS trg_kecamatan_updated ON kecamatan;');
        DB::statement('DROP FUNCTION IF EXISTS update_timestamp_column();');
    }
};
