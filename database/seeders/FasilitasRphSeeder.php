<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\FasilitasRph;

class FasilitasRphSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi Titik Geospasial Fasilitas Rumah Potong Hewan (RPH) di Kabupaten Banyumas (SRID 4326).
     */
    public function run(): void
    {
        $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));

        $rphList = [
            [
                'nama_kecamatan' => 'Sumbang',
                'nama_rph' => 'RPH Tambaksogra',
                'alamat_lengkap' => 'Jl. Raya Tambaksogra No. 12, Kecamatan Sumbang, Kabupaten Banyumas',
                'jenis_pengelola' => 'Pemerintah',
                'jenis_hewan_potong' => 'Sapi, Kerbau, Kambing',
                'kapasitas_potong_ekor_hari' => 25,
                'status_operasional' => 'Aktif',
                'lng' => 109.2612,
                'lat' => -7.3821,
                'nomor_kontak' => '(0281) 684123',
            ],
            [
                'nama_kecamatan' => 'Sumpiuh',
                'nama_rph' => 'RPH Kradenan Sumpiuh',
                'alamat_lengkap' => 'Jl. Stasiun Kradenan, Kecamatan Sumpiuh, Kabupaten Banyumas',
                'jenis_pengelola' => 'Pemerintah',
                'jenis_hewan_potong' => 'Sapi, Kambing',
                'kapasitas_potong_ekor_hari' => 15,
                'status_operasional' => 'Aktif',
                'lng' => 109.3582,
                'lat' => -7.6124,
                'nomor_kontak' => '(0281) 689432',
            ],
            [
                'nama_kecamatan' => 'Wangon',
                'nama_rph' => 'RPH Wangon',
                'alamat_lengkap' => 'Jl. Raya Wangon-Ajibarang Km 2, Kecamatan Wangon, Kabupaten Banyumas',
                'jenis_pengelola' => 'Pemerintah',
                'jenis_hewan_potong' => 'Sapi, Kambing',
                'kapasitas_potong_ekor_hari' => 15,
                'status_operasional' => 'Aktif',
                'lng' => 109.0521,
                'lat' => -7.5187,
                'nomor_kontak' => '(0281) 657190',
            ],
            [
                'nama_kecamatan' => 'Sokaraja',
                'nama_rph' => 'RPH Sokaraja Lor',
                'alamat_lengkap' => 'Jl. Suparjo Rustam No. 45, Kecamatan Sokaraja, Kabupaten Banyumas',
                'jenis_pengelola' => 'Pemerintah',
                'jenis_hewan_potong' => 'Sapi, Kambing',
                'kapasitas_potong_ekor_hari' => 20,
                'status_operasional' => 'Aktif',
                'lng' => 109.2645,
                'lat' => -7.4562,
                'nomor_kontak' => '(0281) 644201',
            ],
            [
                'nama_kecamatan' => 'Ajibarang',
                'nama_rph' => 'RPH Mitra Mandiri Ajibarang',
                'alamat_lengkap' => 'Jl. Pancasan No. 8, Kecamatan Ajibarang, Kabupaten Banyumas',
                'jenis_pengelola' => 'Swasta',
                'jenis_hewan_potong' => 'Sapi, Kambing',
                'kapasitas_potong_ekor_hari' => 10,
                'status_operasional' => 'Aktif',
                'lng' => 109.0732,
                'lat' => -7.4125,
                'nomor_kontak' => '081391234567',
            ],
        ];

        foreach ($rphList as $rph) {
            $kec = Kecamatan::where('nama_kecamatan', 'ILIKE', '%' . $rph['nama_kecamatan'] . '%')->first();
            if (!$kec) {
                continue;
            }

            $pointJson = json_encode([
                'type' => 'Point',
                'coordinates' => [$rph['lng'], $rph['lat']],
            ]);

            $existing = FasilitasRph::where('nama_rph', $rph['nama_rph'])->first();

            if ($hasPostgis) {
                if ($existing) {
                    DB::statement("
                        UPDATE fasilitas_rph 
                        SET kecamatan_id = ?,
                            alamat_lengkap = ?,
                            jenis_pengelola = ?,
                            jenis_hewan_potong = ?,
                            kapasitas_potong_ekor_hari = ?,
                            status_operasional = ?,
                            geom_titik = ST_SetSRID(ST_MakePoint(?, ?), 4326),
                            nomor_kontak = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [
                        $kec->id,
                        $rph['alamat_lengkap'],
                        $rph['jenis_pengelola'],
                        $rph['jenis_hewan_potong'],
                        $rph['kapasitas_potong_ekor_hari'],
                        $rph['status_operasional'],
                        $rph['lng'],
                        $rph['lat'],
                        $rph['nomor_kontak'],
                        $existing->id,
                    ]);
                } else {
                    DB::statement("
                        INSERT INTO fasilitas_rph (
                            kecamatan_id, nama_rph, alamat_lengkap, jenis_pengelola,
                            jenis_hewan_potong, kapasitas_potong_ekor_hari, status_operasional,
                            geom_titik, nomor_kontak, created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?,
                            ?, ?, ?,
                            ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, NOW(), NOW()
                        )
                    ", [
                        $kec->id,
                        $rph['nama_rph'],
                        $rph['alamat_lengkap'],
                        $rph['jenis_pengelola'],
                        $rph['jenis_hewan_potong'],
                        $rph['kapasitas_potong_ekor_hari'],
                        $rph['status_operasional'],
                        $rph['lng'],
                        $rph['lat'],
                        $rph['nomor_kontak'],
                    ]);
                }
            } else {
                FasilitasRph::updateOrCreate(
                    ['nama_rph' => $rph['nama_rph']],
                    [
                        'kecamatan_id' => $kec->id,
                        'alamat_lengkap' => $rph['alamat_lengkap'],
                        'jenis_pengelola' => $rph['jenis_pengelola'],
                        'jenis_hewan_potong' => $rph['jenis_hewan_potong'],
                        'kapasitas_potong_ekor_hari' => $rph['kapasitas_potong_ekor_hari'],
                        'status_operasional' => $rph['status_operasional'],
                        'geom_titik' => [
                            'type' => 'Point',
                            'coordinates' => [$rph['lng'], $rph['lat']],
                        ],
                        'nomor_kontak' => $rph['nomor_kontak'],
                    ]
                );
            }
        }
    }
}
