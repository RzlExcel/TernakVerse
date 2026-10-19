<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penyakit;
use App\Models\ParameterZonasi;

class MasterPenyakitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi Master Penyakit Menular Hewan (PMK & LSD) dan Parameter Ambang Batas Zonasi EWS.
     */
    public function run(): void
    {
        $penyakitList = [
            [
                'kode' => 'PMK',
                'nama' => 'Penyakit Mulut dan Kuku',
                'nama_latin' => 'Aphtae Epizooticae',
                'tingkat_bahaya' => 'Tinggi',
                'fcode' => 'CA0010',
                'ambang_kuning_maks' => 5, // 0 = Hijau, 1-5 = Kuning, >5 = Merah
                'jendela_hari_aktif' => 14,
            ],
            [
                'kode' => 'LSD',
                'nama' => 'Lumpy Skin Disease',
                'nama_latin' => 'Lumpy Skin Disease Virus',
                'tingkat_bahaya' => 'Sedang',
                'fcode' => 'CA0010',
                'ambang_kuning_maks' => 5, // 0 = Hijau, 1-5 = Kuning, >5 = Merah
                'jendela_hari_aktif' => 14,
            ],
        ];

        foreach ($penyakitList as $item) {
            $penyakit = Penyakit::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'nama_latin' => $item['nama_latin'],
                    'tingkat_bahaya' => $item['tingkat_bahaya'],
                    'fcode' => $item['fcode'],
                ]
            );

            // Simpan / update parameter zonasi EWS terkait penyakit ini
            ParameterZonasi::updateOrCreate(
                ['penyakit_id' => $penyakit->id],
                [
                    'jendela_hari_aktif' => $item['jendela_hari_aktif'],
                    'ambang_kuning_maks' => $item['ambang_kuning_maks'],
                ]
            );
        }
    }
}
