<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\DinasDashboardController;
use App\Http\Middleware\EnsureUserRole;
use App\Models\User;
use Illuminate\Http\Request;
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

        if ($role === 'admin') {
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

Route::post('/internal/login', function (Request $request) {
    $roleInput = (string) $request->input('role', 'dinas');
    $roleSlug = ($roleInput === 'admin') ? 'admin' : 'stakeholder';
    $email = (string) $request->input('email', $roleSlug === 'admin' ? 'admin.diskanak@banyumaskab.go.id' : 'drh.sulistyo@banyumaskab.go.id');
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

    if ($authenticatedUser->role === 'admin') {
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
Route::middleware([EnsureUserRole::class.':stakeholder,admin'])->group(function () {
    Route::get('/internal/dinas', function () {
        return Inertia::render('Internal/DinasPortal');
    })->name('internal.dinas');

    // Route Dinas Dashboard Handshake Data (DinasAnalyticsService + ZoningEngineService)
    Route::get('/dinas/dashboard', [DinasDashboardController::class, 'index'])->name('dinas.dashboard');
});

// 5. PANEL ADMIN TEKNIS (Staf Teknis - Tata Letak 1160px & CRUD Data)
Route::middleware([EnsureUserRole::class.':admin'])->group(function () {
    Route::get('/internal/admin', function () {
        return Inertia::render('Internal/AdminPortal');
    })->name('internal.admin');

    // Panel Admin Proteksi Penuh & Audit Logs
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/panel', [AdminPanelController::class, 'index'])->name('panel');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');
    });
});

// 6. DISPATCHER DASHBOARD (Redirect cerdas sesuai peran pengguna setelah autentikasi)
Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    if ($user && $user->role === 'admin') {
        return redirect()->route('internal.admin');
    }

    return redirect()->route('internal.dinas');
})->name('dashboard');

// 7. PENGATURAN PENGGUNA (Profile, Security, Appearance)
require __DIR__.'/settings.php';
