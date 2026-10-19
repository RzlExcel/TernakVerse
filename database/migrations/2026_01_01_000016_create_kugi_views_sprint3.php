<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat 2 PostGIS Spatial Views Berstandar KUGI (Katalog Unsur Geografi Indonesia):
     * - v_kugi_administrasi_ar_peternakan (6 Komoditas Lengkap)
     * - v_kugi_administrasi_ar_penyakithewan (PMK & LSD + Kasus Aktif)
     */
    public function up(): void
    {
        // Drop existing views to allow column list restructuring
        DB::statement('DROP VIEW IF EXISTS v_kugi_administrasi_ar_peternakan CASCADE;');
        DB::statement('DROP VIEW IF EXISTS v_kugi_administrasi_ar_penyakithewan CASCADE;');

        // 1. View KUGI: ADMINISTRASI_AR_PETERNAKAN
        DB::statement('
            CREATE OR REPLACE VIEW v_kugi_administrasi_ar_peternakan AS
            SELECT 
                k.id AS objectid,
                k.nama_kecamatan AS wadmkc,
                REPLACE(k.kode_kecamatan, \'.\', \'\')::INTEGER AS kdcpum,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'SAPI_POTONG\'), 0) AS pop_sapipotong,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'SAPI_PERAH\'), 0) AS pop_sapiperah,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'KAMBING\'), 0) AS pop_kambing,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'DOMBA\'), 0) AS pop_domba,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'AYAM_POTONG\'), 0) AS pop_ayampotong,
                COALESCE(SUM(p.jumlah_total) FILTER (WHERE kom.kode = \'AYAM_TELUR\'), 0) AS pop_ayamtelur,
                COALESCE(SUM(p.jumlah_total), 0) AS pop_total,
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

        // 2. View KUGI: ADMINISTRASI_AR_PENYAKITHEWAN
        DB::statement('
            CREATE OR REPLACE VIEW v_kugi_administrasi_ar_penyakithewan AS
            SELECT 
                k.id AS objectid,
                k.nama_kecamatan AS wadmkc,
                REPLACE(k.kode_kecamatan, \'.\', \'\')::INTEGER AS kdcpum,
                COALESCE(SUM(kp.kasus_positif) FILTER (WHERE pyk.kode = \'PMK\'), 0) AS kasus_pmk,
                COALESCE(SUM(kp.kasus_positif) FILTER (WHERE pyk.kode = \'LSD\'), 0) AS kasus_lsd,
                COALESCE(SUM(kp.kasus_positif), 0) AS kasus_total,
                COALESCE(SUM(kp.kasus_aktif), 0) AS kasus_aktif,
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
    }
};
