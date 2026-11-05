<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\DinasDashboardController;
use App\Http\Controllers\Internal\MasterDataManagementController;
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

Route::get('/', $publicPortalHandler)->name('home');

Route::get('/portal', $publicPortalHandler)->name('public.portal');

Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

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

// 3. LIVING DESIGN SYSTEM & TOKEN INSPECTOR
Route::get('/design-rule', function () {
    return Inertia::render('DesignRule');
})->name('design.rule');

// 4. PORTAL DINAS (Analisis Kuantitatif & Kewaspadaan Wabah)
Route::middleware([EnsureUserRole::class.':superadmin,admin_dinas,admin,stakeholder'])->group(function () {
    Route::get('/internal/dinas', function () {
        return Inertia::render('Internal/DinasPortal');
    })->name('internal.dinas');

    // Route Dinas Dashboard Handshake Data (DinasAnalyticsService + ZoningEngineService)
    Route::get('/dinas/dashboard', [DinasDashboardController::class, 'index'])->name('dinas.dashboard');

    // Unduh Laporan Resmi Eksekutif (PDF & Excel)
    Route::get('/internal/laporan/ekspor-pdf', [App\Http\Controllers\Internal\DinasDashboardController::class, 'downloadLaporan'])->name('internal.laporan.pdf');
    Route::get('/internal/laporan/ekspor-excel', fn (Request $r) => app(App\Http\Controllers\Internal\DinasDashboardController::class)->downloadLaporan($r->merge(['format' => 'excel'])))->name('internal.laporan.excel');
});

// 5. PANEL ADMIN TEKNIS (Staf Teknis - Tata Letak 1160px & CRUD Data)
Route::middleware([EnsureUserRole::class.':superadmin,admin_dinas,admin'])->group(function () {
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
        });
    });
});

// 6. DISPATCHER DASHBOARD (Redirect cerdas sesuai peran pengguna setelah autentikasi)
Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    if ($user && in_array($user->role, ['superadmin', 'admin', 'admin_dinas'], true)) {
        return redirect()->route('internal.admin');
    }

    return redirect()->route('internal.dinas');
})->name('dashboard');

// 7. PENGATURAN PENGGUNA (Profile, Security, Appearance)
require __DIR__.'/settings.php';
