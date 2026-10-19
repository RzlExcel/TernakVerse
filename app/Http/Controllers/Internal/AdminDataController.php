<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PopulasiTernak;
use App\Models\ProduksiTernak;
use App\Models\KasusPenyakit;
use App\Models\FasilitasRph;
use App\Models\ParameterZonasi;
use App\Models\AuditLog;
use App\Http\Requests\Internal\PopulasiTernakRequest;
use App\Http\Requests\Internal\ProduksiTernakRequest;
use App\Http\Requests\Internal\KasusPenyakitRequest;
use App\Http\Requests\Internal\FasilitasRphRequest;
use App\Services\ZoningEngineService;
use App\Services\IsoMetadataGeneratorService;

class AdminDataController extends Controller
{
    // =========================================================================
    // 1. CRUD POPULASI TERNAK
    // =========================================================================

    public function indexPopulasi(Request $request): JsonResponse
    {
        $query = PopulasiTernak::with(['desa.kecamatan', 'komoditas'])
            ->orderByDesc('tahun')
            ->orderByDesc('triwulan');

        if ($request->has('desa_id')) {
            $query->where('desa_id', $request->query('desa_id'));
        }
        if ($request->has('komoditas_id')) {
            $query->where('komoditas_id', $request->query('komoditas_id'));
        }
        if ($request->has('tahun')) {
            $query->where('tahun', $request->query('tahun'));
        }
        if ($request->has('triwulan')) {
            $query->where('triwulan', $request->query('triwulan'));
        }

        return response()->json($query->paginate(25));
    }

    public function storePopulasi(PopulasiTernakRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $record = PopulasiTernak::create($validated);

        AuditLog::catat('CREATE', 'populasi_ternak', $record->id, null, $record->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan populasi ternak berhasil disimpan.',
            'data' => $record->load(['desa.kecamatan', 'komoditas']),
        ], Response::HTTP_CREATED);
    }

    public function showPopulasi(int $id): JsonResponse
    {
        $record = PopulasiTernak::with(['desa.kecamatan', 'komoditas'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $record]);
    }

    public function updatePopulasi(PopulasiTernakRequest $request, int $id): JsonResponse
    {
        $record = PopulasiTernak::findOrFail($id);
        $old = $record->toArray();

        $record->update($request->validated());

        AuditLog::catat('UPDATE', 'populasi_ternak', $record->id, $old, $record->fresh()->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan populasi ternak berhasil diperbarui.',
            'data' => $record->fresh(['desa.kecamatan', 'komoditas']),
        ]);
    }

    public function destroyPopulasi(int $id): JsonResponse
    {
        $record = PopulasiTernak::findOrFail($id);
        $old = $record->toArray();
        $record->delete();

        AuditLog::catat('DELETE', 'populasi_ternak', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan populasi ternak berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 2. CRUD PRODUKSI TERNAK
    // =========================================================================

    public function indexProduksi(Request $request): JsonResponse
    {
        $query = ProduksiTernak::with(['desa.kecamatan', 'komoditas', 'kategoriProduksi'])
            ->orderByDesc('tahun')
            ->orderByDesc('triwulan');

        if ($request->has('desa_id')) {
            $query->where('desa_id', $request->query('desa_id'));
        }
        if ($request->has('komoditas_id')) {
            $query->where('komoditas_id', $request->query('komoditas_id'));
        }
        if ($request->has('kategori_produksi_id')) {
            $query->where('kategori_produksi_id', $request->query('kategori_produksi_id'));
        }

        return response()->json($query->paginate(25));
    }

    public function storeProduksi(ProduksiTernakRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $record = ProduksiTernak::create($validated);

        AuditLog::catat('CREATE', 'produksi_ternak', $record->id, null, $record->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan hasil produksi berhasil disimpan.',
            'data' => $record->load(['desa.kecamatan', 'komoditas', 'kategoriProduksi']),
        ], Response::HTTP_CREATED);
    }

    public function showProduksi(int $id): JsonResponse
    {
        $record = ProduksiTernak::with(['desa.kecamatan', 'komoditas', 'kategoriProduksi'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $record]);
    }

    public function updateProduksi(ProduksiTernakRequest $request, int $id): JsonResponse
    {
        $record = ProduksiTernak::findOrFail($id);
        $old = $record->toArray();

        $record->update($request->validated());

        AuditLog::catat('UPDATE', 'produksi_ternak', $record->id, $old, $record->fresh()->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan hasil produksi berhasil diperbarui.',
            'data' => $record->fresh(['desa.kecamatan', 'komoditas', 'kategoriProduksi']),
        ]);
    }

    public function destroyProduksi(int $id): JsonResponse
    {
        $record = ProduksiTernak::findOrFail($id);
        $old = $record->toArray();
        $record->delete();

        AuditLog::catat('DELETE', 'produksi_ternak', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan hasil produksi berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 3. CRUD KASUS PENYAKIT (SURVEILANS WABAH)
    // =========================================================================

    public function indexKasus(Request $request): JsonResponse
    {
        $query = KasusPenyakit::with(['desa.kecamatan', 'penyakit', 'komoditas'])
            ->orderByDesc('tanggal_laporan');

        if ($request->has('desa_id')) {
            $query->where('desa_id', $request->query('desa_id'));
        }
        if ($request->has('penyakit_id')) {
            $query->where('penyakit_id', $request->query('penyakit_id'));
        }

        return response()->json($query->paginate(25));
    }

    public function storeKasus(KasusPenyakitRequest $request, ZoningEngineService $engine): JsonResponse
    {
        $validated = $request->validated();
        $record = KasusPenyakit::create($validated);

        AuditLog::catat('CREATE', 'kasus_penyakit', $record->id, null, $record->toArray());

        // Otomasi pembaruan EWS jika ada kasus baru
        $engine->recalculateZoning($record->tahun, $record->triwulan);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan kejadian kasus penyakit berhasil dicatat dan zonasi EWS otomatis diperbarui.',
            'data' => $record->load(['desa.kecamatan', 'penyakit', 'komoditas']),
        ], Response::HTTP_CREATED);
    }

    public function showKasus(int $id): JsonResponse
    {
        $record = KasusPenyakit::with(['desa.kecamatan', 'penyakit', 'komoditas'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $record]);
    }

    public function updateKasus(KasusPenyakitRequest $request, int $id, ZoningEngineService $engine): JsonResponse
    {
        $record = KasusPenyakit::findOrFail($id);
        $old = $record->toArray();

        $record->update($request->validated());

        AuditLog::catat('UPDATE', 'kasus_penyakit', $record->id, $old, $record->fresh()->toArray());

        // Otomasi pembaruan EWS
        $engine->recalculateZoning($record->tahun, $record->triwulan);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan kejadian kasus penyakit berhasil diperbarui.',
            'data' => $record->fresh(['desa.kecamatan', 'penyakit', 'komoditas']),
        ]);
    }

    public function destroyKasus(int $id, ZoningEngineService $engine): JsonResponse
    {
        $record = KasusPenyakit::findOrFail($id);
        $old = $record->toArray();
        $tahun = $record->tahun;
        $triwulan = $record->triwulan;

        $record->delete();

        AuditLog::catat('DELETE', 'kasus_penyakit', $id, $old, null);

        // Rekalkulasi EWS
        $engine->recalculateZoning($tahun, $triwulan);

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan kasus penyakit berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 4. CRUD FASILITAS RPH (RUMAH POTONG HEWAN)
    // =========================================================================

    public function indexRph(): JsonResponse
    {
        $rphs = FasilitasRph::with('kecamatan')->withGeoJson('geom_titik')->get();
        return response()->json(['status' => 'success', 'data' => $rphs]);
    }

    public function storeRph(FasilitasRphRequest $request): JsonResponse
    {
        $val = $request->validated();

        $rph = new FasilitasRph();
        $rph->kecamatan_id = $val['kecamatan_id'];
        $rph->nama_rph = $val['nama_rph'];
        $rph->alamat_lengkap = $val['alamat_lengkap'];
        $rph->jenis_pengelola = $val['jenis_pengelola'];
        $rph->jenis_hewan_potong = $val['jenis_hewan_potong'];
        $rph->kapasitas_potong_ekor_hari = $val['kapasitas_potong_ekor_hari'];
        $rph->status_operasional = $val['status_operasional'];
        $rph->nomor_kontak = $val['nomor_kontak'] ?? null;
        $rph->geom_titik = DB::raw("ST_SetSRID(ST_MakePoint({$val['longitude']}, {$val['latitude']}), 4326)");
        $rph->save();

        AuditLog::catat('CREATE', 'fasilitas_rph', $rph->id, null, $val);

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas RPH baru berhasil ditambahkan.',
            'data' => $rph->load('kecamatan'),
        ], Response::HTTP_CREATED);
    }

    public function showRph(int $id): JsonResponse
    {
        $rph = FasilitasRph::with('kecamatan')->withGeoJson('geom_titik')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $rph]);
    }

    public function updateRph(FasilitasRphRequest $request, int $id): JsonResponse
    {
        $rph = FasilitasRph::findOrFail($id);
        $old = $rph->toArray();
        $val = $request->validated();

        $rph->kecamatan_id = $val['kecamatan_id'];
        $rph->nama_rph = $val['nama_rph'];
        $rph->alamat_lengkap = $val['alamat_lengkap'];
        $rph->jenis_pengelola = $val['jenis_pengelola'];
        $rph->jenis_hewan_potong = $val['jenis_hewan_potong'];
        $rph->kapasitas_potong_ekor_hari = $val['kapasitas_potong_ekor_hari'];
        $rph->status_operasional = $val['status_operasional'];
        $rph->nomor_kontak = $val['nomor_kontak'] ?? null;
        $rph->geom_titik = DB::raw("ST_SetSRID(ST_MakePoint({$val['longitude']}, {$val['latitude']}), 4326)");
        $rph->save();

        AuditLog::catat('UPDATE', 'fasilitas_rph', $rph->id, $old, $val);

        return response()->json([
            'status' => 'success',
            'message' => 'Data fasilitas RPH berhasil diperbarui.',
            'data' => $rph->fresh('kecamatan'),
        ]);
    }

    public function destroyRph(int $id): JsonResponse
    {
        $rph = FasilitasRph::findOrFail($id);
        $old = $rph->toArray();
        $rph->delete();

        AuditLog::catat('DELETE', 'fasilitas_rph', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas RPH berhasil dihapus.',
        ]);
    }

    // =========================================================================
    // 5. PENGATURAN PARAMETER AMBANG BATAS EWS
    // =========================================================================

    public function getParameters(): JsonResponse
    {
        $params = ParameterZonasi::with('penyakit')->get();
        return response()->json(['status' => 'success', 'data' => $params]);
    }

    public function updateParameters(Request $request, ZoningEngineService $engine): JsonResponse
    {
        $old = ParameterZonasi::all()->toArray();

        if ($request->has('parameters')) {
            $request->validate([
                'parameters' => ['required', 'array'],
                'parameters.*.id' => ['required', 'integer', 'exists:parameter_zonasi,id'],
                'parameters.*.ambang_kuning_maks' => ['required', 'integer', 'min:1'],
                'parameters.*.jendela_hari_aktif' => ['nullable', 'integer', 'min:1', 'max:90'],
            ]);
            $items = $request->input('parameters');
        } else {
            $val = $request->validate([
                'id' => ['nullable', 'integer', 'exists:parameter_zonasi,id'],
                'penyakit_id' => ['nullable', 'integer', 'exists:penyakit,id'],
                'ambang_kuning_maks' => ['nullable', 'integer', 'min:1'],
                'ambang_kuning' => ['nullable', 'integer', 'min:1'],
                'jendela_hari_aktif' => ['nullable', 'integer', 'min:1', 'max:90'],
            ]);
            $firstParam = ParameterZonasi::first();
            $paramId = $val['id'] ?? ($val['penyakit_id'] ?? ($firstParam?->id ?? 1));
            $kuning = $val['ambang_kuning_maks'] ?? ($val['ambang_kuning'] ?? 5);
            $items = [
                [
                    'id' => $paramId,
                    'ambang_kuning_maks' => $kuning,
                    'jendela_hari_aktif' => $val['jendela_hari_aktif'] ?? 30,
                ]
            ];
        }

        foreach ($items as $item) {
            $updateData = ['ambang_kuning_maks' => $item['ambang_kuning_maks']];
            if (!empty($item['jendela_hari_aktif'])) {
                $updateData['jendela_hari_aktif'] = $item['jendela_hari_aktif'];
            }
            ParameterZonasi::where('id', $item['id'])->update($updateData);
        }

        AuditLog::catat('CONFIG_CHANGE', 'parameter_zonasi', null, $old, $items);

        // Jalankan rekalkulasi ulang zonasi dengan ambang batas baru
        $engine->recalculateZoning((int) date('Y'), (int) ceil(date('n') / 3));

        return response()->json([
            'status' => 'success',
            'message' => 'Ambang batas parameter EWS berhasil diperbarui dan zonasi wilayah otomatis disesuaikan.',
            'data' => ParameterZonasi::with('penyakit')->get(),
        ]);
    }

    // =========================================================================
    // 6. EKSPOR DATA KUGI (GEOJSON) & METADATA ISO 19115 XML
    // =========================================================================

    public function eksporKugi(): JsonResponse
    {
        $rows = DB::table('v_kugi_administrasi_ar_peternakan')
            ->select(
                'objectid', 'wadmkc', 'kdcpum',
                'pop_sapipotong', 'pop_sapiperah', 'pop_kambing', 'pop_domba',
                'pop_ayampotong', 'pop_ayamtelur', 'pop_total',
                'fcode', 'metadata', 'shape', 'srs_id', 'remark',
                DB::raw('ST_AsGeoJSON(geom) as geojson')
            )
            ->get();

        $features = $rows->map(function ($r) {
            return [
                'type' => 'Feature',
                'id' => $r->objectid,
                'geometry' => json_decode($r->geojson, true),
                'properties' => [
                    'OBJECTID' => $r->objectid,
                    'WADMKC' => $r->wadmkc,
                    'KDCPUM' => $r->kdcpum,
                    'POP_SAPIPOTONG' => (int) $r->pop_sapipotong,
                    'POP_SAPIPERAH' => (int) $r->pop_sapiperah,
                    'POP_KAMBING' => (int) $r->pop_kambing,
                    'POP_DOMBA' => (int) $r->pop_domba,
                    'POP_AYAMPOTONG' => (int) $r->pop_ayampotong,
                    'POP_AYAMTELUR' => (int) $r->pop_ayamtelur,
                    'POP_TOTAL' => (int) $r->pop_total,
                    'FCODE' => $r->fcode,
                    'METADATA' => $r->metadata,
                    'SRS_ID' => $r->srs_id,
                    'REMARK' => $r->remark,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'name' => 'ADMINISTRASI_AR_PETERNAKAN_BMS_2026',
            'crs' => [
                'type' => 'name',
                'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'],
            ],
            'features' => $features,
        ]);
    }

    public function eksporIsoXml(Request $request, IsoMetadataGeneratorService $iso): Response
    {
        $type = $request->query('tipe', 'PETERNAKAN');
        $xml = $iso->generateIso19115Xml($type);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="ISO-19115-' . $type . '-BANYUMAS.xml"',
        ]);
    }
}
