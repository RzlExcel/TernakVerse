<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriProduksi;

class MasterProduksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi Kategori Produksi Peternakan (Daging, Susu, Telur).
     */
    public function run(): void
    {
        $kategoriList = [
            [
                'nama' => 'Daging',
                'satuan' => 'kg',
            ],
            [
                'nama' => 'Susu',
                'satuan' => 'liter',
            ],
            [
                'nama' => 'Telur',
                'satuan' => 'butir/kg',
            ],
        ];

        foreach ($kategoriList as $item) {
            KategoriProduksi::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'satuan' => $item['satuan'],
                ]
            );
        }
    }
}
