<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\DinasDashboardController;
use App\Http\Controllers\Internal\MasterDataManagementController;
use App\Http\Controllers\Internal\SystemGovernanceController;
use App\Http\Middleware\CheckIpAccessAndMaintenance;
use App\Http\Middleware\EnsureUserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes — TERNAKVERSE WebGIS Banyumas (Inertia.js v2 + Vue 3)
|--------------------------------------------------------------------------
| Terintegrasi Seluruh Modul (Portal Publik, Portal Dinas, Panel Admin, & FE)
|--------------------------------------------------------------------------
*/

// 1. PORTAL PUBLIK (Landing Page — WebGIS Peta Banyumas)
$publicPortalHandler = function (Request $request) {
    // Parameter preview eksplisit (e.g. ?preview=1) mengizinkan staf internal melihat tampilan publik
    $isPreview = $request->query('preview') === '1' || $request->boolean('preview');

    if (! $isPreview) {
        $user = Auth::user() ?? $request->user();
        $role = $user?->role ?? ($request->hasSession() ? $request->session()->get('authenticated_role') : null);

        if ($role && in_array($role, ['superadmin', 'admin', 'admin_dinas'], true)) {
            return redirect()->route('internal.admin');
        }

        if ($role === 'stakeholder' || $role === 'dinas') {
            return redirect()->route('internal.dinas');
        }
    }

    return Inertia::render('Public/Portal');
};

Route::middleware([CheckIpAccessAndMaintenance::class])->group(function () use ($publicPortalHandler) {
    Route::get('/', $publicPortalHandler)->name('home');
    Route::get('/portal', $publicPortalHandler)->name('public.portal');
    Route::get('/welcome', function () {
        return Inertia::render('Welcome');
    })->name('welcome');
    Route::get('/design-rule', function () {
        return Inertia::render('DesignRule');
    })->name('design.rule');
});

// 2. PORTAL INTERNAL PETUGAS (Pintu Masuk Login Sesi)
Route::get('/internal/login', function () {
    return Inertia::render('Internal/Login');
})->name('internal.login');

Route::get('/internal', function () {
    return redirect()->route('internal.login');
});

Route::get('/login', function () {
    return redirect()->route('internal.login');
})->name('login');

Route::post('/internal/login', function (Request $request) {
    $roleInput = (string) $request->input('role', 'dinas');
    $roleSlug = ($roleInput === 'admin') ? 'admin' : 'stakeholder';
    $emailInput = trim((string) $request->input('email', ''));
    $email = $emailInput !== '' ? $emailInput : ($roleSlug === 'admin' ? 'admin.diskanak@banyumaskab.go.id' : 'drh.sulistyo@banyumaskab.go.id');
    $password = (string) $request->input('password', '');

    $authenticatedUser = null;
    $dbOffline = false;

    // Coba autentikasi via database jika koneksi aktif
    try {
        DB::connection()->getPdo();
    } catch (Throwable) {
        $dbOffline = true;
    }

    if (! $dbOffline) {
        try {
            // Jika akun aparatur standar belum ada di database pada environment local/testing, lakukan auto-seed
            if (app()->environment('local', 'testing')
                && ! User::where('email', $email)->exists()
                && in_array($email, ['drh.sulistyo@banyumaskab.go.id', 'admin.diskanak@banyumaskab.go.id', 'superadmin@banyumaskab.go.id', 'admin@banyumaskab.go.id', 'dinas@banyumaskab.go.id'], true)
            ) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\UserRoleSeeder', '--force' => true]);
            }

            if (Auth::attempt(['email' => $email, 'password' => $password])) {
                $authenticatedUser = Auth::user();
            } else {
                return back()->withErrors([
                    'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
                ]);
            }
        } catch (Throwable) {
            $dbOffline = true;
        }
    }

    // Fallback akun in-memory HANYA jika DB offline DAN environment local / testing
    if (! $authenticatedUser) {
        if ($dbOffline && app()->environment('local', 'testing')) {
            $authenticatedUser = new User([
                'id' => $roleSlug === 'admin' ? 1 : 2,
                'nama' => $roleSlug === 'admin' ? 'Super Administrator' : 'drh. Sulistyo (Petugas Dinas)',
                'name' => $roleSlug === 'admin' ? 'Super Administrator' : 'drh. Sulistyo (Petugas Dinas)',
                'email' => $email,
                'role' => $roleSlug,
                'aktif' => true,
            ]);
            Auth::login($authenticatedUser);
        } else {
            return back()->withErrors([
                'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
        }
    }

    // Simpan penanda sesi otorisasi
    $request->session()->put('authenticated_user_id', $authenticatedUser->id);
    $request->session()->put('authenticated_role', $authenticatedUser->role);
    $request->session()->put('authenticated_email', $authenticatedUser->email);
    $request->session()->put('authenticated_name', $authenticatedUser->nama ?? $authenticatedUser->name);

    $request->session()->regenerate();

    if (in_array($authenticatedUser->role, ['superadmin', 'admin', 'admin_dinas'], true)) {
        return redirect()->route('internal.admin');
    }

    return redirect()->route('internal.dinas');
})->middleware('throttle:10,1')->name('internal.login.submit');

Route::match(['get', 'post'], '/internal/logout', function (Request $request) {
    Auth::logout();
    $request->session()->forget([
        'authenticated_user_id',
        'authenticated_role',
        'authenticated_email',
        'authenticated_name',
    ]);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('internal.login');
})->name('internal.logout');

// 3. PORTAL DINAS (Analisis Kuantitatif & Kewaspadaan Wabah)
Route::middleware([EnsureUserRole::class.':superadmin,admin_dinas,admin,stakeholder', CheckIpAccessAndMaintenance::class])->group(function () {
    Route::get('/internal/dinas', function () {
        return Inertia::render('Internal/DinasPortal');
    })->name('internal.dinas');

    // Route Dinas Dashboard Handshake Data (DinasAnalyticsService + ZoningEngineService)
    Route::get('/dinas/dashboard', [DinasDashboardController::class, 'index'])->name('dinas.dashboard');

    // Unduh Laporan Resmi Eksekutif (PDF & Excel)
    Route::get('/internal/laporan/ekspor-pdf', [App\Http\Controllers\Internal\DinasDashboardController::class, 'downloadLaporan'])->name('internal.laporan.pdf');
    Route::get('/internal/laporan/ekspor-excel', fn (Request $r) => app(App\Http\Controllers\Internal\DinasDashboardController::class)->downloadLaporan($r->merge(['format' => 'excel'])))->name('internal.laporan.excel');
});

// 4. PANEL ADMIN TEKNIS (Staf Teknis - Tata Letak 1160px & CRUD Data)
Route::middleware([EnsureUserRole::class.':superadmin,admin_dinas,admin', CheckIpAccessAndMaintenance::class])->group(function () {
    Route::get('/internal/admin', function () {
        return Inertia::render('Internal/AdminPortal');
    })->name('internal.admin');

    // Panel Admin Proteksi Penuh & Audit Logs
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/panel', [AdminPanelController::class, 'index'])->name('panel');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');

        // Master Data Management API (Komoditas, Fasilitas RPH, Lahan HMT, Penyakit, Produksi, Maintenance)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/master-data', [MasterDataManagementController::class, 'index'])->name('master-data');
            Route::post('/commodities', [MasterDataManagementController::class, 'storeKomoditas'])->name('commodities.store');
            Route::put('/commodities/{id}', [MasterDataManagementController::class, 'updateKomoditas'])->name('commodities.update');
            Route::delete('/commodities/{id}', [MasterDataManagementController::class, 'destroyKomoditas'])->name('commodities.destroy');
            Route::post('/facilities', [MasterDataManagementController::class, 'storeFasilitas'])->name('facilities.store');
            Route::put('/facilities/{id}', [MasterDataManagementController::class, 'updateFasilitas'])->name('facilities.update');
            Route::delete('/facilities/{id}', [MasterDataManagementController::class, 'destroyFasilitas'])->name('facilities.destroy');
            Route::put('/kecamatan/{id}/hmt', [MasterDataManagementController::class, 'updateKecamatanHmt'])->name('kecamatan.hmt.update');
            Route::post('/survey-quarterly', [MasterDataManagementController::class, 'storeSurveyQuarterly'])->name('survey.quarterly.store');

            // CRUD Penyakit & Parameter EWS
            Route::get('/diseases', [MasterDataManagementController::class, 'indexDiseases'])->name('diseases.index');
            Route::post('/diseases', [MasterDataManagementController::class, 'storeDisease'])->name('diseases.store');
            Route::put('/diseases/{id}', [MasterDataManagementController::class, 'updateDisease'])->name('diseases.update');
            Route::delete('/diseases/{id}', [MasterDataManagementController::class, 'destroyDisease'])->name('diseases.destroy');

            // CRUD Kategori Produksi
            Route::get('/production-categories', [MasterDataManagementController::class, 'indexProductionCategories'])->name('production-categories.index');
            Route::post('/production-categories', [MasterDataManagementController::class, 'storeProductionCategory'])->name('production-categories.store');
            Route::put('/production-categories/{id}', [MasterDataManagementController::class, 'updateProductionCategory'])->name('production-categories.update');
            Route::delete('/production-categories/{id}', [MasterDataManagementController::class, 'destroyProductionCategory'])->name('production-categories.destroy');

            // Maintenance & Operasional Server
            Route::get('/maintenance/status', [MasterDataManagementController::class, 'maintenanceStatus'])->name('maintenance.status');
            Route::post('/maintenance/clear-cache', [MasterDataManagementController::class, 'clearCache'])->name('maintenance.clear-cache');

            // Governance & System Administration (Super Admin Only)
            Route::prefix('governance')->name('governance.')->middleware([EnsureUserRole::class.':superadmin', CheckIpAccessAndMaintenance::class])->group(function () {
                // 1. Business Policy & Thematic Intervals
                Route::get('/thematic-intervals', [SystemGovernanceController::class, 'indexThematicIntervals'])->name('thematic-intervals.index');
                Route::match(['put', 'post'], '/thematic-intervals', [SystemGovernanceController::class, 'updateThematicIntervals'])->name('thematic-intervals.update');
                Route::post('/thematic-intervals/recalculate', [SystemGovernanceController::class, 'recalculateThematicIntervals'])->name('thematic-intervals.recalculate');
                Route::match(['put', 'post'], '/conversion-factors', [SystemGovernanceController::class, 'updateConversionFactors'])->name('conversion-factors.update');

                // 2. System State & Availability
                Route::get('/state', [SystemGovernanceController::class, 'indexState'])->name('state.index');
                Route::match(['put', 'post'], '/maintenance', [SystemGovernanceController::class, 'updateMaintenanceMode'])->name('maintenance.update');
                Route::match(['put', 'post'], '/emergency-banner', [SystemGovernanceController::class, 'updateEmergencyBanner'])->name('emergency-banner.update');
                Route::match(['put', 'post'], '/features', [SystemGovernanceController::class, 'updateFeatureFlags'])->name('features.update');
                Route::post('/cache/clear', [SystemGovernanceController::class, 'clearGranularCache'])->name('cache.clear');

                // 3. Data Resilience & Backup
                Route::get('/backups', [SystemGovernanceController::class, 'indexBackups'])->name('backups.index');
                Route::post('/backups', [SystemGovernanceController::class, 'createBackup'])->name('backups.store');
                Route::get('/backups/{id}/download', [SystemGovernanceController::class, 'downloadBackup'])->name('backups.download');
                Route::post('/backups/{id}/restore', [SystemGovernanceController::class, 'restoreBackup'])->name('backups.restore');
                Route::delete('/backups/{id}', [SystemGovernanceController::class, 'destroyBackup'])->name('backups.destroy');
                Route::get('/queue-status', [SystemGovernanceController::class, 'queueStatus'])->name('queue-status');

                // 4. Security & Access Governance
                Route::get('/sessions', [SystemGovernanceController::class, 'indexSessions'])->name('sessions.index');
                Route::delete('/sessions/{sessionId}', [SystemGovernanceController::class, 'terminateSession'])->name('sessions.destroy');
                Route::post('/sessions/terminate-others', [SystemGovernanceController::class, 'terminateAllOtherSessions'])->name('sessions.terminate-others');
                Route::get('/ip-rules', [SystemGovernanceController::class, 'indexIpAccessRules'])->name('ip-rules.index');
                Route::post('/ip-rules', [SystemGovernanceController::class, 'storeIpAccessRule'])->name('ip-rules.store');
                Route::delete('/ip-rules/{id}', [SystemGovernanceController::class, 'destroyIpAccessRule'])->name('ip-rules.destroy');
                Route::match(['put', 'post'], '/security-policies', [SystemGovernanceController::class, 'updateSecurityPolicies'])->name('security-policies.update');

                // 5. Observability & Auditing
                Route::get('/audit-logs', [SystemGovernanceController::class, 'indexAuditLogs'])->name('audit-logs.index');
                Route::get('/system-logs', [SystemGovernanceController::class, 'indexSystemLogs'])->name('system-logs.index');
                Route::get('/health', [SystemGovernanceController::class, 'systemHealth'])->name('health');

                // 6. Integrations & Gateway Settings
                Route::get('/integrations', [SystemGovernanceController::class, 'indexIntegrations'])->name('integrations.index');
                Route::match(['put', 'post'], '/integrations', [SystemGovernanceController::class, 'updateIntegrations'])->name('integrations.update');
                Route::post('/integrations/{gateway}/test', [SystemGovernanceController::class, 'testIntegrationConnection'])->name('integrations.test');
            });
        });
    });
});

// 5. DISPATCHER DASHBOARD (Redirect cerdas sesuai peran pengguna setelah autentikasi)
Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    if ($user && in_array($user->role, ['superadmin', 'admin', 'admin_dinas'], true)) {
        return redirect()->route('internal.admin');
    }

    return redirect()->route('internal.dinas');
})->middleware([CheckIpAccessAndMaintenance::class])->name('dashboard');

// 7. PENGATURAN PENGGUNA (Profile, Security, Appearance)
require __DIR__.'/settings.php';
