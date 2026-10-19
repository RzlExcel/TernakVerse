<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Services\DinasAnalyticsService;
use App\Services\ZoningEngineService;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Komoditas;
use App\Models\FasilitasRph;

class DinasDashboardController extends Controller
{
    public function __construct(
        protected DinasAnalyticsService $analyticsService,
        protected ZoningEngineService $zoningService
    ) {}

    /**
     * Halaman Dashboard Utama Dinas Peternakan (Inertia v2 + Vue 3).
     * Menyajikan Handshake Props untuk Vue-Leaflet Choropleth dan Vue-ECharts.
     */
    public function index(Request $request): Response
    {
        $tahun = (int) $request->query('tahun', 2026);
        $triwulan = $request->has('triwulan') && $request->query('triwulan') !== '' ? (int) $request->query('triwulan') : 3;
        $komoditasKode = strtoupper($request->query('komoditas', 'SAPI_POTONG'));

        // 1. KPI CARDS DATA
        $summary = $this->analyticsService->getSummaryMetrics($tahun, $triwulan);
        
        // Rincian spesifik produksi per kategori (Daging, Susu, Telur)
        $prodTotals = DB::table('produksi_ternak as pt')
            ->join('kategori_produksi as kp', 'kp.id', '=', 'pt.kategori_produksi_id')
            ->where('pt.tahun', $tahun)
            ->when($triwulan !== null, fn($q) => $q->where('pt.triwulan', $triwulan))
            ->select(
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'daging' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_daging"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'susu' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_susu"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'telur' THEN pt.jumlah_produksi ELSE 0 END), 0) as total_telur")
            )
            ->first();

        $kpi = $summary['kpi'] ?? [];
        $kpiCards = [
            'total_populasi_ternak' => (int) ($kpi['populasi']['total'] ?? 0),
            'total_produksi_daging_kg' => round((float) ($kpi['produksi']['daging_kg'] ?? ($prodTotals->total_daging ?? 0)), 2),
            'total_produksi_susu_liter' => round((float) ($kpi['produksi']['susu_liter'] ?? ($prodTotals->total_susu ?? 0)), 2),
            'total_produksi_telur_butir' => round((float) ($kpi['produksi']['telur_kg'] ?? ($prodTotals->total_telur ?? 0)), 2),
            'total_kasus_aktif' => (int) ($kpi['kesehatan_hewan']['total_kasus_aktif'] ?? 0),
            'total_rph_aktif' => (int) ($kpi['fasilitas_rph']['total_aktif'] ?? 0),
        ];

        // 2. CHART DATA (VUE-ECHARTS FORMAT)
        // a. Bar Chart: Ranking 27 Kecamatan berdasarkan populasi komoditas terpilih
        $rankingData = $this->analyticsService->getCommodityRankings($tahun, $triwulan, $komoditasKode, 27);
        $rankedCollection = collect($rankingData['top'] ?? []);

        $chartRanking = [
            'categories' => $rankedCollection->pluck('nama_kecamatan')->toArray(),
            'series' => [
                [
                    'name' => 'Populasi ' . str_replace('_', ' ', $komoditasKode),
                    'data' => $rankedCollection->pluck('total')->toArray(),
                ]
            ],
        ];

        // b. Donut Chart: Proporsi 6 Komoditas Ternak
        $proporsiData = DB::table('populasi_ternak as p')
            ->join('komoditas as k', 'k.id', '=', 'p.komoditas_id')
            ->where('p.tahun', $tahun)
            ->when($triwulan !== null, fn($q) => $q->where('p.triwulan', $triwulan))
            ->groupBy('k.nama', 'k.kode')
            ->select('k.nama as name', 'k.kode', DB::raw('SUM(p.jumlah_total) as value'))
            ->orderByDesc('value')
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'kode' => $item->kode,
                'value' => (int) $item->value,
            ])
            ->toArray();

        // c. Line Chart: Tren Triwulanan Kasus Penyakit (PMK & LSD)
        $trends = $this->analyticsService->getDiseaseTrends($tahun);
        $pmkData = array_map(fn($item) => $item['aktif'] ?? 0, $trends['series']['PMK'] ?? []);
        $lsdData = array_map(fn($item) => $item['aktif'] ?? 0, $trends['series']['LSD'] ?? []);

        $chartTren = [
            'categories' => $trends['categories'],
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
                ]
            ],
        ];

        // d. Komparasi Produksi (Susu & Daging)
        $breakdown = $this->analyticsService->getProductionBreakdown($tahun, $triwulan);
        $chartKomparasi = [
            'susu' => array_map(fn($item) => [
                'name' => $item['nama'],
                'value' => $item['jumlah'],
                'kode' => $item['kode'],
            ], $breakdown['Susu']['items'] ?? []),
            'daging' => array_map(fn($item) => [
                'name' => $item['nama'],
                'value' => $item['jumlah'],
                'kode' => $item['kode'],
            ], $breakdown['Daging']['items'] ?? []),
        ];

        // 3. GEO CHOROPLETH KABUPATEN (27 KECAMATAN DENGAN PALET TOKEN DESIGN_RULE.MD)
        // Ambil kuantil choropleth 5 tier
        $choropleth = $this->analyticsService->calculateChoroplethIntervals($tahun, $triwulan, $komoditasKode);
        $choroLookup = collect($choropleth['data'])->keyBy('kecamatan_id');

        // Agregat produksi per kecamatan
        $prodPerKec = DB::table('produksi_ternak as pt')
            ->join('desa as d', 'd.id', '=', 'pt.desa_id')
            ->join('kategori_produksi as kp', 'kp.id', '=', 'pt.kategori_produksi_id')
            ->where('pt.tahun', $tahun)
            ->when($triwulan !== null, fn($q) => $q->where('pt.triwulan', $triwulan))
            ->groupBy('d.kecamatan_id')
            ->select(
                'd.kecamatan_id',
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'daging' THEN pt.jumlah_produksi ELSE 0 END), 0) as daging"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'susu' THEN pt.jumlah_produksi ELSE 0 END), 0) as susu"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'telur' THEN pt.jumlah_produksi ELSE 0 END), 0) as telur")
            )
            ->get()
            ->keyBy('kecamatan_id');

        // Status zonasi EWS
        $zonaMap = DB::table('zona_snapshot_kecamatan')
            ->where('tahun', $tahun)
            ->when($triwulan !== null, fn($q) => $q->where('triwulan', $triwulan))
            ->groupBy('kecamatan_id')
            ->select(
                'kecamatan_id',
                DB::raw("CASE 
                    WHEN bool_or(status_zona = 'MERAH') THEN 'MERAH'
                    WHEN bool_or(status_zona = 'KUNING') THEN 'KUNING'
                    ELSE 'HIJAU'
                END as worst_status")
            )
            ->get()
            ->keyBy('kecamatan_id');

        // Ambil data spasial poligon 27 kecamatan
        $kecamatans = DB::table('v_kugi_administrasi_ar_peternakan as p')
            ->select(
                'p.objectid', 'p.wadmkc', 'p.kdcpum',
                'p.pop_sapipotong', 'p.pop_sapiperah', 'p.pop_kambing', 'p.pop_domba',
                'p.pop_ayampotong', 'p.pop_ayamtelur', 'p.pop_total',
                DB::raw('ST_AsGeoJSON(p.geom) as geojson')
            )
            ->orderBy('p.objectid')
            ->get();

        $geoChoropleth = $kecamatans->map(function ($row) use ($komoditasKode, $choroLookup, $prodPerKec, $zonaMap) {
            $choroInfo = $choroLookup->get($row->objectid);
            $prod = $prodPerKec->get($row->objectid);
            $zona = $zonaMap->get($row->objectid);

            $popVal = match ($komoditasKode) {
                'SAPI_POTONG' => (int) $row->pop_sapipotong,
                'SAPI_PERAH'  => (int) $row->pop_sapiperah,
                'KAMBING'     => (int) $row->pop_kambing,
                'DOMBA'       => (int) $row->pop_domba,
                'AYAM_POTONG' => (int) $row->pop_ayampotong,
                'AYAM_TELUR'  => (int) $row->pop_ayamtelur,
                default       => (int) $row->pop_total,
            };

            return [
                'objectid' => (int) $row->objectid,
                'kode_kecamatan' => (string) $row->kdcpum,
                'nama_kecamatan' => $row->wadmkc,
                'total_populasi' => $popVal,
                'produksi_daging_kg' => round((float) ($prod->daging ?? 0), 2),
                'produksi_susu_liter' => round((float) ($prod->susu ?? 0), 2),
                'produksi_telur_butir' => round((float) ($prod->telur ?? 0), 2),
                'status_zonasi_ews' => $zona->worst_status ?? 'HIJAU',
                'color_hex' => $choroInfo['color_hex'] ?? '#14535E',
                'class_tier' => $choroInfo['class_tier'] ?? 1,
                'geometry' => json_decode($row->geojson, true),
            ];
        })->toArray();

        // 4. INERTIA v2 DEFERRED SPATIAL DATA (331 TITIK BALAI DESA)
        // Menggunakan Inertia::defer() agar payload awal ringan dan render instan
        $balaiDesaDeferred = Inertia::defer(function () {
            $desaList = DB::table('desa as d')
                ->join('kecamatan as k', 'k.id', '=', 'd.kecamatan_id')
                ->select(
                    'd.id', 'd.nama_desa', 'd.kode_desa', 'd.jenis',
                    'k.nama_kecamatan',
                    DB::raw('ST_AsGeoJSON(d.titik_balai_desa) as geojson')
                )
                ->get();

            return [
                'type' => 'FeatureCollection',
                'features' => $desaList->map(fn($d) => [
                    'type' => 'Feature',
                    'id' => $d->id,
                    'geometry' => json_decode($d->geojson, true),
                    'properties' => [
                        'id' => $d->id,
                        'nama_desa' => $d->nama_desa,
                        'kode_desa' => $d->kode_desa,
                        'jenis' => $d->jenis,
                        'kecamatan' => $d->nama_kecamatan,
                    ],
                ])->toArray(),
            ];
        });

        return Inertia::render('Dinas/Dashboard', [
            'filterState' => [
                'tahun' => $tahun,
                'triwulan' => $triwulan,
                'komoditas' => $komoditasKode,
            ],
            'kpiCards' => $kpiCards,
            'chartData' => [
                'ranking_kecamatan' => $chartRanking,
                'proporsi_komoditas' => $proporsiData,
                'tren_penyakit' => $chartTren,
                'komparasi_produksi' => $chartKomparasi,
            ],
            'geoChoropleth' => $geoChoropleth,
            'balaiDesaPoints' => $balaiDesaDeferred,
        ]);
    }
}
