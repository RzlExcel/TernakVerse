<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DinasDashboardController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Middleware\EnsureUserRole;

/*
|--------------------------------------------------------------------------
| Web Routes — TERNAKVERSE WebGIS Banyumas (Inertia.js v2 + Vue 3)
|--------------------------------------------------------------------------
| Terintegrasi Seluruh Modul (Portal Publik, Portal Dinas, Panel Admin, & FE)
|--------------------------------------------------------------------------
*/

<<<<<<< Updated upstream
// 1. PORTAL PUBLIK WEBGIS (Bab 11.1 Design Rule: Zero Data Leakage)
Route::get('/', function () {
    return Inertia::render('Public/Portal');
})->name('public.portal');
=======
// 1. PORTAL PUBLIK (Landing Page — WebGIS Peta Banyumas)
Route::inertia('/', 'Public/Portal')->name('home');
Route::inertia('/portal', 'Public/Portal')->name('public.portal');

// 1.b PORTAL INTERNAL & LIVING DESIGN SYSTEM (Frontend Reference)
Route::inertia('/internal/login', 'Internal/Login')->name('internal.login');
Route::get('/internal', fn () => redirect()->route('internal.login'));
Route::inertia('/internal/dinas', 'Internal/DinasPortal')->name('internal.dinas');
Route::inertia('/internal/admin', 'Internal/AdminPortal')->name('internal.admin');
Route::inertia('/design-rule', 'DesignRule')->name('design.rule');
>>>>>>> Stashed changes

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

// 3. LIVING DESIGN SYSTEM & TOKEN INSPECTOR
Route::get('/design-rule', function () {
    return Inertia::render('DesignRule');
})->name('design.rule');

// 4. PORTAL DINAS (Analisis Kuantitatif & Kewaspadaan Wabah)
Route::get('/internal/dinas', function () {
    return Inertia::render('Internal/DinasPortal');
})->name('internal.dinas');

// Route Dinas Dashboard Handshake Data (DinasAnalyticsService + ZoningEngineService)
Route::middleware([EnsureUserRole::class . ':stakeholder,admin'])->group(function () {
    Route::get('/dinas/dashboard', [DinasDashboardController::class, 'index'])->name('dinas.dashboard');
});

// 5. PANEL ADMIN TEKNIS (Staf Teknis - Tata Letak 1160px & CRUD Data)
Route::get('/internal/admin', function () {
    return Inertia::render('Internal/AdminPortal');
})->name('internal.admin');

// Panel Admin Proteksi Penuh & Audit Logs
Route::middleware([EnsureUserRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/panel', [AdminPanelController::class, 'index'])->name('panel');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');
});

// 6. DISPATCHER DASHBOARD (Redirect cerdas sesuai peran pengguna setelah autentikasi)
Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    if ($user && $user->role === 'admin') {
        return redirect()->route('internal.admin');
    }
    return redirect()->route('internal.dinas');
})->name('dashboard');

// 7. PENGATURAN PENGGUNA (Profile, Security, Appearance)
require __DIR__.'/settings.php';
