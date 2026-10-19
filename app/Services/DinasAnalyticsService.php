<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Komoditas;
use App\Models\KategoriProduksi;
use App\Models\Penyakit;
use App\Models\PopulasiTernak;
use App\Models\ProduksiTernak;
use App\Models\KasusPenyakit;
use App\Models\FasilitasRph;
use App\Models\ZonaSnapshotKecamatan;

class DinasAnalyticsService
{
    /**
     * Palet Token Warna Choropleth DESIGN_RULE.md (Cyber-Green & Neon Spectrum)
     */
    public const CHOROPLETH_PALETTE = [
        1 => ['label' => 'Sangat Rendah', 'hex' => '#14535E', 'class' => 'choro-q1'],
        2 => ['label' => 'Rendah',        'hex' => '#0E7490', 'class' => 'choro-q2'],
        3 => ['label' => 'Sedang',        'hex' => '#06B6D4', 'class' => 'choro-q3'],
        4 => ['label' => 'Tinggi',        'hex' => '#19F5D4', 'class' => 'choro-q4'],
        5 => ['label' => 'Sangat Tinggi', 'hex' => '#39FF14', 'class' => 'choro-q5'],
    ];

    /**
     * a. Menghitung Kartu Ringkasan Metrik / KPI Cards Pimpinan Dinas
     */
    public function getSummaryMetrics(int $tahun, ?int $triwulan = null): array
    {
        // 1. Populasi Ternak Berdasarkan Kelompok
        $populasiQuery = PopulasiTernak::query()->where('tahun', $tahun);
        if ($triwulan !== null) {
            $populasiQuery->where('triwulan', $triwulan);
        }

        $populasiByKelompok = (clone $populasiQuery)
            ->join('komoditas', 'komoditas.id', '=', 'populasi_ternak.komoditas_id')
            ->select('komoditas.kelompok', DB::raw('SUM(populasi_ternak.jumlah_total) as total'))
            ->groupBy('komoditas.kelompok')
            ->pluck('total', 'kelompok')
            ->toArray();

        $totalTernakBesar = (int) ($populasiByKelompok['Ternak Besar'] ?? 0);
        $totalTernakKecil = (int) ($populasiByKelompok['Ternak Kecil'] ?? 0);
        $totalUnggas      = (int) ($populasiByKelompok['Unggas'] ?? 0);
        $totalSemuaTernak = $totalTernakBesar + $totalTernakKecil + $totalUnggas;

        // 2. Produksi Ternak Sesuai Ketentuan Bisnis
        // a) Produksi Telur (hanya dari AYAM_TELUR)
        $prodTelurQuery = ProduksiTernak::query()
            ->whereHas('kategoriProduksi', fn($q) => $q->where('nama', 'Telur'))
            ->whereHas('komoditas', fn($q) => $q->where('kode', 'AYAM_TELUR'))
            ->where('tahun', $tahun);
        if ($triwulan !== null) {
            $prodTelurQuery->where('triwulan', $triwulan);
        }
        $totalProduksiTelur = (float) $prodTelurQuery->sum('jumlah_produksi');

        // b) Produksi Susu (SAPI_PERAH, KAMBING, DOMBA)
        $prodSusuQuery = ProduksiTernak::query()
            ->whereHas('kategoriProduksi', fn($q) => $q->where('nama', 'Susu'))
            ->whereHas('komoditas', fn($q) => $q->whereIn('kode', ['SAPI_PERAH', 'KAMBING', 'DOMBA']))
            ->where('tahun', $tahun);
        if ($triwulan !== null) {
            $prodSusuQuery->where('triwulan', $triwulan);
        }
        $totalProduksiSusu = (float) $prodSusuQuery->sum('jumlah_produksi');

        // c) Produksi Daging (Ayam, Sapi, Kambing, Domba)
        $prodDagingQuery = ProduksiTernak::query()
            ->whereHas('kategoriProduksi', fn($q) => $q->where('nama', 'Daging'))
            ->where('tahun', $tahun);
        if ($triwulan !== null) {
            $prodDagingQuery->where('triwulan', $triwulan);
        }
        $totalProduksiDaging = (float) $prodDagingQuery->sum('jumlah_produksi');

        // 3. Kasus Kesehatan Hewan & Wabah (PMK & LSD)
        $kasusQuery = KasusPenyakit::query()->where('tahun', $tahun);
        if ($triwulan !== null) {
            $kasusQuery->where('triwulan', $triwulan);
        }

        $kasusPerPenyakit = (clone $kasusQuery)
            ->join('penyakit', 'penyakit.id', '=', 'kasus_penyakit.penyakit_id')
            ->select(
                'penyakit.kode',
                DB::raw('SUM(kasus_penyakit.kasus_suspek) as total_suspek'),
                DB::raw('SUM(kasus_penyakit.kasus_positif) as total_positif'),
                DB::raw('SUM(kasus_penyakit.kasus_sembuh) as total_sembuh'),
                DB::raw('SUM(kasus_penyakit.kasus_mati) as total_mati'),
                DB::raw('SUM(kasus_penyakit.potong_bersyarat) as total_potong'),
                DB::raw('SUM(kasus_penyakit.kasus_aktif) as total_aktif')
            )
            ->groupBy('penyakit.kode')
            ->get()
            ->keyBy('kode');

        $pmkStat = $kasusPerPenyakit->get('PMK');
        $lsdStat = $kasusPerPenyakit->get('LSD');

        $totalKasusAktifPmk = (int) ($pmkStat->total_aktif ?? 0);
        $totalKasusAktifLsd = (int) ($lsdStat->total_aktif ?? 0);
        $totalSemuaKasusAktif = $totalKasusAktifPmk + $totalKasusAktifLsd;

        // 4. Rumah Potong Hewan (RPH)
        $totalRphAktif = FasilitasRph::where('status_operasional', 'Aktif')->count();
        $totalKapasitasPotongHarian = (int) FasilitasRph::where('status_operasional', 'Aktif')->sum('kapasitas_potong_ekor_hari');

        // 5. Hitung Pertumbuhan (Growth Rate) Terhadap Triwulan Sebelumnya
        $growth = $this->calculateGrowthRate($tahun, $triwulan, $totalSemuaTernak, $totalProduksiDaging + $totalProduksiTelur + $totalProduksiSusu);

        return [
            'periode' => [
                'tahun' => $tahun,
                'triwulan' => $triwulan,
                'label' => $triwulan ? "Triwulan {$triwulan} - {$tahun}" : "Tahun {$tahun} (Kumulatif)",
            ],
            'kpi' => [
                'populasi' => [
                    'total' => $totalSemuaTernak,
                    'ternak_besar' => $totalTernakBesar,
                    'ternak_kecil' => $totalTernakKecil,
                    'unggas' => $totalUnggas,
                    'growth_pct' => $growth['populasi'],
                ],
                'produksi' => [
                    'daging_kg' => round($totalProduksiDaging, 2),
                    'susu_liter' => round($totalProduksiSusu, 2),
                    'telur_kg' => round($totalProduksiTelur, 2),
                    'growth_pct' => $growth['produksi'],
                ],
                'kesehatan_hewan' => [
                    'total_kasus_aktif' => $totalSemuaKasusAktif,
                    'pmk' => [
                        'aktif' => $totalKasusAktifPmk,
                        'suspek' => (int) ($pmkStat->total_suspek ?? 0),
                        'positif' => (int) ($pmkStat->total_positif ?? 0),
                        'sembuh' => (int) ($pmkStat->total_sembuh ?? 0),
                        'mati' => (int) ($pmkStat->total_mati ?? 0),
                    ],
                    'lsd' => [
                        'aktif' => $totalKasusAktifLsd,
                        'suspek' => (int) ($lsdStat->total_suspek ?? 0),
                        'positif' => (int) ($lsdStat->total_positif ?? 0),
                        'sembuh' => (int) ($lsdStat->total_sembuh ?? 0),
                        'mati' => (int) ($lsdStat->total_mati ?? 0),
                    ],
                ],
                'fasilitas_rph' => [
                    'total_aktif' => $totalRphAktif,
                    'kapasitas_harian_ekor' => $totalKapasitasPotongHarian,
                ],
            ],
        ];
    }

    /**
     * b. Menghitung Top 5 dan Bottom 5 Kecamatan Berdasarkan Populasi Tertentu
     */
    public function getCommodityRankings(int $tahun, ?int $triwulan = null, string $komoditasKode = 'SAPI_POTONG', int $limit = 5): array
    {
        $query = DB::table('kecamatan')
            ->join('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->join('populasi_ternak', 'populasi_ternak.desa_id', '=', 'desa.id')
            ->join('komoditas', 'komoditas.id', '=', 'populasi_ternak.komoditas_id')
            ->where('komoditas.kode', $komoditasKode)
            ->where('populasi_ternak.tahun', $tahun);

        if ($triwulan !== null) {
            $query->where('populasi_ternak.triwulan', $triwulan);
        }

        $ranked = $query
            ->select(
                'kecamatan.id as kecamatan_id',
                'kecamatan.kode_kecamatan',
                'kecamatan.nama_kecamatan',
                DB::raw('COALESCE(SUM(populasi_ternak.jumlah_total), 0) as total_populasi'),
                DB::raw('COALESCE(SUM(populasi_ternak.jumlah_jantan), 0) as jantan'),
                DB::raw('COALESCE(SUM(populasi_ternak.jumlah_betina), 0) as betina')
            )
            ->groupBy('kecamatan.id', 'kecamatan.kode_kecamatan', 'kecamatan.nama_kecamatan')
            ->orderByDesc('total_populasi')
            ->get();

        $top = $ranked->take($limit)->values()->map(fn($item, $idx) => [
            'rank' => $idx + 1,
            'nama_kecamatan' => $item->nama_kecamatan,
            'kode_kecamatan' => $item->kode_kecamatan,
            'total' => (int) $item->total_populasi,
            'jantan' => (int) $item->jantan,
            'betina' => (int) $item->betina,
        ]);

        $bottom = $ranked->sortBy('total_populasi')->take($limit)->values()->map(fn($item, $idx) => [
            'rank' => $idx + 1,
            'nama_kecamatan' => $item->nama_kecamatan,
            'kode_kecamatan' => $item->kode_kecamatan,
            'total' => (int) $item->total_populasi,
            'jantan' => (int) $item->jantan,
            'betina' => (int) $item->betina,
        ]);

        return [
            'komoditas' => $komoditasKode,
            'tahun' => $tahun,
            'triwulan' => $triwulan,
            'top' => $top,
            'bottom' => $bottom,
        ];
    }

    /**
     * c. Breakdown Produksi Berdasarkan Komoditas untuk Donut Chart
     */
    public function getProductionBreakdown(int $tahun, ?int $triwulan = null): array
    {
        $query = DB::table('produksi_ternak')
            ->join('kategori_produksi', 'kategori_produksi.id', '=', 'produksi_ternak.kategori_produksi_id')
            ->join('komoditas', 'komoditas.id', '=', 'produksi_ternak.komoditas_id')
            ->where('produksi_ternak.tahun', $tahun);

        if ($triwulan !== null) {
            $query->where('produksi_ternak.triwulan', $triwulan);
        }

        $rows = $query
            ->select(
                'kategori_produksi.nama as kategori',
                'kategori_produksi.satuan',
                'komoditas.kode as komoditas_kode',
                'komoditas.nama as komoditas_nama',
                DB::raw('SUM(produksi_ternak.jumlah_produksi) as jumlah')
            )
            ->groupBy('kategori_produksi.nama', 'kategori_produksi.satuan', 'komoditas.kode', 'komoditas.nama')
            ->get();

        $result = [
            'Daging' => ['satuan' => 'kg', 'total' => 0.0, 'items' => []],
            'Susu'   => ['satuan' => 'liter', 'total' => 0.0, 'items' => []],
            'Telur'  => ['satuan' => 'butir/kg', 'total' => 0.0, 'items' => []],
        ];

        foreach ($rows as $row) {
            $kat = $row->kategori;
            if (!isset($result[$kat])) {
                continue;
            }

            // Validasi filter komoditas sesuai aturan bisnis
            if ($kat === 'Telur' && $row->komoditas_kode !== 'AYAM_TELUR') {
                continue;
            }
            if ($kat === 'Susu' && !in_array($row->komoditas_kode, ['SAPI_PERAH', 'KAMBING', 'DOMBA'])) {
                continue;
            }

            $val = (float) $row->jumlah;
            $result[$kat]['total'] += $val;
            $result[$kat]['items'][] = [
                'kode' => $row->komoditas_kode,
                'nama' => $row->komoditas_nama,
                'jumlah' => round($val, 2),
            ];
        }

        // Hitung proporsi persentase untuk Donut Chart
        foreach ($result as $kat => &$data) {
            $total = $data['total'];
            foreach ($data['items'] as &$item) {
                $item['persentase'] = $total > 0 ? round(($item['jumlah'] / $total) * 100, 1) : 0.0;
            }
            $data['total'] = round($data['total'], 2);
        }

        return $result;
    }

    /**
     * d. Data Historis Tren Triwulan PMK & LSD untuk Multi-Line Chart
     */
    public function getDiseaseTrends(int $tahun): array
    {
        $rows = DB::table('kasus_penyakit')
            ->join('penyakit', 'penyakit.id', '=', 'kasus_penyakit.penyakit_id')
            ->where('kasus_penyakit.tahun', $tahun)
            ->select(
                'kasus_penyakit.triwulan',
                'penyakit.kode as penyakit_kode',
                DB::raw('SUM(kasus_penyakit.kasus_positif) as positif'),
                DB::raw('SUM(kasus_penyakit.kasus_sembuh) as sembuh'),
                DB::raw('SUM(kasus_penyakit.kasus_mati) as mati'),
                DB::raw('SUM(kasus_penyakit.kasus_aktif) as aktif')
            )
            ->groupBy('kasus_penyakit.triwulan', 'penyakit.kode')
            ->orderBy('kasus_penyakit.triwulan')
            ->get();

        $triwulans = [1, 2, 3, 4];
        $trends = [
            'PMK' => [],
            'LSD' => [],
        ];

        foreach (['PMK', 'LSD'] as $pk) {
            foreach ($triwulans as $tw) {
                $match = $rows->first(fn($r) => $r->penyakit_kode === $pk && $r->triwulan == $tw);
                $trends[$pk][] = [
                    'triwulan' => $tw,
                    'label' => "TW {$tw}",
                    'positif' => (int) ($match->positif ?? 0),
                    'sembuh' => (int) ($match->sembuh ?? 0),
                    'mati' => (int) ($match->mati ?? 0),
                    'aktif' => (int) ($match->aktif ?? 0),
                ];
            }
        }

        return [
            'tahun' => $tahun,
            'categories' => ['TW 1', 'TW 2', 'TW 3', 'TW 4'],
            'series' => $trends,
        ];
    }

    /**
     * e. Algoritma Klasifikasi 5 Kelas Interval Warna Choropleth DESIGN_RULE.md
     */
    public function calculateChoroplethIntervals(int $tahun, ?int $triwulan = null, string $komoditasKode = 'SAPI_POTONG'): array
    {
        $kecamatans = Kecamatan::all();

        $query = DB::table('kecamatan')
            ->leftJoin('desa', 'desa.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('populasi_ternak', function ($join) use ($tahun, $triwulan, $komoditasKode) {
                $join->on('populasi_ternak.desa_id', '=', 'desa.id')
                     ->where('populasi_ternak.tahun', '=', $tahun);
                if ($triwulan !== null) {
                    $join->where('populasi_ternak.triwulan', '=', $triwulan);
                }
            })
            ->leftJoin('komoditas', function ($join) use ($komoditasKode) {
                $join->on('komoditas.id', '=', 'populasi_ternak.komoditas_id')
                     ->where('komoditas.kode', '=', $komoditasKode);
            })
            ->select(
                'kecamatan.id as kecamatan_id',
                'kecamatan.kode_kecamatan',
                'kecamatan.nama_kecamatan',
                DB::raw('COALESCE(SUM(populasi_ternak.jumlah_total), 0) as nilai')
            )
            ->groupBy('kecamatan.id', 'kecamatan.kode_kecamatan', 'kecamatan.nama_kecamatan')
            ->get();

        $values = $query->pluck('nilai')->map(fn($v) => (float) $v)->sort()->values()->toArray();
        $minVal = !empty($values) ? min($values) : 0;
        $maxVal = !empty($values) ? max($values) : 0;

        // Tentukan batas 5 kelas interval kuantil
        $intervals = $this->computeQuintileThresholds($values, $minVal, $maxVal);

        $classified = $query->map(function ($item) use ($intervals) {
            $val = (float) $item->nilai;
            $class = 1;

            if ($val > $intervals[3]) {
                $class = 5;
            } elseif ($val > $intervals[2]) {
                $class = 4;
            } elseif ($val > $intervals[1]) {
                $class = 3;
            } elseif ($val > $intervals[0]) {
                $class = 2;
            } else {
                $class = 1;
            }

            $palette = self::CHOROPLETH_PALETTE[$class];

            return [
                'kecamatan_id' => $item->kecamatan_id,
                'kode_kecamatan' => $item->kode_kecamatan,
                'nama_kecamatan' => $item->nama_kecamatan,
                'nilai' => (int) $val,
                'class_tier' => $class,
                'color_hex' => $palette['hex'],
                'color_class' => $palette['class'],
                'label' => $palette['label'],
            ];
        });

        return [
            'komoditas' => $komoditasKode,
            'tahun' => $tahun,
            'triwulan' => $triwulan,
            'min' => (int) $minVal,
            'max' => (int) $maxVal,
            'intervals' => $intervals,
            'legend' => self::CHOROPLETH_PALETTE,
            'data' => $classified,
        ];
    }

    /**
     * Hitung ambang batas 5 kuantil interval choropleth
     */
    protected function computeQuintileThresholds(array $values, float $min, float $max): array
    {
        if ($max <= $min || count($values) < 5) {
            $step = ($max - $min) / 5.0;
            return [
                round($min + $step, 1),
                round($min + ($step * 2), 1),
                round($min + ($step * 3), 1),
                round($min + ($step * 4), 1),
            ];
        }

        $count = count($values);
        return [
            (float) $values[(int) floor($count * 0.20)],
            (float) $values[(int) floor($count * 0.40)],
            (float) $values[(int) floor($count * 0.60)],
            (float) $values[(int) floor($count * 0.80)],
        ];
    }

    /**
     * Menghitung persentase pertumbuhan dibandingkan triwulan atau tahun sebelumnya
     */
    protected function calculateGrowthRate(int $tahun, ?int $triwulan, int $currPop, float $currProd): array
    {
        $prevTahun = $tahun;
        $prevTriwulan = $triwulan ? $triwulan - 1 : null;

        if ($prevTriwulan === 0) {
            $prevTahun = $tahun - 1;
            $prevTriwulan = 4;
        }

        $prevPopQuery = PopulasiTernak::query()->where('tahun', $prevTahun);
        $prevProdQuery = ProduksiTernak::query()->where('tahun', $prevTahun);

        if ($prevTriwulan !== null) {
            $prevPopQuery->where('triwulan', $prevTriwulan);
            $prevProdQuery->where('triwulan', $prevTriwulan);
        }

        $prevPop = (int) $prevPopQuery->sum('jumlah_total');
        $prevProd = (float) $prevProdQuery->sum('jumlah_produksi');

        $popGrowth = $prevPop > 0 ? round((($currPop - $prevPop) / $prevPop) * 100, 1) : 0.0;
        $prodGrowth = $prevProd > 0 ? round((($currProd - $prevProd) / $prevProd) * 100, 1) : 0.0;

        return [
            'populasi' => $popGrowth,
            'produksi' => $prodGrowth,
        ];
    }
}
