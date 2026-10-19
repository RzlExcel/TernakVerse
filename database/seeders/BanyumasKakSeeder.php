<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Komoditas;
use App\Models\KategoriProduksi;
use App\Models\Penyakit;
use App\Models\PopulasiTernak;
use App\Models\ProduksiTernak;
use App\Models\KasusPenyakit;
use App\Models\ZonaSnapshotKecamatan;
use App\Models\ZonaSnapshotDesa;

class BanyumasKakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi Data Tabular Aktual 27 Kecamatan Berdasarkan KAK Banyumas 2026 (PRD Subbab 7.4):
     * - Sapi Potong: 5.271 Jantan + 7.947 Betina = 13.218 Ekor
     * - Sapi Perah: 172 Jantan + 2.485 Betina = 2.657 Ekor
     * - Kasus PMK: 160 Kasus
     * - Kasus LSD: 140 Kasus
     * Serta data realistis komoditas Kambing, Domba, Ayam Potong, Ayam Telur, Produksi (Daging, Susu, Telur),
     * dan Snapshot Zonasi EWS.
     */
    public function run(): void
    {
        $tahun = 2026;
        $triwulan = 3; // Periode acuan KAK September 2026

        // Ambil ID Komoditas
        $komSapiPotong = Komoditas::where('kode', 'SAPI_POTONG')->firstOrFail()->id;
        $komSapiPerah  = Komoditas::where('kode', 'SAPI_PERAH')->firstOrFail()->id;
        $komKambing    = Komoditas::where('kode', 'KAMBING')->firstOrFail()->id;
        $komDomba      = Komoditas::where('kode', 'DOMBA')->firstOrFail()->id;
        $komAyamPotong = Komoditas::where('kode', 'AYAM_POTONG')->firstOrFail()->id;
        $komAyamTelur  = Komoditas::where('kode', 'AYAM_TELUR')->firstOrFail()->id;

        // Ambil ID Kategori Produksi
        $katDaging = KategoriProduksi::where('nama', 'Daging')->firstOrFail()->id;
        $katSusu   = KategoriProduksi::where('nama', 'Susu')->firstOrFail()->id;
        $katTelur  = KategoriProduksi::where('nama', 'Telur')->firstOrFail()->id;

        // Ambil ID Penyakit
        $pykPmk = Penyakit::where('kode', 'PMK')->firstOrFail()->id;
        $pykLsd = Penyakit::where('kode', 'LSD')->firstOrFail()->id;

        // Tabel Data Aktual 27 Kecamatan Sesuai KAK Banyumas 2026 (PRD Subbab 7.4)
        $kakData = [
            ['nama' => 'Lumbir',             'sp_j' => 133, 'sp_b' => 267,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 5,  'lsd' => 1,  'status' => 'MERAH'],
            ['nama' => 'Wangon',             'sp_j' => 173, 'sp_b' => 297,   'sper_j' => 1,   'sper_b' => 10,   'pmk' => 19, 'lsd' => 14, 'status' => 'MERAH'],
            ['nama' => 'Jatilawang',         'sp_j' => 189, 'sp_b' => 228,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 1,  'status' => 'KUNING'],
            ['nama' => 'Rawalo',             'sp_j' => 67,  'sp_b' => 107,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
            ['nama' => 'Kebasen',            'sp_j' => 80,  'sp_b' => 177,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
            ['nama' => 'Kemranjen',          'sp_j' => 123, 'sp_b' => 92,    'sper_j' => 0,   'sper_b' => 0,    'pmk' => 5,  'lsd' => 7,  'status' => 'MERAH'],
            ['nama' => 'Sumpiuh',            'sp_j' => 61,  'sp_b' => 75,    'sper_j' => 0,   'sper_b' => 0,    'pmk' => 2,  'lsd' => 12, 'status' => 'MERAH'],
            ['nama' => 'Tambak',             'sp_j' => 78,  'sp_b' => 116,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 3,  'lsd' => 9,  'status' => 'MERAH'],
            ['nama' => 'Somagede',           'sp_j' => 366, 'sp_b' => 360,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 8,  'lsd' => 34, 'status' => 'MERAH'],
            ['nama' => 'Kalibagor',          'sp_j' => 790, 'sp_b' => 1107,  'sper_j' => 0,   'sper_b' => 0,    'pmk' => 5,  'lsd' => 11, 'status' => 'MERAH'],
            ['nama' => 'Banyumas',           'sp_j' => 157, 'sp_b' => 216,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 7,  'lsd' => 2,  'status' => 'MERAH'],
            ['nama' => 'Patikraja',          'sp_j' => 156, 'sp_b' => 125,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 3,  'lsd' => 2,  'status' => 'KUNING'],
            ['nama' => 'Purwojati',          'sp_j' => 67,  'sp_b' => 165,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 1,  'lsd' => 4,  'status' => 'KUNING'],
            ['nama' => 'Ajibarang',          'sp_j' => 161, 'sp_b' => 264,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 4,  'lsd' => 17, 'status' => 'MERAH'],
            ['nama' => 'Gumelar',            'sp_j' => 111, 'sp_b' => 107,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
            ['nama' => 'Pekuncen',           'sp_j' => 125, 'sp_b' => 80,    'sper_j' => 18,  'sper_b' => 485,  'pmk' => 0,  'lsd' => 4,  'status' => 'KUNING'],
            ['nama' => 'Cilongok',           'sp_j' => 240, 'sp_b' => 214,   'sper_j' => 15,  'sper_b' => 334,  'pmk' => 2,  'lsd' => 2,  'status' => 'KUNING'],
            ['nama' => 'Karanglewas',        'sp_j' => 144, 'sp_b' => 74,    'sper_j' => 8,   'sper_b' => 98,   'pmk' => 3,  'lsd' => 3,  'status' => 'MERAH'],
            ['nama' => 'Kedungbanteng',      'sp_j' => 188, 'sp_b' => 310,   'sper_j' => 3,   'sper_b' => 60,   'pmk' => 0,  'lsd' => 1,  'status' => 'KUNING'],
            ['nama' => 'Baturraden',         'sp_j' => 270, 'sp_b' => 319,   'sper_j' => 111, 'sper_b' => 1342, 'pmk' => 11, 'lsd' => 0,  'status' => 'MERAH'],
            ['nama' => 'Sumbang',            'sp_j' => 697, 'sp_b' => 1671,  'sper_j' => 13,  'sper_b' => 145,  'pmk' => 70, 'lsd' => 12, 'status' => 'MERAH'],
            ['nama' => 'Kembaran',           'sp_j' => 465, 'sp_b' => 1287,  'sper_j' => 0,   'sper_b' => 0,    'pmk' => 3,  'lsd' => 1,  'status' => 'KUNING'],
            ['nama' => 'Sokaraja',           'sp_j' => 412, 'sp_b' => 264,   'sper_j' => 0,   'sper_b' => 0,    'pmk' => 9,  'lsd' => 3,  'status' => 'MERAH'],
            ['nama' => 'Purwokerto Selatan', 'sp_j' => 0,   'sp_b' => 6,     'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
            ['nama' => 'Purwokerto Barat',   'sp_j' => 0,   'sp_b' => 6,     'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
            ['nama' => 'Purwokerto Timur',   'sp_j' => 9,   'sp_b' => 7,     'sper_j' => 0,   'sper_b' => 0,    'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
            ['nama' => 'Purwokerto Utara',   'sp_j' => 9,   'sp_b' => 6,     'sper_j' => 3,   'sper_b' => 11,   'pmk' => 0,  'lsd' => 0,  'status' => 'HIJAU'],
        ];

        // Indeks pengali populasi realistis untuk komoditas sekunder per kecamatan
        $secondaryBase = [
            'Lumbir'             => ['kambing' => 850,  'domba' => 320, 'ayam_p' => 12000, 'ayam_t' => 3500],
            'Wangon'             => ['kambing' => 1200, 'domba' => 450, 'ayam_p' => 25000, 'ayam_t' => 8000],
            'Jatilawang'         => ['kambing' => 950,  'domba' => 280, 'ayam_p' => 15000, 'ayam_t' => 4000],
            'Rawalo'             => ['kambing' => 720,  'domba' => 210, 'ayam_p' => 9500,  'ayam_t' => 2500],
            'Kebasen'            => ['kambing' => 810,  'domba' => 260, 'ayam_p' => 11000, 'ayam_t' => 3000],
            'Kemranjen'          => ['kambing' => 1100, 'domba' => 390, 'ayam_p' => 18000, 'ayam_t' => 6000],
            'Sumpiuh'            => ['kambing' => 890,  'domba' => 310, 'ayam_p' => 14000, 'ayam_t' => 5000],
            'Tambak'             => ['kambing' => 760,  'domba' => 240, 'ayam_p' => 10500, 'ayam_t' => 3200],
            'Somagede'           => ['kambing' => 1400, 'domba' => 520, 'ayam_p' => 22000, 'ayam_t' => 7500],
            'Kalibagor'          => ['kambing' => 1650, 'domba' => 610, 'ayam_p' => 32000, 'ayam_t' => 12000],
            'Banyumas'           => ['kambing' => 920,  'domba' => 300, 'ayam_p' => 13500, 'ayam_t' => 4500],
            'Patikraja'          => ['kambing' => 880,  'domba' => 290, 'ayam_p' => 12500, 'ayam_t' => 4200],
            'Purwojati'          => ['kambing' => 790,  'domba' => 250, 'ayam_p' => 10000, 'ayam_t' => 3100],
            'Ajibarang'          => ['kambing' => 1350, 'domba' => 480, 'ayam_p' => 28000, 'ayam_t' => 9000],
            'Gumelar'            => ['kambing' => 830,  'domba' => 270, 'ayam_p' => 11500, 'ayam_t' => 3400],
            'Pekuncen'           => ['kambing' => 1500, 'domba' => 550, 'ayam_p' => 26000, 'ayam_t' => 8500],
            'Cilongok'           => ['kambing' => 1800, 'domba' => 700, 'ayam_p' => 35000, 'ayam_t' => 14000],
            'Karanglewas'        => ['kambing' => 980,  'domba' => 340, 'ayam_p' => 16000, 'ayam_t' => 5500],
            'Kedungbanteng'      => ['kambing' => 1250, 'domba' => 460, 'ayam_p' => 20000, 'ayam_t' => 7000],
            'Baturraden'         => ['kambing' => 1100, 'domba' => 380, 'ayam_p' => 19000, 'ayam_t' => 6500],
            'Sumbang'            => ['kambing' => 1950, 'domba' => 750, 'ayam_p' => 42000, 'ayam_t' => 18000],
            'Kembaran'           => ['kambing' => 1450, 'domba' => 510, 'ayam_p' => 30000, 'ayam_t' => 11000],
            'Sokaraja'           => ['kambing' => 1300, 'domba' => 470, 'ayam_p' => 24000, 'ayam_t' => 8000],
            'Purwokerto Selatan' => ['kambing' => 150,  'domba' => 40,  'ayam_p' => 2000,  'ayam_t' => 800],
            'Purwokerto Barat'   => ['kambing' => 120,  'domba' => 30,  'ayam_p' => 1500,  'ayam_t' => 600],
            'Purwokerto Timur'   => ['kambing' => 180,  'domba' => 50,  'ayam_p' => 2500,  'ayam_t' => 900],
            'Purwokerto Utara'   => ['kambing' => 210,  'domba' => 60,  'ayam_p' => 3000,  'ayam_t' => 1100],
        ];

        foreach ($kakData as $row) {
            $kec = Kecamatan::where('nama_kecamatan', 'ILIKE', '%' . $row['nama'] . '%')->first();
            if (!$kec) {
                continue;
            }

            $desaList = Desa::where('kecamatan_id', $kec->id)->get();
            if ($desaList->isEmpty()) {
                continue;
            }

            $numDesa = $desaList->count();
            $firstDesa = $desaList->first();

            // 1. Distribusi Sapi Potong (Total Jantan & Betina Presisi KAK)
            $this->distributePopulasi($desaList, $komSapiPotong, $tahun, $triwulan, $row['sp_j'], $row['sp_b']);

            // 2. Distribusi Sapi Perah (Total Jantan & Betina Presisi KAK)
            $this->distributePopulasi($desaList, $komSapiPerah, $tahun, $triwulan, $row['sper_j'], $row['sper_b']);

            // 3. Distribusi Komoditas Sekunder (Kambing, Domba, Ayam Potong, Ayam Telur)
            $sec = $secondaryBase[$row['nama']] ?? ['kambing' => 500, 'domba' => 200, 'ayam_p' => 8000, 'ayam_t' => 2500];
            $kambingJ = (int) round($sec['kambing'] * 0.35);
            $kambingB = $sec['kambing'] - $kambingJ;
            $this->distributePopulasi($desaList, $komKambing, $tahun, $triwulan, $kambingJ, $kambingB);

            $dombaJ = (int) round($sec['domba'] * 0.35);
            $dombaB = $sec['domba'] - $dombaJ;
            $this->distributePopulasi($desaList, $komDomba, $tahun, $triwulan, $dombaJ, $dombaB);

            $ayamPJ = (int) round($sec['ayam_p'] * 0.45);
            $ayamPB = $sec['ayam_p'] - $ayamPJ;
            $this->distributePopulasi($desaList, $komAyamPotong, $tahun, $triwulan, $ayamPJ, $ayamPB);

            $ayamTJ = (int) round($sec['ayam_t'] * 0.05); // Layer dominan betina
            $ayamTB = $sec['ayam_t'] - $ayamTJ;
            $this->distributePopulasi($desaList, $komAyamTelur, $tahun, $triwulan, $ayamTJ, $ayamTB);

            // 4. Distribusi Kasus PMK & LSD (Presisi KAK Banyumas)
            $this->distributeKasus($desaList, $pykPmk, $komSapiPotong, $tahun, $triwulan, $row['pmk']);
            $this->distributeKasus($desaList, $pykLsd, $komSapiPotong, $tahun, $triwulan, $row['lsd']);

            // 5. Input Produksi Ternak Realistis (Daging, Susu, Telur)
            $this->generateProduksi($firstDesa, [
                // Daging dari Sapi Potong (estimasi kg)
                ['kom_id' => $komSapiPotong, 'kat_id' => $katDaging, 'jumlah' => round(($row['sp_j'] + $row['sp_b']) * 12.5, 2)],
                // Daging dari Kambing
                ['kom_id' => $komKambing,    'kat_id' => $katDaging, 'jumlah' => round($sec['kambing'] * 2.1, 2)],
                // Daging dari Ayam Potong
                ['kom_id' => $komAyamPotong, 'kat_id' => $katDaging, 'jumlah' => round($sec['ayam_p'] * 1.3, 2)],
                // Susu dari Sapi Perah (liter per triwulan)
                ['kom_id' => $komSapiPerah,  'kat_id' => $katSusu,   'jumlah' => round($row['sper_b'] * 120.0, 2)],
                // Telur dari Ayam Telur (kg per triwulan)
                ['kom_id' => $komAyamTelur,  'kat_id' => $katTelur,  'jumlah' => round($sec['ayam_t'] * 3.8, 2)],
            ], $tahun, $triwulan);

            // 6. Snapshot Zonasi Kecamatan (PMK & LSD)
            $statusPmk = $row['pmk'] == 0 ? 'HIJAU' : ($row['pmk'] <= 5 ? 'KUNING' : 'MERAH');
            $statusLsd = $row['lsd'] == 0 ? 'HIJAU' : ($row['lsd'] <= 5 ? 'KUNING' : 'MERAH');

            ZonaSnapshotKecamatan::updateOrCreate(
                ['kecamatan_id' => $kec->id, 'penyakit_id' => $pykPmk, 'tahun' => $tahun, 'triwulan' => $triwulan],
                ['status_zona' => $statusPmk, 'total_kasus_aktif' => $row['pmk'], 'waktu_kalkulasi' => now()]
            );

            ZonaSnapshotKecamatan::updateOrCreate(
                ['kecamatan_id' => $kec->id, 'penyakit_id' => $pykLsd, 'tahun' => $tahun, 'triwulan' => $triwulan],
                ['status_zona' => $statusLsd, 'total_kasus_aktif' => $row['lsd'], 'waktu_kalkulasi' => now()]
            );
        }
    }

    /**
     * Membagi total populasi kecamatan ke desa-desa binaan sehingga SUM(desa) == Total Kecamatan
     */
    protected function distributePopulasi($desaList, int $komoditasId, int $tahun, int $triwulan, int $totJantan, int $totBetina): void
    {
        $count = $desaList->count();
        if ($count === 0) return;

        // Hitung pembagian rata
        $baseJ = (int) floor($totJantan / $count);
        $remJ  = $totJantan % $count;

        $baseB = (int) floor($totBetina / $count);
        $remB  = $totBetina % $count;

        foreach ($desaList as $idx => $desa) {
            $j = $baseJ + ($idx < $remJ ? 1 : 0);
            $b = $baseB + ($idx < $remB ? 1 : 0);

            PopulasiTernak::updateOrCreate(
                [
                    'desa_id' => $desa->id,
                    'komoditas_id' => $komoditasId,
                    'tahun' => $tahun,
                    'triwulan' => $triwulan,
                ],
                [
                    'jumlah_jantan' => $j,
                    'jumlah_betina' => $b,
                ]
            );
        }
    }

    /**
     * Membagi kasus penyakit ke desa-desa terpilih sehingga SUM(kasus_positif) == Total Kecamatan
     */
    protected function distributeKasus($desaList, int $penyakitId, int $komoditasId, int $tahun, int $triwulan, int $totalKasus): void
    {
        if ($totalKasus <= 0 || $desaList->isEmpty()) return;

        // Sebarkan ke maksimal 3 desa teratas
        $targetDesas = $desaList->take(min(3, $desaList->count()));
        $kCount = $targetDesas->count();

        $baseKasus = (int) floor($totalKasus / $kCount);
        $remKasus  = $totalKasus % $kCount;

        foreach ($targetDesas as $idx => $desa) {
            $kasus = $baseKasus + ($idx < $remKasus ? 1 : 0);
            if ($kasus <= 0) continue;

            KasusPenyakit::create([
                'desa_id' => $desa->id,
                'penyakit_id' => $penyakitId,
                'komoditas_id' => $komoditasId,
                'tanggal_laporan' => "{$tahun}-09-15",
                'tahun' => $tahun,
                'triwulan' => $triwulan,
                'kasus_suspek' => 0,
                'kasus_positif' => $kasus,
                'kasus_sembuh' => 0,
                'kasus_mati' => 0,
                'potong_bersyarat' => 0,
                'catatan_lapangan' => 'Laporan surveilans aktif dinas peternakan semester 2',
            ]);
        }
    }

    /**
     * Memasukkan estimasi produksi komoditas pada desa sentra
     */
    protected function generateProduksi(Desa $desa, array $items, int $tahun, int $triwulan): void
    {
        foreach ($items as $item) {
            if ($item['jumlah'] <= 0) continue;

            ProduksiTernak::updateOrCreate(
                [
                    'desa_id' => $desa->id,
                    'komoditas_id' => $item['kom_id'],
                    'kategori_produksi_id' => $item['kat_id'],
                    'tahun' => $tahun,
                    'triwulan' => $triwulan,
                ],
                [
                    'jumlah_produksi' => $item['jumlah'],
                ]
            );
        }
    }
}
