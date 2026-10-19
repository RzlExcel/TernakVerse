<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\FasilitasRph;
use App\Services\DinasAnalyticsService;
use App\Services\ReportBuilderService;

class DinasDashboardController extends Controller
{
    protected DinasAnalyticsService $analyticsService;
    protected ReportBuilderService $reportBuilder;

    public function __construct(
        DinasAnalyticsService $analyticsService,
        ReportBuilderService $reportBuilder
    ) {
        $this->analyticsService = $analyticsService;
        $this->reportBuilder = $reportBuilder;
    }

    /**
     * Mengembalikan FeatureCollection GeoJSON 27 Kecamatan dengan agregasi kuantitatif
     * populasi, produksi (Daging, Susu, Telur), dan status zonasi EWS terkini.
     */
    public function getPetaKabupaten(Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', date('Y'));
        $triwulan = $request->has('triwulan') ? (int) $request->query('triwulan') : null;
        $komoditasKode = $request->query('komoditas'); // Null = Semua komoditas

        // 1. Agregat Produksi (Daging, Susu, Telur) per Kecamatan
        $prodQuery = DB::table('produksi_ternak as pt')
            ->join('desa as d', 'd.id', '=', 'pt.desa_id')
            ->join('kategori_produksi as kp', 'kp.id', '=', 'pt.kategori_produksi_id')
            ->where('pt.tahun', '=', $tahun);
        if ($triwulan !== null) {
            $prodQuery->where('pt.triwulan', '=', $triwulan);
        }
        $produksiMap = $prodQuery->groupBy('d.kecamatan_id')
            ->select(
                'd.kecamatan_id',
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'daging' THEN pt.jumlah_produksi ELSE 0 END), 0) as prod_daging"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'susu' THEN pt.jumlah_produksi ELSE 0 END), 0) as prod_susu"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(kp.nama) = 'telur' THEN pt.jumlah_produksi ELSE 0 END), 0) as prod_telur")
            )
            ->get()
            ->keyBy('kecamatan_id');

        // 2. Agregat Zonasi EWS Terkini (Mengambil status terparah MERAH > KUNING > HIJAU per kecamatan)
        $zonaQuery = DB::table('zona_snapshot_kecamatan')
            ->where('tahun', '=', $tahun);
        if ($triwulan !== null) {
            $zonaQuery->where('triwulan', '=', $triwulan);
        }
        $zonaMap = $zonaQuery->groupBy('kecamatan_id')
            ->select(
                'kecamatan_id',
                DB::raw("CASE 
                    WHEN bool_or(status_zona = 'MERAH') THEN 'MERAH'
                    WHEN bool_or(status_zona = 'KUNING') THEN 'KUNING'
                    ELSE 'HIJAU'
                END as worst_status"),
                DB::raw("SUM(total_kasus_aktif) as agg_kasus_aktif")
            )
            ->get()
            ->keyBy('kecamatan_id');

        // 3. Ambil data view spasial KUGI peternakan & penyakit (27 Kecamatan)
        $rows = DB::table('v_kugi_administrasi_ar_peternakan as p')
            ->leftJoin('v_kugi_administrasi_ar_penyakithewan as k', 'k.objectid', '=', 'p.objectid')
            ->select(
                'p.objectid', 'p.wadmkc', 'p.kdcpum',
                'p.pop_sapipotong', 'p.pop_sapiperah', 'p.pop_kambing', 'p.pop_domba',
                'p.pop_ayampotong', 'p.pop_ayamtelur', 'p.pop_total',
                'k.kasus_pmk', 'k.kasus_lsd', 'k.kasus_total', 'k.kasus_aktif',
                DB::raw('ST_AsGeoJSON(p.geom) as geojson')
            )
            ->orderBy('p.objectid')
            ->get();

        // 4. Ambil interval kuantil choropleth untuk pewarnaan dinamis
        $targetKomoditas = $komoditasKode ?: 'SAPI_POTONG';
        $choropleth = $this->analyticsService->calculateChoroplethIntervals($tahun, $triwulan, $targetKomoditas);
        $choroLookup = collect($choropleth['data'])->keyBy('kecamatan_id');

        $features = $rows->map(function ($row) use ($komoditasKode, $choroLookup, $produksiMap, $zonaMap) {
            $zonaInfo = $zonaMap->get($row->objectid);
            $statusEws = $zonaInfo->worst_status ?? ($row->kasus_aktif > 5 ? 'MERAH' : ($row->kasus_aktif > 0 ? 'KUNING' : 'HIJAU'));
            
            $prodInfo = $produksiMap->get($row->objectid);
            $dagingKg = (float) ($prodInfo->prod_daging ?? 0);
            $susuLiter = (float) ($prodInfo->prod_susu ?? 0);
            $telurButir = (float) ($prodInfo->prod_telur ?? 0);

            // Filter nilai populasi jika komoditas spesifik diminta
            $populasiNilai = match ($komoditasKode) {
                'SAPI_POTONG' => (int) $row->pop_sapipotong,
                'SAPI_PERAH'  => (int) $row->pop_sapiperah,
                'KAMBING'     => (int) $row->pop_kambing,
                'DOMBA'       => (int) $row->pop_domba,
                'AYAM_POTONG' => (int) $row->pop_ayampotong,
                'AYAM_TELUR'  => (int) $row->pop_ayamtelur,
                default       => (int) $row->pop_total,
            };

            $choroInfo = $choroLookup->get($row->objectid);
            $colorHex = match ($statusEws) {
                'MERAH'  => '#ef4444',
                'KUNING' => '#eab308',
                default  => '#22c55e',
            };

            return [
                'type' => 'Feature',
                'id' => (int) $row->objectid,
                'geometry' => json_decode($row->geojson, true),
                'properties' => [
                    'objectid' => (int) $row->objectid,
                    'kdcpum' => (string) $row->kdcpum,
                    'kode_kecamatan' => (string) $row->kdcpum,
                    'wadmkc' => $row->wadmkc,
                    'nama_kecamatan' => $row->wadmkc,
                    'total_populasi' => $populasiNilai,
                    'populasi_terpilih' => $populasiNilai,
                    'produksi_daging_kg' => round($dagingKg, 2),
                    'produksi_susu_liter' => round($susuLiter, 2),
                    'produksi_telur_butir' => round($telurButir, 2),
                    'status_zonasi_ews' => $statusEws,
                    'status_zonasi' => $statusEws,
                    'color_hex' => $colorHex,
                    'populasi' => [
                        'sapi_potong' => (int) $row->pop_sapipotong,
                        'sapi_perah'  => (int) $row->pop_sapiperah,
                        'kambing'     => (int) $row->pop_kambing,
                        'domba'       => (int) $row->pop_domba,
                        'ayam_potong' => (int) $row->pop_ayampotong,
                        'ayam_telur'  => (int) $row->pop_ayamtelur,
                        'total'       => (int) $row->pop_total,
                    ],
                    'produksi' => [
                        'daging_kg'   => round($dagingKg, 2),
                        'susu_liter'  => round($susuLiter, 2),
                        'telur_butir' => round($telurButir, 2),
                    ],
                    'kesehatan_hewan' => [
                        'kasus_pmk'   => (int) $row->kasus_pmk,
                        'kasus_lsd'   => (int) $row->kasus_lsd,
                        'total_kasus' => (int) $row->kasus_total,
                        'kasus_aktif' => (int) ($zonaInfo->agg_kasus_aktif ?? $row->kasus_aktif),
                    ],
                    'choropleth' => [
                        'class_tier' => $choroInfo['class_tier'] ?? 1,
                        'color_hex'  => $choroInfo['color_hex'] ?? '#14535E',
                        'label'      => $choroInfo['label'] ?? 'Sangat Rendah',
                    ],
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'metadata' => [
                'kabupaten' => 'Banyumas',
                'total_kecamatan' => $features->count(),
                'srs' => 'EPSG:4326',
                'standar' => 'KUGI 2.1 (ADMINISTRASI_AR_PETERNAKAN)',
                'periode' => ['tahun' => $tahun, 'triwulan' => $triwulan],
                'filter_komoditas' => $komoditasKode,
                'choropleth_legend' => DinasAnalyticsService::CHOROPLETH_PALETTE,
                'updated_at' => now()->toIso8601String(),
            ],
            'features' => $features,
        ]);
    }

    /**
     * Mengembalikan rincian geospasial drill-down: Poligon Kecamatan + Titik Balai Desa + Fasilitas RPH.
     */
    public function getDrilldownKecamatan(int $kecamatanId, Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', date('Y'));
        $triwulan = $request->has('triwulan') ? (int) $request->query('triwulan') : null;

        $kecamatan = Kecamatan::withGeoJson('geom')->findOrFail($kecamatanId);

        // Ambil titik balai desa dengan agregasi populasi & penyakit tingkat desa
        $desaList = Desa::where('kecamatan_id', $kecamatanId)
            ->withGeoJson('titik_balai_desa')
            ->get();

        $desaFeatures = $desaList->map(function ($desa) use ($tahun, $triwulan) {
            $popQuery = $desa->populasiTernak()->where('tahun', $tahun);
            $kasusQuery = $desa->kasusPenyakit()->where('tahun', $tahun);

            if ($triwulan !== null) {
                $popQuery->where('triwulan', $triwulan);
                $kasusQuery->where('triwulan', $triwulan);
            }

            return [
                'type' => 'Feature',
                'id' => $desa->id,
                'geometry' => is_string($desa->titik_balai_desa) ? json_decode($desa->titik_balai_desa, true) : $desa->titik_balai_desa,
                'properties' => [
                    'nama_desa' => $desa->nama_desa,
                    'kode_desa' => $desa->kode_desa,
                    'jenis' => $desa->jenis,
                    'total_populasi' => (int) $popQuery->sum('jumlah_total'),
                    'total_kasus_aktif' => (int) $kasusQuery->sum('kasus_aktif'),
                ],
            ];
        });

        // Ambil fasilitas RPH pada kecamatan ini
        $rphList = FasilitasRph::where('kecamatan_id', $kecamatanId)
            ->withGeoJson('geom_titik')
            ->get();

        $rphFeatures = $rphList->map(function ($rph) {
            return [
                'type' => 'Feature',
                'id' => $rph->id,
                'geometry' => is_string($rph->geom_titik) ? json_decode($rph->geom_titik, true) : $rph->geom_titik,
                'properties' => [
                    'nama_rph' => $rph->nama_rph,
                    'alamat' => $rph->alamat_lengkap,
                    'pengelola' => $rph->jenis_pengelola,
                    'hewan_potong' => $rph->jenis_hewan_potong,
                    'kapasitas_harian' => $rph->kapasitas_potong_ekor_hari,
                    'status' => $rph->status_operasional,
                    'kontak' => $rph->nomor_kontak,
                ],
            ];
        });

        return response()->json([
            'kecamatan' => [
                'type' => 'Feature',
                'id' => $kecamatan->id,
                'geometry' => is_string($kecamatan->geom) ? json_decode($kecamatan->geom, true) : $kecamatan->geom,
                'properties' => [
                    'kode_kecamatan' => $kecamatan->kode_kecamatan,
                    'nama_kecamatan' => $kecamatan->nama_kecamatan,
                    'luas_wilayah_km2' => (float) $kecamatan->luas_wilayah_km2,
                ],
            ],
            'balai_desa' => [
                'type' => 'FeatureCollection',
                'features' => $desaFeatures,
            ],
            'fasilitas_rph' => [
                'type' => 'FeatureCollection',
                'features' => $rphFeatures,
            ],
        ]);
    }

    /**
     * Payload lengkap untuk komponen grafik analitik Vue-ECharts.
     */
    public function getAnalitikData(Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', date('Y'));
        $triwulan = $request->has('triwulan') ? (int) $request->query('triwulan') : null;
        $komoditas = $request->query('komoditas', 'SAPI_POTONG');

        $kpi = $this->analyticsService->getSummaryMetrics($tahun, $triwulan);
        $ranking = $this->analyticsService->getCommodityRankings($tahun, $triwulan, $komoditas, 5);
        $produksi = $this->analyticsService->getProductionBreakdown($tahun, $triwulan);
        $trenPenyakit = $this->analyticsService->getDiseaseTrends($tahun);

        return response()->json([
            'status' => 'success',
            'periode' => ['tahun' => $tahun, 'triwulan' => $triwulan],
            'kpi_summary' => $kpi['kpi'],
            'ranking_komoditas' => $ranking,
            'breakdown_produksi' => $produksi,
            'tren_penyakit_historis' => $trenPenyakit,
        ]);
    }

    /**
     * Mengunduh berkas laporan resmi eksekutif (PDF Landscape atau Excel Multi-Sheet).
     */
    public function downloadLaporan(Request $request): BinaryFileResponse
    {
        $tahun = (int) $request->query('tahun', date('Y'));
        $triwulan = $request->has('triwulan') ? (int) $request->query('triwulan') : null;
        $format = strtolower($request->query('format', 'pdf'));

        if ($format === 'excel' || $format === 'xlsx') {
            $filePath = $this->reportBuilder->exportExcel($tahun, $triwulan);
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // Default: PDF Landscape
        $filePath = $this->reportBuilder->exportPdf($tahun, $triwulan);
        return response()->download($filePath, basename($filePath), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
