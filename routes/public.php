<?php

use Illuminate\Support\Facades\Route;
use App\Models\Kecamatan;
use App\Models\Komoditas;
use App\Models\Penyakit;
use App\Models\FasilitasRph;
use App\Models\ZonaSnapshotKecamatan;

/*
|--------------------------------------------------------------------------
| Public WebGIS Routes — Domain: ternak.banyumaskab.go.id
| Proteksi: Zero Data Leakage (Hanya status risiko kualitatif, tanpa data kuantitatif privat)
|--------------------------------------------------------------------------
*/

Route::get('/zonasi', function () {
    // Ambil data snapshot zonasi risiko terkini untuk 27 kecamatan
    $kecamatanList = Kecamatan::withGeoJson('geom')->get();

    $features = $kecamatanList->map(function ($kec) {
        $snapshot = ZonaSnapshotKecamatan::where('kecamatan_id', $kec->id)
            ->latest('waktu_kalkulasi')
            ->first();

        $statusZona = $snapshot ? $snapshot->status_zona : 'HIJAU';

        // Pesan biosekuriti kualitatif publik
        $pesanBiosekuriti = match ($statusZona) {
            'MERAH' => 'Zona Merah: Pembatasan ketat lalu lintas ternak. Segera lapor petugas jika ada gejala PMK/LSD.',
            'KUNING' => 'Zona Waspada: Perketat sanitasi kandang dan desinfeksi kendaraan pengangkut hewan.',
            default => 'Zona Aman: Pertahankan biosekuriti standar dan vaksinasi berkala.',
        };

        return [
            'type' => 'Feature',
            'id' => $kec->id,
            'geometry' => is_string($kec->geom) ? json_decode($kec->geom, true) : $kec->geom,
            'properties' => [
                'kode_kecamatan' => $kec->kode_kecamatan,
                'nama_kecamatan' => $kec->nama_kecamatan,
                'luas_wilayah_km2' => (float) $kec->luas_wilayah_km2,
                'status_zona' => $statusZona,
                'pesan_biosekuriti' => $pesanBiosekuriti,
            ],
        ];
    });

    return response()->json([
        'type' => 'FeatureCollection',
        'metadata' => [
            'kabupaten' => 'Banyumas',
            'srs' => 'EPSG:4326',
            'updated_at' => now()->toIso8601String(),
        ],
        'features' => $features,
    ]);
});

Route::get('/panduan-pmk-lsd', function () {
    $penyakit = Penyakit::all(['kode', 'nama', 'nama_latin', 'tingkat_bahaya']);
    return response()->json([
        'status' => 'success',
        'data' => $penyakit,
    ]);
});

Route::get('/komoditas', function () {
    return response()->json([
        'status' => 'success',
        'data' => Komoditas::aktif()->get(['id', 'kode', 'nama', 'kelompok']),
    ]);
});

Route::get('/rph', function () {
    $rph = FasilitasRph::withGeoJson('geom_titik')->get();
    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $rph->map(function ($item) {
            return [
                'type' => 'Feature',
                'id' => $item->id,
                'geometry' => is_string($item->geom_titik) ? json_decode($item->geom_titik, true) : $item->geom_titik,
                'properties' => [
                    'nama_rph' => $item->nama_rph,
                    'jenis_pengelola' => $item->jenis_pengelola,
                    'jenis_hewan_potong' => $item->jenis_hewan_potong,
                    'status_operasional' => $item->status_operasional,
                ],
            ];
        }),
    ]);
});
