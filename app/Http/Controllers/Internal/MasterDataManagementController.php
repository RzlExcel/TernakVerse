<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FasilitasRph;
use App\Models\KasusPenyakit;
use App\Models\KategoriProduksi;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\ParameterZonasi;
use App\Models\Penyakit;
use App\Models\PopulasiTernak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class MasterDataManagementController extends Controller
{
    /**
     * Display a listing of master data (commodities, facilities, and kecamatan HMT).
     */
    public function index(): JsonResponse
    {
        $komoditas = Komoditas::select(['id', 'kode', 'nama', 'kelompok', 'aktif'])
            ->orderBy('id')
            ->get();

        $fasilitasRecords = FasilitasRph::with('kecamatan')
            ->withGeoJson('geom_titik')
            ->orderBy('id')
            ->get();

        $fasilitas = $fasilitasRecords->map(function (FasilitasRph $f): array {
            $lng = null;
            $lat = null;
            $geom = $f->geom_titik;

            if (is_array($geom)) {
                if (isset($geom['coordinates']) && is_array($geom['coordinates'])) {
                    $lng = isset($geom['coordinates'][0]) ? (float) $geom['coordinates'][0] : null;
                    $lat = isset($geom['coordinates'][1]) ? (float) $geom['coordinates'][1] : null;
                }
            } elseif (is_string($geom)) {
                $decoded = json_decode($geom, true);
                if (is_array($decoded) && isset($decoded['coordinates']) && is_array($decoded['coordinates'])) {
                    $lng = isset($decoded['coordinates'][0]) ? (float) $decoded['coordinates'][0] : null;
                    $lat = isset($decoded['coordinates'][1]) ? (float) $decoded['coordinates'][1] : null;
                } elseif (preg_match('/POINT\s*\(\s*([-\d.]+)\s+([-\d.]+)\s*\)/i', $geom, $matches)) {
                    $lng = (float) $matches[1];
                    $lat = (float) $matches[2];
                }
            }

            $item = $f->toArray();
            $item['lat'] = $lat;
            $item['lng'] = $lng;
            $item['latitude'] = $lat;
            $item['longitude'] = $lng;

            return $item;
        });

        $kecamatan = Kecamatan::select(['id', 'kode_kecamatan', 'nama_kecamatan', 'luas_hmt_ha'])
            ->orderBy('id')
            ->get();

        $penyakit = Penyakit::with('parameterZonasi')
            ->orderBy('id')
            ->get();

        $kategoriProduksi = KategoriProduksi::orderBy('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'komoditas' => $komoditas,
            'fasilitas' => $fasilitas,
            'kecamatan' => $kecamatan,
            'penyakit' => $penyakit,
            'kategori_produksi' => $kategoriProduksi,
            'data' => [
                'komoditas' => $komoditas,
                'fasilitas' => $fasilitas,
                'kecamatan' => $kecamatan,
                'penyakit' => $penyakit,
                'kategori_produksi' => $kategoriProduksi,
            ],
        ]);
    }

    /**
     * Store a newly created commodity.
     */
    public function storeKomoditas(Request $request): JsonResponse
    {
        if ($request->has('kode') && is_string($request->input('kode'))) {
            $request->merge(['kode' => strtoupper(trim($request->input('kode')))]);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:komoditas,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'kelompok' => ['required', 'string', Rule::in(['Ternak Besar', 'Ternak Kecil', 'Unggas'])],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $komoditas = Komoditas::create([
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'kelompok' => $validated['kelompok'],
            'aktif' => $request->boolean('aktif', true),
        ]);

        AuditLog::catat('CREATE', 'komoditas', (int) $komoditas->id, null, $komoditas->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Komoditas berhasil ditambahkan.',
            'data' => $komoditas,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing commodity.
     */
    public function updateKomoditas(Request $request, int $id): JsonResponse
    {
        $komoditas = Komoditas::findOrFail($id);
        $old = $komoditas->toArray();

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:100'],
            'kelompok' => ['sometimes', 'required', 'string', Rule::in(['Ternak Besar', 'Ternak Kecil', 'Unggas'])],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $komoditas->update($validated);

        AuditLog::catat('UPDATE', 'komoditas', (int) $komoditas->id, $old, $komoditas->fresh()->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Komoditas berhasil diperbarui.',
            'data' => $komoditas->fresh(),
        ]);
    }

    /**
     * Delete or deactivate a commodity.
     */
    public function destroyKomoditas(int $id): JsonResponse
    {
        $komoditas = Komoditas::findOrFail($id);
        $old = $komoditas->toArray();

        $hasRelations = $komoditas->populasiTernak()->exists()
            || $komoditas->produksiTernak()->exists()
            || $komoditas->kasusPenyakit()->exists();

        if ($hasRelations) {
            $komoditas->update(['aktif' => false]);
            AuditLog::catat('UPDATE', 'komoditas', (int) $komoditas->id, $old, $komoditas->fresh()->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Komoditas memiliki riwayat transaksi/data terkait, status berhasil dinonaktifkan.',
                'data' => $komoditas->fresh(),
            ]);
        }

        $komoditas->delete();
        AuditLog::catat('DELETE', 'komoditas', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Komoditas berhasil dihapus.',
        ]);
    }

    /**
     * Store a newly created facility (RPH, Puskeswan, Pasar Hewan).
     */
    public function storeFasilitas(Request $request): JsonResponse
    {
        if (! $request->has('lat') && $request->has('latitude')) {
            $request->merge(['lat' => $request->input('latitude')]);
        }
        if (! $request->has('lng') && $request->has('longitude')) {
            $request->merge(['lng' => $request->input('longitude')]);
        }

        $validated = $request->validate([
            'kecamatan_id' => ['required', 'integer', 'exists:kecamatan,id'],
            'nama_rph' => ['required', 'string', 'max:150'],
            'alamat_lengkap' => ['required', 'string'],
            'tipe_fasilitas' => ['required', 'string', Rule::in(['RPH', 'Puskeswan', 'Pasar Hewan'])],
            'status_operasional' => ['required', 'string', 'max:50'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'hari_pasaran' => ['nullable', 'string', 'max:100'],
            'wilayah_binaan' => ['nullable', 'string'],
            'penanggung_jawab' => ['nullable', 'string', 'max:150'],
            'nomor_kontak' => ['nullable', 'string', 'max:50'],
            'jenis_pengelola' => ['nullable', 'string', 'max:50'],
            'jenis_hewan_potong' => ['nullable', 'string', 'max:100'],
            'kapasitas_potong_ekor_hari' => ['nullable', 'integer', 'min:0'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];

        $facility = new FasilitasRph;
        $facility->kecamatan_id = (int) $validated['kecamatan_id'];
        $facility->nama_rph = $validated['nama_rph'];
        $facility->alamat_lengkap = $validated['alamat_lengkap'];
        $facility->tipe_fasilitas = $validated['tipe_fasilitas'];
        $facility->status_operasional = $validated['status_operasional'];
        $facility->hari_pasaran = $validated['hari_pasaran'] ?? null;
        $facility->wilayah_binaan = $validated['wilayah_binaan'] ?? null;
        $facility->penanggung_jawab = $validated['penanggung_jawab'] ?? null;
        $facility->nomor_kontak = $validated['nomor_kontak'] ?? null;
        $facility->jenis_pengelola = $validated['jenis_pengelola'] ?? 'Pemerintah';
        $facility->jenis_hewan_potong = $validated['jenis_hewan_potong'] ?? null;
        $facility->kapasitas_potong_ekor_hari = (int) ($validated['kapasitas_potong_ekor_hari'] ?? 0);

        if (DB::getDriverName() === 'pgsql') {
            $facility->geom_titik = DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)");
        } else {
            $facility->geom_titik = [
                'type' => 'Point',
                'coordinates' => [$lng, $lat],
            ];
        }

        $facility->save();

        AuditLog::catat('CREATE', 'fasilitas_rph', (int) $facility->id, null, $facility->toArray());

        $result = $facility->fresh('kecamatan');
        $data = $result ? $result->toArray() : $facility->toArray();
        $data['lat'] = $lat;
        $data['lng'] = $lng;

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas baru berhasil disimpan.',
            'data' => $data,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing facility.
     */
    public function updateFasilitas(Request $request, int $id): JsonResponse
    {
        if (! $request->has('lat') && $request->has('latitude')) {
            $request->merge(['lat' => $request->input('latitude')]);
        }
        if (! $request->has('lng') && $request->has('longitude')) {
            $request->merge(['lng' => $request->input('longitude')]);
        }

        $facility = FasilitasRph::findOrFail($id);
        $old = $facility->toArray();

        $validated = $request->validate([
            'kecamatan_id' => ['sometimes', 'required', 'integer', 'exists:kecamatan,id'],
            'nama_rph' => ['sometimes', 'required', 'string', 'max:150'],
            'alamat_lengkap' => ['sometimes', 'required', 'string'],
            'tipe_fasilitas' => ['sometimes', 'required', 'string', Rule::in(['RPH', 'Puskeswan', 'Pasar Hewan'])],
            'status_operasional' => ['sometimes', 'required', 'string', 'max:50'],
            'lat' => ['sometimes', 'required', 'numeric'],
            'lng' => ['sometimes', 'required', 'numeric'],
            'hari_pasaran' => ['nullable', 'string', 'max:100'],
            'wilayah_binaan' => ['nullable', 'string'],
            'penanggung_jawab' => ['nullable', 'string', 'max:150'],
            'nomor_kontak' => ['nullable', 'string', 'max:50'],
            'jenis_pengelola' => ['nullable', 'string', 'max:50'],
            'jenis_hewan_potong' => ['nullable', 'string', 'max:100'],
            'kapasitas_potong_ekor_hari' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach ([
            'kecamatan_id',
            'nama_rph',
            'alamat_lengkap',
            'tipe_fasilitas',
            'status_operasional',
            'hari_pasaran',
            'wilayah_binaan',
            'penanggung_jawab',
            'nomor_kontak',
            'jenis_pengelola',
            'jenis_hewan_potong',
            'kapasitas_potong_ekor_hari',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $facility->{$field} = $validated[$field];
            }
        }

        if (isset($validated['lat']) || isset($validated['lng'])) {
            $existingLng = null;
            $existingLat = null;
            $geom = $facility->geom_titik;
            if (is_array($geom) && isset($geom['coordinates']) && is_array($geom['coordinates'])) {
                $existingLng = $geom['coordinates'][0] ?? null;
                $existingLat = $geom['coordinates'][1] ?? null;
            }

            $newLng = isset($validated['lng']) ? (float) $validated['lng'] : ($existingLng !== null ? (float) $existingLng : null);
            $newLat = isset($validated['lat']) ? (float) $validated['lat'] : ($existingLat !== null ? (float) $existingLat : null);

            if ($newLng !== null && $newLat !== null) {
                if (DB::getDriverName() === 'pgsql') {
                    $facility->geom_titik = DB::raw("ST_SetSRID(ST_MakePoint({$newLng}, {$newLat}), 4326)");
                } else {
                    $facility->geom_titik = [
                        'type' => 'Point',
                        'coordinates' => [$newLng, $newLat],
                    ];
                }
            }
        }

        $facility->save();

        AuditLog::catat('UPDATE', 'fasilitas_rph', (int) $facility->id, $old, $facility->fresh()->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Data fasilitas berhasil diperbarui.',
            'data' => $facility->fresh('kecamatan'),
        ]);
    }

    /**
     * Delete a facility.
     */
    public function destroyFasilitas(int $id): JsonResponse
    {
        $facility = FasilitasRph::findOrFail($id);
        $old = $facility->toArray();
        $facility->delete();

        AuditLog::catat('DELETE', 'fasilitas_rph', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas berhasil dihapus.',
        ]);
    }

    /**
     * Update forage land area (luas_hmt_ha) for a kecamatan.
     */
    public function updateKecamatanHmt(Request $request, int $id): JsonResponse
    {
        $kecamatan = Kecamatan::findOrFail($id);
        $old = $kecamatan->toArray();

        $validated = $request->validate([
            'luas_hmt_ha' => ['required', 'numeric', 'min:0'],
        ]);

        $kecamatan->update([
            'luas_hmt_ha' => $validated['luas_hmt_ha'],
        ]);

        AuditLog::catat('UPDATE', 'kecamatan', (int) $kecamatan->id, $old, $kecamatan->fresh()->toArray());

        return response()->json([
            'status' => 'success',
            'message' => "Luas lahan HMT Kecamatan {$kecamatan->nama_kecamatan} berhasil diperbarui.",
            'data' => $kecamatan->fresh(),
        ]);
    }

    /**
     * Display a listing of diseases (penyakit) with EWS parameters.
     */
    public function indexDiseases(): JsonResponse
    {
        $diseases = Penyakit::with('parameterZonasi')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $diseases,
        ]);
    }

    /**
     * Store a newly created disease.
     */
    public function storeDisease(Request $request): JsonResponse
    {
        if ($request->has('kode') && is_string($request->input('kode'))) {
            $request->merge(['kode' => strtoupper(trim($request->input('kode')))]);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:penyakit,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'nama_latin' => ['nullable', 'string', 'max:150'],
            'tingkat_bahaya' => ['required', 'string', Rule::in(['Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'])],
            'fcode' => ['nullable', 'string', 'max:20'],
            'ambang_kuning_maks' => ['nullable', 'integer', 'min:0'],
            'jendela_hari_aktif' => ['nullable', 'integer', 'min:1'],
        ]);

        $disease = Penyakit::create([
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'nama_latin' => $validated['nama_latin'] ?? null,
            'tingkat_bahaya' => $validated['tingkat_bahaya'],
            'fcode' => $validated['fcode'] ?? 'CA0010',
        ]);

        if (array_key_exists('ambang_kuning_maks', $validated) || array_key_exists('jendela_hari_aktif', $validated)) {
            ParameterZonasi::create([
                'penyakit_id' => $disease->id,
                'jendela_hari_aktif' => $validated['jendela_hari_aktif'] ?? 14,
                'ambang_kuning_maks' => $validated['ambang_kuning_maks'] ?? 5,
            ]);
        }

        AuditLog::catat('CREATE', 'penyakit', (int) $disease->id, null, $disease->fresh('parameterZonasi')->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Data penyakit berhasil ditambahkan.',
            'data' => $disease->fresh('parameterZonasi'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing disease.
     */
    public function updateDisease(Request $request, int $id): JsonResponse
    {
        $disease = Penyakit::findOrFail($id);
        $old = $disease->load('parameterZonasi')->toArray();

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:100'],
            'nama_latin' => ['sometimes', 'nullable', 'string', 'max:150'],
            'tingkat_bahaya' => ['sometimes', 'required', 'string', Rule::in(['Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'])],
            'fcode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'ambang_kuning_maks' => ['sometimes', 'integer', 'min:0'],
            'jendela_hari_aktif' => ['sometimes', 'integer', 'min:1'],
        ]);

        $diseaseFields = array_intersect_key($validated, array_flip(['nama', 'nama_latin', 'tingkat_bahaya', 'fcode']));
        if (! empty($diseaseFields)) {
            $disease->update($diseaseFields);
        }

        if (array_key_exists('ambang_kuning_maks', $validated) || array_key_exists('jendela_hari_aktif', $validated)) {
            $param = ParameterZonasi::firstOrNew(['penyakit_id' => $disease->id]);
            if (isset($validated['ambang_kuning_maks'])) {
                $param->ambang_kuning_maks = $validated['ambang_kuning_maks'];
            }
            if (isset($validated['jendela_hari_aktif'])) {
                $param->jendela_hari_aktif = $validated['jendela_hari_aktif'];
            }
            $param->save();
        }

        AuditLog::catat('UPDATE', 'penyakit', (int) $disease->id, $old, $disease->fresh('parameterZonasi')->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Data penyakit berhasil diperbarui.',
            'data' => $disease->fresh('parameterZonasi'),
        ]);
    }

    /**
     * Delete a disease.
     */
    public function destroyDisease(int $id): JsonResponse
    {
        $disease = Penyakit::findOrFail($id);
        $old = $disease->load('parameterZonasi')->toArray();

        $hasRelations = $disease->kasusPenyakit()->exists()
            || $disease->zonaSnapshotsKecamatan()->exists()
            || $disease->zonaSnapshotsDesa()->exists();

        if ($hasRelations) {
            return response()->json([
                'status' => 'error',
                'message' => "Penyakit {$disease->nama} memiliki rekaman kasus aktif atau histori snapshot zonasi dan tidak dapat dihapus.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($disease->parameterZonasi) {
            $disease->parameterZonasi->delete();
        }

        $disease->delete();

        AuditLog::catat('DELETE', 'penyakit', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Data penyakit berhasil dihapus.',
        ]);
    }

    /**
     * Display a listing of production categories.
     */
    public function indexProductionCategories(): JsonResponse
    {
        $categories = KategoriProduksi::orderBy('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created production category.
     */
    public function storeProductionCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_produksi,nama'],
            'satuan' => ['required', 'string', 'max:50'],
        ]);

        $category = KategoriProduksi::create([
            'nama' => $validated['nama'],
            'satuan' => $validated['satuan'],
        ]);

        AuditLog::catat('CREATE', 'kategori_produksi', (int) $category->id, null, $category->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori produksi berhasil ditambahkan.',
            'data' => $category,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing production category.
     */
    public function updateProductionCategory(Request $request, int $id): JsonResponse
    {
        $category = KategoriProduksi::findOrFail($id);
        $old = $category->toArray();

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('kategori_produksi', 'nama')->ignore($id)],
            'satuan' => ['sometimes', 'required', 'string', 'max:50'],
        ]);

        $category->update($validated);

        AuditLog::catat('UPDATE', 'kategori_produksi', (int) $category->id, $old, $category->fresh()->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori produksi berhasil diperbarui.',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Delete a production category.
     */
    public function destroyProductionCategory(int $id): JsonResponse
    {
        $category = KategoriProduksi::findOrFail($id);
        $old = $category->toArray();

        if ($category->produksiTernak()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => "Kategori produksi {$category->nama} memiliki relasi data produksi aktif dan tidak dapat dihapus.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category->delete();

        AuditLog::catat('DELETE', 'kategori_produksi', $id, $old, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori produksi berhasil dihapus.',
        ]);
    }

    /**
     * Get system maintenance and infrastructure status.
     */
    public function maintenanceStatus(): JsonResponse
    {
        $dbConnected = false;
        $driver = 'unknown';

        try {
            DB::connection()->getPdo();
            $dbConnected = true;
            $driver = DB::getDriverName();
        } catch (\Throwable) {
            $dbConnected = false;
        }

        return response()->json([
            'status' => 'success',
            'database' => $dbConnected ? 'connected' : 'disconnected',
            'driver' => $driver,
            'server_time' => now()->toIso8601String(),
            'cache_status' => 'active',
            'cache_driver' => config('cache.default', 'file'),
        ]);
    }

    /**
     * Clear system and application caches.
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('optimize:clear');
            $output = Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Cache aplikasi dan konfigurasi berhasil dibersihkan.',
                'output' => trim($output),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membersihkan cache: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store quarterly survey data for a kecamatan and synchronize to populasi_ternak and kasus_penyakit.
     */
    public function storeSurveyQuarterly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'kode_kec' => 'nullable|string',
            'tahun' => 'required|integer|between:2020,2035',
            'triwulan' => 'required|integer|between:1,4',
            'sapi_potong' => 'nullable|numeric|min:0',
            'sapi_perah' => 'nullable|numeric|min:0',
            'kambing' => 'nullable|numeric|min:0',
            'kambing_perah' => 'nullable|numeric|min:0',
            'domba' => 'nullable|numeric|min:0',
            'ayam_broiler' => 'nullable|numeric|min:0',
            'ayam_petelur' => 'nullable|numeric|min:0',
            'pmk' => 'nullable|integer|min:0',
            'lsd' => 'nullable|integer|min:0',
        ]);

        $nama = trim($validated['nama']);
        $cleanNama = trim((string) preg_replace('/^kecamatan\s+/i', '', $nama));
        $tahun = (int) $validated['tahun'];
        $triwulan = (int) $validated['triwulan'];

        $kecamatan = null;
        if ($request->filled('kode_kec')) {
            $kecamatan = Kecamatan::where('kode_kecamatan', trim((string) $request->input('kode_kec')))->first();
        }

        if (! $kecamatan) {
            $kecamatan = Kecamatan::where(function ($q) use ($cleanNama, $nama) {
                $q->whereRaw('LOWER(nama_kecamatan) = ?', [strtolower($cleanNama)])
                  ->orWhereRaw('LOWER(nama_kecamatan) = ?', [strtolower($nama)]);

                if (strlen($cleanNama) >= 3) {
                    $q->orWhere('nama_kecamatan', 'LIKE', '%' . $cleanNama . '%');
                }
            })->first();
        }

        if (! $kecamatan) {
            return response()->json([
                'status' => 'error',
                'message' => "Wilayah/Kecamatan '{$request->nama}' tidak ditemukan dalam basis data.",
            ], 422);
        }

        $desa = DB::table('desa')->where('kecamatan_id', $kecamatan->id)->first();
        if (! $desa) {
            return response()->json([
                'status' => 'error',
                'message' => "Desa pada kecamatan '{$kecamatan->nama_kecamatan}' belum terdaftar.",
            ], 422);
        }

        $komoditasMap = Komoditas::pluck('id', 'kode')->all();

        $commodityCounts = [
            'SAPI_POTONG' => isset($validated['sapi_potong']) && $validated['sapi_potong'] !== null ? (float) $validated['sapi_potong'] : null,
            'SAPI_PERAH' => isset($validated['sapi_perah']) && $validated['sapi_perah'] !== null ? (float) $validated['sapi_perah'] : null,
            'KAMBING' => isset($validated['kambing']) && $validated['kambing'] !== null ? (float) $validated['kambing'] : null,
            'KAMBING_PERAH' => isset($validated['kambing_perah']) && $validated['kambing_perah'] !== null ? (float) $validated['kambing_perah'] : null,
            'DOMBA' => isset($validated['domba']) && $validated['domba'] !== null ? (float) $validated['domba'] : null,
            'AYAM_POTONG' => isset($validated['ayam_broiler']) && $validated['ayam_broiler'] !== null ? (float) $validated['ayam_broiler'] : null,
            'AYAM_TELUR' => isset($validated['ayam_petelur']) && $validated['ayam_petelur'] !== null ? (float) $validated['ayam_petelur'] : null,
        ];

        foreach ($commodityCounts as $code => $count) {
            if ($count !== null && $count >= 0 && isset($komoditasMap[$code])) {
                $komId = (int) $komoditasMap[$code];
                $jantan = (int) ceil($count * 0.4);
                $betina = (int) floor($count * 0.6);

                PopulasiTernak::updateOrCreate(
                    [
                        'desa_id' => $desa->id,
                        'komoditas_id' => $komId,
                        'tahun' => $tahun,
                        'triwulan' => $triwulan,
                    ],
                    [
                        'jumlah_jantan' => $jantan,
                        'jumlah_betina' => $betina,
                    ]
                );
            }
        }

        $penyakitMap = Penyakit::pluck('id', 'kode')->all();
        $defaultKomoditasId = (int) ($komoditasMap['SAPI_POTONG'] ?? ($komoditasMap['SAPI_PERAH'] ?? 1));
        $month = str_pad((string) ($triwulan * 3), 2, '0', STR_PAD_LEFT);
        $tanggalLaporan = "{$tahun}-{$month}-15";

        $pmkCount = isset($validated['pmk']) && $validated['pmk'] !== null ? (int) $validated['pmk'] : null;
        if ($pmkCount !== null && $pmkCount >= 0 && isset($penyakitMap['PMK'])) {
            KasusPenyakit::updateOrCreate(
                [
                    'desa_id' => $desa->id,
                    'penyakit_id' => (int) $penyakitMap['PMK'],
                    'komoditas_id' => $defaultKomoditasId,
                    'tahun' => $tahun,
                    'triwulan' => $triwulan,
                ],
                [
                    'tanggal_laporan' => $tanggalLaporan,
                    'kasus_positif' => $pmkCount,
                    'kasus_suspek' => (int) round($pmkCount * 0.3),
                    'kasus_sembuh' => (int) round($pmkCount * 0.1),
                    'kasus_mati' => 0,
                    'potong_bersyarat' => 0,
                    'catatan_lapangan' => 'Laporan surveilans lapangan Dinas Perikanan dan Peternakan Banyumas',
                ]
            );
        }

        $lsdCount = isset($validated['lsd']) && $validated['lsd'] !== null ? (int) $validated['lsd'] : null;
        if ($lsdCount !== null && $lsdCount >= 0 && isset($penyakitMap['LSD'])) {
            KasusPenyakit::updateOrCreate(
                [
                    'desa_id' => $desa->id,
                    'penyakit_id' => (int) $penyakitMap['LSD'],
                    'komoditas_id' => $defaultKomoditasId,
                    'tahun' => $tahun,
                    'triwulan' => $triwulan,
                ],
                [
                    'tanggal_laporan' => $tanggalLaporan,
                    'kasus_positif' => $lsdCount,
                    'kasus_suspek' => (int) round($lsdCount * 0.3),
                    'kasus_sembuh' => (int) round($lsdCount * 0.1),
                    'kasus_mati' => 0,
                    'potong_bersyarat' => 0,
                    'catatan_lapangan' => 'Laporan surveilans lapangan Dinas Perikanan dan Peternakan Banyumas',
                ]
            );
        }

        Cache::flush();
        AuditLog::catat('UPDATE', 'populasi_ternak', (int) $kecamatan->id, null, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data triwulan berhasil disimpan dan disinkronisasi ke basis data.',
        ]);
    }
}
