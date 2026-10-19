<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Komoditas;

class MasterKomoditasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi 6 Komoditas Utama Peternakan Kabupaten Banyumas (Unggas, Ternak Kecil, Ternak Besar).
     */
    public function run(): void
    {
        $komoditasList = [
            // 1. Unggas
            [
                'kode' => 'AYAM_POTONG',
                'nama' => 'Ayam Ras Pedaging / Broiler',
                'kelompok' => 'Unggas',
                'aktif' => true,
            ],
            [
                'kode' => 'AYAM_TELUR',
                'nama' => 'Ayam Ras Petelur / Layer',
                'kelompok' => 'Unggas',
                'aktif' => true,
            ],

            // 2. Ternak Kecil
            [
                'kode' => 'KAMBING',
                'nama' => 'Kambing',
                'kelompok' => 'Ternak Kecil',
                'aktif' => true,
            ],
            [
                'kode' => 'DOMBA',
                'nama' => 'Domba',
                'kelompok' => 'Ternak Kecil',
                'aktif' => true,
            ],

            // 3. Ternak Besar
            [
                'kode' => 'SAPI_POTONG',
                'nama' => 'Sapi Potong',
                'kelompok' => 'Ternak Besar',
                'aktif' => true,
            ],
            [
                'kode' => 'SAPI_PERAH',
                'nama' => 'Sapi Perah',
                'kelompok' => 'Ternak Besar',
                'aktif' => true,
            ],
        ];

        foreach ($komoditasList as $item) {
            Komoditas::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'kelompok' => $item['kelompok'],
                    'aktif' => $item['aktif'],
                ]
            );
        }
    }
}
