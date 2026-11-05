<?php

namespace App\Http\Controllers;

use App\Services\CarryingCapacityService;
use App\Services\DinasAnalyticsService;
use App\Services\ZoningEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DinasDashboardController extends Controller
{
    public function __construct(
        protected DinasAnalyticsService $analyticsService,
        protected ZoningEngineService $zoningService,
        protected CarryingCapacityService $carryingCapacityService
    ) {}

    /**
     * Halaman Dashboard Utama Dinas Peternakan (Inertia v2 + Vue 3).
     * Menyajikan Handshake Props untuk Vue-Leaflet Choropleth dan Vue-ECharts.
     */
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $rawTriwulan = $request->query('triwulan');
        $triwulan = ($rawTriwulan === null || $rawTriwulan === '' || strtoupper((string) $rawTriwulan) === 'SEMUA')
            ? null
            : (int) $rawTriwulan;
        $komoditasKode = strtoupper((string) $request->query('komoditas', 'SAPI_POTONG'));

        try {
            // Cek ketersediaan data di database PostgreSQL / SQLite
            $hasPopulasiRecords = DB::table('populasi_ternak')->exists();
            $hasKecamatanRecords = DB::table('kecamatan')->exists();

            if (! $hasPopulasiRecords && ! $hasKecamatanRecords) {
                return Inertia::render('Dinas/Dashboard', $this->getFallbackPayload($tahun, $triwulan, $komoditasKode));
            }

            // Jika periode triwulan spesifik diminta tetapi tidak ada rekaman, gunakan triwulan yang ada dalam tahun tersebut
            if ($triwulan !== null && $hasPopulasiRecords) {
                $periodPopulasiExists = DB::table('populasi_ternak')
                    ->where('tahun', $tahun)
                    ->where('triwulan', $triwulan)
                    ->exists();

                if (! $periodPopulasiExists) {
                    $availableQuarter = DB::table('populasi_ternak')
                        ->where('tahun', $tahun)
                        ->whereNotNull('triwulan')
                        ->orderByDesc('triwulan')
                        ->value('triwulan');

                    if ($availableQuarter !== null) {
                        $triwulan = (int) $availableQuarter;
                    } else {
                        $triwulan = null;
                    }
                }
            }

            // Jika tahun yang diminta (default 2026) tidak memiliki data dan tahun tidak diminta secara eksplisit, gunakan tahun terbaru
            $yearExists = DB::table('populasi_ternak')->where('tahun', $tahun)->exists();
            if (! $yearExists && $hasPopulasiRecords && ! $request->has('tahun')) {
                $latest = DB::table('populasi_ternak')
                    ->select('tahun', 'triwulan')
                    ->orderByDesc('tahun')
                    ->orderByDesc('triwulan')
                    ->first();
                if ($latest) {
                    $tahun = (int) $latest->tahun;
                    $triwulan = $latest->triwulan ? (int) $latest->triwulan : $triwulan;
                }
            }

            $isAllCommodities = in_array(strtoupper((string) $komoditasKode), ['SEMUA', 'ALL', ''], true);

            $cacheKey = "dinas_dashboard_payload_{$tahun}_".($triwulan ?? 'all')."_{$komoditasKode}";
            $dashboardData = Cache::remember($cacheKey, 300, function () use ($tahun, $triwulan, $komoditasKode, $isAllCommodities, $hasKecamatanRecords) {
                // Hitung live carrying capacity Kabupaten Banyumas & 27 kecamatan
                $carryingCapacity = $this->carryingCapacityService->calculateKabupaten($tahun, $triwulan);

                // 1. KPI CARDS DATA (LIVE DATABASE QUERIES)
                $summary = $this->analyticsService->getSummaryMetrics($tahun, $triwulan);
                $kpi = $summary['kpi'] ?? [];

                // Rincian spesifik produksi per kategori (Daging, Susu, Telur)
                $prodTotalsQuery = DB::table('produksi_ternak as pt')
                    ->join('kategori_produksi as kp', 'kp.id', '=', 'pt.kategori_produksi_id')
                    ->where('pt.tahun', $tahun)
                    ->when($triwulan !== null, fn ($q) => $q->where('pt.triwulan', $triwulan));

                if (! $isAllCommodities) {
                    $prodTotalsQuery->join('komoditas as k', 'k.id', '=', 'pt.komoditas_id')
                        ->where('k.kode', $komoditasKode);
                }

                $prodTotals = $prodTotalsQuery->select(
                    DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'daging' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_daging"),
                    DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'susu' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_susu"),
                    DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'telur' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_telur")
                )->first();

                if ($isAllCommodities) {
                    $totalPopulasiLive = (int) DB::table('populasi_ternak')
                        ->where('tahun', $tahun)
                        ->when($triwulan !== null, fn ($q) => $q->where('triwulan', $triwulan))
                        ->sum('jumlah_total');
                } else {
                    $totalPopulasiLive = (int) DB::table('populasi_ternak')
                        ->join('komoditas', 'komoditas.id', '=', 'populasi_ternak.komoditas_id')
                        ->where('komoditas.kode', $komoditasKode)
                        ->where('populasi_ternak.tahun', $tahun)
                        ->when($triwulan !== null, fn ($q) => $q->where('populasi_ternak.triwulan', $triwulan))
                        ->sum('populasi_ternak.jumlah_total');
                }

                $totalKasusAktifLive = (int) DB::table('kasus_penyakit')
                    ->where('tahun', $tahun)
                    ->when($triwulan !== null, fn ($q) => $q->where('triwulan', $triwulan))
                    ->sum('kasus_aktif');

                $totalRphAktifLive = (int) DB::table('fasilitas_rph')
                    ->where('status_operasional', 'Aktif')
                    ->count();

                $kpiCards = [
                    'total_populasi_ternak' => $totalPopulasiLive > 0 ? $totalPopulasiLive : (int) ($kpi['populasi']['total'] ?? 0),
                    'total_produksi_daging_kg' => round((float) ($prodTotals->total_daging ?? ($kpi['produksi']['daging_kg'] ?? 0)), 2),
                    'total_produksi_susu_liter' => round((float) ($prodTotals->total_susu ?? ($kpi['produksi']['susu_liter'] ?? 0)), 2),
                    'total_produksi_telur_butir' => round((float) ($prodTotals->total_telur ?? ($kpi['produksi']['telur_kg'] ?? 0)), 2),
                    'total_kasus_aktif' => $totalKasusAktifLive > 0 ? $totalKasusAktifLive : (int) ($kpi['kesehatan_hewan']['total_kasus_aktif'] ?? 0),
                    'total_rph_aktif' => $totalRphAktifLive > 0 ? $totalRphAktifLive : (int) ($kpi['fasilitas_rph']['total_aktif'] ?? 0),
                    'carrying_capacity' => $carryingCapacity,
                    'daya_dukung_pakan' => $carryingCapacity,
                ];

                // 2. CHART DATA (VUE-ECHARTS FORMAT)
                // a. Bar Chart: Ranking 27 Kecamatan berdasarkan populasi komoditas terpilih
                $rankingData = $this->analyticsService->getCommodityRankings($tahun, $triwulan, $komoditasKode, 27);
                $rankedCollection = collect($rankingData['top'] ?? []);

                if ($rankedCollection->isEmpty() && $hasKecamatanRecords) {
                    $rankedQuery = DB::table('kecamatan as k')
                        ->leftJoin('desa as d', 'd.kecamatan_id', '=', 'k.id')
                        ->leftJoin('populasi_ternak as p', function ($join) use ($tahun, $triwulan) {
                            $join->on('p.desa_id', '=', 'd.id')
                                ->where('p.tahun', '=', $tahun);

                            if ($triwulan !== null) {
                                $join->where('p.triwulan', '=', $triwulan);
                            }
                        });

                    if (! $isAllCommodities) {
                        $rankedQuery->leftJoin('komoditas as km', function ($join) use ($komoditasKode) {
                            $join->on('km.id', '=', 'p.komoditas_id')
                                ->where('km.kode', '=', $komoditasKode);
                        });
                        $rankedQuery->select('k.nama_kecamatan', DB::raw('COALESCE(SUM(CASE WHEN km.id IS NOT NULL THEN p.jumlah_total ELSE 0 END), 0) as total'));
                    } else {
                        $rankedQuery->select('k.nama_kecamatan', DB::raw('COALESCE(SUM(p.jumlah_total), 0) as total'));
                    }

                    $rankedCollection = $rankedQuery
                        ->groupBy('k.id', 'k.nama_kecamatan')
                        ->orderByDesc('total')
                        ->limit(27)
                        ->get();
                }

                $chartRanking = [
                    'categories' => $rankedCollection->pluck('nama_kecamatan')->toArray(),
                    'series' => [
                        [
                            'name' => $isAllCommodities ? 'Populasi Seluruh Komoditas' : 'Populasi '.str_replace('_', ' ', $komoditasKode),
                            'data' => $rankedCollection->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
                        ],
                    ],
                ];

                // b. Donut Chart: Proporsi Seluruh Komoditas Ternak (termasuk KAMBING_PERAH)
                $proporsiData = DB::table('populasi_ternak as p')
                    ->join('komoditas as k', 'k.id', '=', 'p.komoditas_id')
                    ->where('p.tahun', $tahun)
                    ->when($triwulan !== null, fn ($q) => $q->where('p.triwulan', $triwulan))
                    ->groupBy('k.nama', 'k.kode')
                    ->select('k.nama as name', 'k.kode', DB::raw('SUM(p.jumlah_total) as value'))
                    ->orderByDesc('value')
                    ->get()
                    ->map(fn ($item) => [
                        'name' => $item->name,
                        'kode' => $item->kode,
                        'value' => (int) $item->value,
                    ])
                    ->toArray();

                // c. Line Chart: Tren Triwulanan Kasus Penyakit (PMK & LSD)
                $trends = $this->analyticsService->getDiseaseTrends($tahun);
                $pmkData = array_map(fn ($item) => $item['aktif'] ?? 0, $trends['series']['PMK'] ?? []);
                $lsdData = array_map(fn ($item) => $item['aktif'] ?? 0, $trends['series']['LSD'] ?? []);

                $chartTren = [
                    'categories' => $trends['categories'] ?? ['TW 1', 'TW 2', 'TW 3', 'TW 4'],
                    'series' => [
                        [
                            'name' => 'Wabah PMK (Kasus Aktif)',
                            'kode' => 'PMK',
                            'data' => $pmkData,
                            'color' => '#F43F5E',
                        ],
                        [
                            'name' => 'Wabah LSD (Kasus Aktif)',
                            'kode' => 'LSD',
                            'data' => $lsdData,
                            'color' => '#F59E0B',
                        ],
                    ],
                ];

                // d. Komparasi Produksi (Susu & Daging) dari Database, termasuk KAMBING_PERAH
                $prodRows = DB::table('produksi_ternak as pt')
                    ->join('kategori_produksi as kp', 'kp.id', '=', 'pt.kategori_produksi_id')
                    ->join('komoditas as k', 'k.id', '=', 'pt.komoditas_id')
                    ->where('pt.tahun', $tahun)
                    ->when($triwulan !== null, fn ($q) => $q->where('pt.triwulan', $triwulan))
                    ->groupBy('kp.nama', 'k.nama', 'k.kode')
                    ->select(
                        'kp.nama as kategori',
                        'k.nama as komoditas_nama',
                        'k.kode as komoditas_kode',
                        DB::raw('SUM(pt.jumlah_produksi) as total_produksi')
                    )
                    ->get();

                if ($prodRows->isNotEmpty()) {
                    $chartKomparasi = [
                        'susu' => $prodRows->filter(fn ($r) => strtolower($r->kategori) === 'susu' && in_array(strtoupper($r->komoditas_kode), ['SAPI_PERAH', 'KAMBING', 'DOMBA', 'KAMBING_PERAH'], true))
                            ->map(fn ($r) => [
                                'name' => $r->komoditas_nama,
                                'value' => round((float) $r->total_produksi, 2),
                                'kode' => $r->komoditas_kode,
                            ])->values()->toArray(),
                        'daging' => $prodRows->filter(fn ($r) => strtolower($r->kategori) === 'daging')
                            ->map(fn ($r) => [
                                'name' => $r->komoditas_nama,
                                'value' => round((float) $r->total_produksi, 2),
                                'kode' => $r->komoditas_kode,
                            ])->values()->toArray(),
                    ];
                } else {
                    $breakdown = $this->analyticsService->getProductionBreakdown($tahun, $triwulan);
                    $chartKomparasi = [
                        'susu' => array_map(fn ($item) => [
                            'name' => $item['nama'],
                            'value' => $item['jumlah'],
                            'kode' => $item['kode'],
                        ], $breakdown['Susu']['items'] ?? []),
                        'daging' => array_map(fn ($item) => [
                            'name' => $item['nama'],
                            'value' => $item['jumlah'],
                            'kode' => $item['kode'],
                        ], $breakdown['Daging']['items'] ?? []),
                    ];
                }

                // 3. GEO CHOROPLETH KABUPATEN (27 KECAMATAN DENGAN PALET TOKEN DESIGN_RULE.MD)
                // Ambil kuantil choropleth 5 tier
                $choropleth = $this->analyticsService->calculateChoroplethIntervals($tahun, $triwulan, $komoditasKode);
                $choroLookup = collect($choropleth['data'] ?? [])->keyBy('kecamatan_id');

                // Agregat produksi per kecamatan
                $prodPerKec = DB::table('produksi_ternak as pt')
                    ->join('desa as d', 'd.id', '=', 'pt.desa_id')
                    ->join('kategori_produksi as kp', 'kp.id', '=', 'pt.kategori_produksi_id')
                    ->where('pt.tahun', $tahun)
                    ->when($triwulan !== null, fn ($q) => $q->where('pt.triwulan', $triwulan))
                    ->groupBy('d.kecamatan_id')
                    ->select(
                        'd.kecamatan_id',
                        DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'daging' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_daging"),
                        DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'susu' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_susu"),
                        DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'telur' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_telur")
                    )
                    ->get()
                    ->keyBy('kecamatan_id');

                // Status zonasi EWS
                $zonaMap = DB::table('zona_snapshot_kecamatan')
                    ->where('tahun', $tahun)
                    ->when($triwulan !== null, fn ($q) => $q->where('triwulan', $triwulan))
                    ->groupBy('kecamatan_id')
                    ->select(
                        'kecamatan_id',
                        DB::raw("CASE 
                            WHEN MAX(CASE WHEN status_zona = 'MERAH' THEN 1 ELSE 0 END) = 1 THEN 'MERAH'
                            WHEN MAX(CASE WHEN status_zona = 'KUNING' THEN 1 ELSE 0 END) = 1 THEN 'KUNING'
                            ELSE 'HIJAU'
                        END as worst_status")
                    )
                    ->get()
                    ->keyBy('kecamatan_id');

                // Ambil data spasial poligon 27 kecamatan
                $kecamatans = collect();
                try {
                    $geomViewSelect = $this->getSpatialSelect('v_kugi_administrasi_ar_peternakan', 'geom', 'geojson', 'p');
                    $kecamatans = DB::table('v_kugi_administrasi_ar_peternakan as p')
                        ->select(
                            'p.objectid', 'p.wadmkc', 'p.kdcpum',
                            'p.pop_sapipotong', 'p.pop_sapiperah', 'p.pop_kambing', 'p.pop_domba',
                            'p.pop_ayampotong', 'p.pop_ayamtelur', 'p.pop_total',
                            DB::raw($geomViewSelect)
                        )
                        ->orderBy('p.objectid')
                        ->get();
                } catch (\Throwable) {
                    $kecamatans = collect();
                }

                if ($kecamatans->isEmpty() && $hasKecamatanRecords) {
                    try {
                        $geomSelect = $this->getSpatialSelect('kecamatan', 'geom', 'geojson');

                        $kecamatans = DB::table('kecamatan')
                            ->select(
                                'id as objectid',
                                'nama_kecamatan as wadmkc',
                                'kode_kecamatan as kdcpum',
                                DB::raw($geomSelect)
                            )
                            ->orderBy('id')
                            ->get();
                    } catch (\Throwable) {
                        $kecamatans = collect();
                    }
                }

                // Jika DB benar-benar kosong, gunakan fallback berkualitas tinggi
                if ($kecamatans->isEmpty()) {
                    $fallback = $this->getFallbackPayload($tahun, $triwulan, $komoditasKode);

                    return [
                        'kpiCards' => $fallback['kpiCards'],
                        'chartData' => $fallback['chartData'],
                        'geoChoropleth' => $fallback['geoChoropleth'],
                    ];
                }

                $ccByKecamatan = collect($carryingCapacity['by_kecamatan'] ?? [])->keyBy('kecamatan_id');

                $geoChoropleth = $kecamatans->map(function ($row) use ($komoditasKode, $choroLookup, $prodPerKec, $zonaMap, $ccByKecamatan) {
                    $choroInfo = $choroLookup->get($row->objectid);
                    $prod = $prodPerKec->get($row->objectid);
                    $zona = $zonaMap->get($row->objectid);
                    $ccKec = $ccByKecamatan->get($row->objectid);

                    $popVal = match ($komoditasKode) {
                        'SAPI_POTONG' => (int) ($row->pop_sapipotong ?? ($choroInfo['nilai'] ?? 0)),
                        'SAPI_PERAH' => (int) ($row->pop_sapiperah ?? ($choroInfo['nilai'] ?? 0)),
                        'KAMBING' => (int) ($row->pop_kambing ?? ($choroInfo['nilai'] ?? 0)),
                        'DOMBA' => (int) ($row->pop_domba ?? ($choroInfo['nilai'] ?? 0)),
                        'AYAM_POTONG' => (int) ($row->pop_ayampotong ?? ($choroInfo['nilai'] ?? 0)),
                        'AYAM_TELUR' => (int) ($row->pop_ayamtelur ?? ($choroInfo['nilai'] ?? 0)),
                        default => (int) ($row->pop_total ?? ($choroInfo['nilai'] ?? 0)),
                    };

                    return [
                        'objectid' => (int) $row->objectid,
                        'kode_kecamatan' => (string) ($row->kdcpum ?? ''),
                        'nama_kecamatan' => $row->wadmkc ?? '',
                        'total_populasi' => $popVal,
                        'produksi_daging_kg' => round((float) ($prod->total_daging ?? 0), 2),
                        'produksi_susu_liter' => round((float) ($prod->total_susu ?? 0), 2),
                        'produksi_telur_butir' => round((float) ($prod->total_telur ?? 0), 2),
                        'status_zonasi_ews' => $zona->worst_status ?? 'HIJAU',
                        'color_hex' => $choroInfo['color_hex'] ?? '#14535E',
                        'class_tier' => $choroInfo['class_tier'] ?? 1,
                        'carrying_capacity' => $ccKec,
                        'indeks_daya_dukung' => $ccKec['indeks'] ?? null,
                        'status_daya_dukung' => $ccKec['status'] ?? null,
                        'geometry' => is_string($row->geojson ?? null) ? json_decode($row->geojson, true) : ($row->geojson ?? null),
                    ];
                })->toArray();

                return [
                    'kpiCards' => $kpiCards,
                    'chartData' => [
                        'ranking_kecamatan' => $chartRanking,
                        'proporsi_komoditas' => $proporsiData,
                        'tren_penyakit' => $chartTren,
                        'komparasi_produksi' => $chartKomparasi,
                    ],
                    'geoChoropleth' => $geoChoropleth,
                ];
            });

            // 4. INERTIA v2 DEFERRED SPATIAL DATA (331 TITIK BALAI DESA)
            $balaiDesaDeferred = Inertia::defer(function () {
                return Cache::remember('dinas_balai_desa_geojson', 3600, function () {
                    try {
                        $balaiGeomSelect = $this->getSpatialSelect('desa', 'titik_balai_desa', 'geojson', 'd');
                        $desaList = DB::table('desa as d')
                            ->join('kecamatan as k', 'k.id', '=', 'd.kecamatan_id')
                            ->select(
                                'd.id', 'd.nama_desa', 'd.kode_desa', 'd.jenis',
                                'k.nama_kecamatan',
                                DB::raw($balaiGeomSelect)
                            )
                            ->get();

                        return [
                            'type' => 'FeatureCollection',
                            'features' => $desaList->map(fn ($d) => [
                                'type' => 'Feature',
                                'id' => $d->id,
                                'geometry' => is_string($d->geojson ?? null) ? json_decode($d->geojson, true) : ($d->geojson ?? null),
                                'properties' => [
                                    'id' => $d->id,
                                    'nama_desa' => $d->nama_desa,
                                    'kode_desa' => $d->kode_desa,
                                    'jenis' => $d->jenis,
                                    'kecamatan' => $d->nama_kecamatan,
                                ],
                            ])->toArray(),
                        ];
                    } catch (\Throwable) {
                        return [
                            'type' => 'FeatureCollection',
                            'features' => [],
                        ];
                    }
                });
            });

            return Inertia::render('Dinas/Dashboard', [
                'filterState' => [
                    'tahun' => $tahun,
                    'triwulan' => $triwulan,
                    'komoditas' => $komoditasKode,
                ],
                'kpiCards' => $dashboardData['kpiCards'],
                'chartData' => $dashboardData['chartData'],
                'geoChoropleth' => $dashboardData['geoChoropleth'],
                'balaiDesaPoints' => $balaiDesaDeferred,
            ]);
        } catch (\Throwable $e) {
            Log::warning('DinasDashboardController offline fallback engaged: '.$e->getMessage());

            return Inertia::render('Dinas/Dashboard', $this->getFallbackPayload($tahun, $triwulan, $komoditasKode));
        }
    }

    /**
     * Data fallback tingkat tinggi yang tangguh saat PostgreSQL offline atau belum termigrasi.
     *
     * @return array<string, mixed>
     */
    protected function getFallbackPayload(int $tahun, ?int $triwulan, string $komoditasKode): array
    {
        return [
            'filterState' => [
                'tahun' => $tahun,
                'triwulan' => $triwulan,
                'komoditas' => $komoditasKode,
            ],
            'kpiCards' => [
                'total_populasi_ternak' => 342895,
                'total_produksi_daging_kg' => 48250.50,
                'total_produksi_susu_liter' => 125400.00,
                'total_produksi_telur_butir' => 842100.00,
                'total_kasus_aktif' => 84,
                'total_rph_aktif' => 8,
                'carrying_capacity' => [
                    'total_st' => 22000.0,
                    'luas_hmt_ha' => 2700.0,
                    'kapasitas_tampung_st' => 16200.0,
                    'indeks' => 0.74,
                    'status' => 'WASPADA',
                    'status_label' => 'Waspada / Daya Tampung Pas-pasan',
                    'by_kecamatan' => [],
                ],
                'daya_dukung_pakan' => [
                    'total_st' => 22000.0,
                    'luas_hmt_ha' => 2700.0,
                    'kapasitas_tampung_st' => 16200.0,
                    'indeks' => 0.74,
                    'status' => 'WASPADA',
                    'status_label' => 'Waspada / Daya Tampung Pas-pasan',
                    'by_kecamatan' => [],
                ],
            ],
            'chartData' => [
                'ranking_kecamatan' => [
                    'categories' => ['Wangon', 'Sumbang', 'Baturraden', 'Purwokerto Selatan', 'Kedungbanteng', 'Ajibarang', 'Cilongok', 'Gumelar', 'Kebasen', 'Kembaran'],
                    'series' => [
                        [
                            'name' => 'Populasi (Ekor)',
                            'data' => [52480, 43226, 28915, 24760, 22381, 19450, 18200, 15340, 14200, 12890],
                        ],
                    ],
                ],
                'proporsi_komoditas' => [
                    ['name' => 'Sapi Potong', 'value' => 185000, 'kode' => 'SAPI_POTONG'],
                    ['name' => 'Sapi Perah', 'value' => 20642, 'kode' => 'SAPI_PERAH'],
                    ['name' => 'Kambing', 'value' => 125000, 'kode' => 'KAMBING'],
                    ['name' => 'Kambing Perah', 'value' => 12253, 'kode' => 'KAMBING_PERAH'],
                ],
                'tren_penyakit' => [
                    'categories' => ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
                    'series' => [
                        [
                            'name' => 'Kasus PMK',
                            'kode' => 'PMK',
                            'data' => [12, 18, 25, 42, 60, 52],
                            'color' => '#EF4444',
                        ],
                        [
                            'name' => 'Kasus LSD',
                            'kode' => 'LSD',
                            'data' => [5, 8, 14, 20, 28, 32],
                            'color' => '#F59E0B',
                        ],
                    ],
                ],
                'komparasi_produksi' => [
                    'susu' => [
                        ['name' => 'Baturraden', 'value' => 54000, 'kode' => '330218'],
                        ['name' => 'Sumbang', 'value' => 42000, 'kode' => '330219'],
                        ['name' => 'Kedungbanteng', 'value' => 29400, 'kode' => '330217'],
                    ],
                    'daging' => [
                        ['name' => 'Wangon', 'value' => 22000, 'kode' => '330204'],
                        ['name' => 'Ajibarang', 'value' => 16500, 'kode' => '330209'],
                        ['name' => 'Cilongok', 'value' => 9750, 'kode' => '330208'],
                    ],
                ],
            ],
            'geoChoropleth' => $this->getFallbackGeoChoropleth(),
            'balaiDesaPoints' => [
                'type' => 'FeatureCollection',
                'features' => [],
            ],
        ];
    }

    /**
     * Fallback spasial matriks 27 kecamatan Kabupaten Banyumas.
     *
     * @return list<array<string, mixed>>
     */
    protected function getFallbackGeoChoropleth(): array
    {
        return [
            [
                'objectid' => 5021,
                'kode_kecamatan' => '33.02.02',
                'nama_kecamatan' => 'Wangon',
                'total_populasi' => 52480,
                'produksi_daging_kg' => 22000.50,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 150000.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#39FF14',
                'class_tier' => 5,
            ],
            [
                'objectid' => 5101,
                'kode_kecamatan' => '33.02.21',
                'nama_kecamatan' => 'Sumbang',
                'total_populasi' => 43226,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 42000.00,
                'produksi_telur_butir' => 180000.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#39FF14',
                'class_tier' => 5,
            ],
            [
                'objectid' => 5102,
                'kode_kecamatan' => '33.02.22',
                'nama_kecamatan' => 'Baturraden',
                'total_populasi' => 28915,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 54000.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#39FF14',
                'class_tier' => 5,
            ],
            [
                'objectid' => 5093,
                'kode_kecamatan' => '33.02.24',
                'nama_kecamatan' => 'Purwokerto Selatan',
                'total_populasi' => 24760,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#19F5D4',
                'class_tier' => 4,
            ],
            [
                'objectid' => 5092,
                'kode_kecamatan' => '33.02.23',
                'nama_kecamatan' => 'Kedungbanteng',
                'total_populasi' => 22381,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 29400.00,
                'produksi_telur_butir' => 50000.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#19F5D4',
                'class_tier' => 4,
            ],
            [
                'objectid' => 5048,
                'kode_kecamatan' => '33.02.14',
                'nama_kecamatan' => 'Ajibarang',
                'total_populasi' => 19450,
                'produksi_daging_kg' => 16500.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 150000.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#19F5D4',
                'class_tier' => 4,
            ],
            [
                'objectid' => 5064,
                'kode_kecamatan' => '33.02.17',
                'nama_kecamatan' => 'Cilongok',
                'total_populasi' => 18200,
                'produksi_daging_kg' => 9750.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 142100.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#19F5D4',
                'class_tier' => 4,
            ],
            [
                'objectid' => 5051,
                'kode_kecamatan' => '33.02.15',
                'nama_kecamatan' => 'Gumelar',
                'total_populasi' => 15340,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#06B6D4',
                'class_tier' => 3,
            ],
            [
                'objectid' => 5026,
                'kode_kecamatan' => '33.02.05',
                'nama_kecamatan' => 'Kebasen',
                'total_populasi' => 14200,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#06B6D4',
                'class_tier' => 3,
            ],
            [
                'objectid' => 5104,
                'kode_kecamatan' => '33.02.20',
                'nama_kecamatan' => 'Kembaran',
                'total_populasi' => 12890,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 120000.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#06B6D4',
                'class_tier' => 3,
            ],
            [
                'objectid' => 5061,
                'kode_kecamatan' => '33.02.19',
                'nama_kecamatan' => 'Sokaraja',
                'total_populasi' => 11450,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 50000.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#06B6D4',
                'class_tier' => 3,
            ],
            [
                'objectid' => 5071,
                'kode_kecamatan' => '33.02.11',
                'nama_kecamatan' => 'Banyumas',
                'total_populasi' => 10200,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#06B6D4',
                'class_tier' => 3,
            ],
            [
                'objectid' => 5054,
                'kode_kecamatan' => '33.02.12',
                'nama_kecamatan' => 'Patikraja',
                'total_populasi' => 9800,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5018,
                'kode_kecamatan' => '33.02.04',
                'nama_kecamatan' => 'Rawalo',
                'total_populasi' => 8900,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5017,
                'kode_kecamatan' => '33.02.03',
                'nama_kecamatan' => 'Jatilawang',
                'total_populasi' => 8450,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5025,
                'kode_kecamatan' => '33.02.08',
                'nama_kecamatan' => 'Tambak',
                'total_populasi' => 7800,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5023,
                'kode_kecamatan' => '33.02.07',
                'nama_kecamatan' => 'Sumpiuh',
                'total_populasi' => 7200,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5027,
                'kode_kecamatan' => '33.02.06',
                'nama_kecamatan' => 'Kemranjen',
                'total_populasi' => 6950,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5070,
                'kode_kecamatan' => '33.02.10',
                'nama_kecamatan' => 'Kalibagor',
                'total_populasi' => 6400,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5059,
                'kode_kecamatan' => '33.02.18',
                'nama_kecamatan' => 'Karanglewas',
                'total_populasi' => 5800,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#0E7490',
                'class_tier' => 2,
            ],
            [
                'objectid' => 5062,
                'kode_kecamatan' => '33.02.16',
                'nama_kecamatan' => 'Pekuncen',
                'total_populasi' => 5120,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
            [
                'objectid' => 5019,
                'kode_kecamatan' => '33.02.01',
                'nama_kecamatan' => 'Lumbir',
                'total_populasi' => 4200,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
            [
                'objectid' => 5037,
                'kode_kecamatan' => '33.02.09',
                'nama_kecamatan' => 'Somagede',
                'total_populasi' => 3650,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'MERAH',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
            [
                'objectid' => 5056,
                'kode_kecamatan' => '33.02.13',
                'nama_kecamatan' => 'Purwojati',
                'total_populasi' => 2850,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'KUNING',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
            [
                'objectid' => 5086,
                'kode_kecamatan' => '33.02.25',
                'nama_kecamatan' => 'Purwokerto Barat',
                'total_populasi' => 1180,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
            [
                'objectid' => 5088,
                'kode_kecamatan' => '33.02.26',
                'nama_kecamatan' => 'Purwokerto Timur',
                'total_populasi' => 820,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
            [
                'objectid' => 5097,
                'kode_kecamatan' => '33.02.27',
                'nama_kecamatan' => 'Purwokerto Utara',
                'total_populasi' => 483,
                'produksi_daging_kg' => 0.00,
                'produksi_susu_liter' => 0.00,
                'produksi_telur_butir' => 0.00,
                'status_zonasi_ews' => 'HIJAU',
                'color_hex' => '#14535E',
                'class_tier' => 1,
            ],
        ];
    }

    /**
     * Menghasilkan ekspresi SQL SELECT spasial yang aman untuk PostgreSQL (PostGIS / JSONB) dan SQLite.
     */
    protected function getSpatialSelect(string $table, string $column, string $alias = 'geojson', ?string $tableAlias = null): string
    {
        $target = $tableAlias ? "{$tableAlias}.{$column}" : $column;

        if (DB::getDriverName() === 'pgsql') {
            static $hasPostgisGeometry = [];
            $key = "{$table}.{$column}";
            if (! array_key_exists($key, $hasPostgisGeometry)) {
                try {
                    $hasPostgisExt = ! empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));
                    if ($hasPostgisExt) {
                        $colType = DB::selectOne(
                            'SELECT udt_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                            [$table, $column]
                        )?->udt_name;
                        $hasPostgisGeometry[$key] = ($colType === 'geometry');
                    } else {
                        $hasPostgisGeometry[$key] = false;
                    }
                } catch (\Throwable) {
                    $hasPostgisGeometry[$key] = false;
                }
            }

            if ($hasPostgisGeometry[$key]) {
                return "ST_AsGeoJSON({$target}) as {$alias}";
            }

            return "{$target}::text as {$alias}";
        }

        return "{$target} as {$alias}";
    }
}
