<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Internal\DinasDashboardController;
use App\Http\Controllers\Internal\AdminDataController;
use App\Http\Middleware\EnsureUserRole;
use App\Models\AuditLog;

/*
|--------------------------------------------------------------------------
| Internal Portal Routes — Domain: internal-ternak.banyumaskab.go.id
| Hak Akses: Petugas Dinas & Super Admin (Diproteksi RBAC Middleware)
|--------------------------------------------------------------------------
*/

Route::middleware([EnsureUserRole::class])->group(function () {

    // =========================================================================
    // 1. DINAS DASHBOARD CONTROLLER (PETA, ANALITIK ECHARTS, LAPORAN RESMI)
    // =========================================================================
    Route::prefix('peta')->group(function () {
        Route::get('/kabupaten', [DinasDashboardController::class, 'getPetaKabupaten']);
        Route::get('/kecamatan/{id}', [DinasDashboardController::class, 'getDrilldownKecamatan']);
        Route::get('/kecamatan/{id}/drilldown', [DinasDashboardController::class, 'getDrilldownKecamatan']);
    });

    Route::prefix('analitik')->group(function () {
        Route::get('/data', [DinasDashboardController::class, 'getAnalitikData']);
        // Alias rute untuk integrasi modular
        Route::get('/metrik', [DinasDashboardController::class, 'getAnalitikData']);
    });

    Route::prefix('laporan')->group(function () {
        Route::get('/unduh', [DinasDashboardController::class, 'downloadLaporan']);
        Route::get('/download', [DinasDashboardController::class, 'downloadLaporan']);
        Route::get('/ekspor-excel', fn(\Illuminate\Http\Request $r) => app(DinasDashboardController::class)->downloadLaporan($r->merge(['format' => 'excel'])));
        Route::get('/ekspor-pdf', fn(\Illuminate\Http\Request $r) => app(DinasDashboardController::class)->downloadLaporan($r->merge(['format' => 'pdf'])));
    });

    // =========================================================================
    // 2. ADMIN DATA CONTROLLER (CRUD RESOURCE & AUDIT TRAIL)
    // =========================================================================
    Route::prefix('admin')->group(function () {
        
        // CRUD Populasi Ternak
        Route::get('/populasi', [AdminDataController::class, 'indexPopulasi']);
        Route::post('/populasi', [AdminDataController::class, 'storePopulasi']);
        Route::get('/populasi/{id}', [AdminDataController::class, 'showPopulasi']);
        Route::match(['put', 'patch'], '/populasi/{id}', [AdminDataController::class, 'updatePopulasi']);
        Route::delete('/populasi/{id}', [AdminDataController::class, 'destroyPopulasi']);

        // CRUD Produksi Ternak
        Route::get('/produksi', [AdminDataController::class, 'indexProduksi']);
        Route::post('/produksi', [AdminDataController::class, 'storeProduksi']);
        Route::get('/produksi/{id}', [AdminDataController::class, 'showProduksi']);
        Route::match(['put', 'patch'], '/produksi/{id}', [AdminDataController::class, 'updateProduksi']);
        Route::delete('/produksi/{id}', [AdminDataController::class, 'destroyProduksi']);

        // CRUD Kasus Penyakit (Wabah PMK & LSD)
        Route::get('/penyakit', [AdminDataController::class, 'indexKasus']);
        Route::post('/penyakit', [AdminDataController::class, 'storeKasus']);
        Route::get('/penyakit/{id}', [AdminDataController::class, 'showKasus']);
        Route::match(['put', 'patch'], '/penyakit/{id}', [AdminDataController::class, 'updateKasus']);
        Route::delete('/penyakit/{id}', [AdminDataController::class, 'destroyKasus']);

        // CRUD Fasilitas Geospasial RPH
        Route::get('/rph', [AdminDataController::class, 'indexRph']);
        Route::post('/rph', [AdminDataController::class, 'storeRph']);
        Route::get('/rph/{id}', [AdminDataController::class, 'showRph']);
        Route::match(['put', 'patch'], '/rph/{id}', [AdminDataController::class, 'updateRph']);
        Route::delete('/rph/{id}', [AdminDataController::class, 'destroyRph']);

        // Konfigurasi Parameter EWS & Rekalkulasi
        Route::get('/ews/parameter', [AdminDataController::class, 'getParameters']);
        Route::get('/ews/parameters', [AdminDataController::class, 'getParameters']);
        Route::match(['post', 'put'], '/ews/parameter', [AdminDataController::class, 'updateParameters']);
        Route::match(['post', 'put'], '/ews/parameters', [AdminDataController::class, 'updateParameters']);
        Route::post('/ews/kalkulasi-ulang', fn(\App\Services\ZoningEngineService $eng) => response()->json([
            'status' => 'success',
            'data' => $eng->recalculateZoning((int) date('Y'), (int) ceil(date('n') / 3))
        ]));

        // Ekspor Geospasial KUGI & ISO 19115 XML
        Route::get('/geospasial/ekspor-kugi', [AdminDataController::class, 'eksporKugi']);
        Route::get('/kugi/export', [AdminDataController::class, 'eksporKugi']);
        Route::get('/geospasial/metadata-iso', [AdminDataController::class, 'eksporIsoXml']);
        Route::get('/iso-xml/export', [AdminDataController::class, 'eksporIsoXml']);

        // Audit Trail Logging Forensik
        Route::get('/audit-logs', function () {
            return response()->json(
                AuditLog::with('user:id,nama,email')
                    ->orderByDesc('created_at')
                    ->paginate(50)
            );
        });
    });

});
