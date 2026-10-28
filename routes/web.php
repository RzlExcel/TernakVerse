<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DinasDashboardController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Middleware\EnsureUserRole;

/*
|--------------------------------------------------------------------------
| Web Routes — TERNAKVERSE WebGIS Banyumas (Inertia.js v2 + Vue 3)
|--------------------------------------------------------------------------
| Terintegrasi 3 Ranah / Portal:
| 1. Portal Publik: Halaman landing & eksplorasi WebGIS terbuka (Zero Data Leakage).
| 2. Autentikasi & Pengaturan: Fortify Auth & Profile/Security Settings.
| 3. Portal Dinas: Dashboard DSS Analitik Pimpinan & Pemangku Kebijakan.
| 4. Panel Admin: Konsol Kendali Manajemen & Audit Trail Petugas Teknis.
|--------------------------------------------------------------------------
*/

// 1. PORTAL PUBLIK (Landing Page)
Route::inertia('/', 'Welcome')->name('home');

// 2. DISPATCHER DASHBOARD (Redirect cerdas sesuai peran pengguna setelah autentikasi)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.panel');
        }
        return redirect()->route('dinas.dashboard');
    })->name('dashboard');
});

// 3. PENGATURAN PENGGUNA (Profile, Security, Appearance)
require __DIR__.'/settings.php';

// 4. PORTAL DINAS (Role: stakeholder, admin)
Route::middleware(['auth', EnsureUserRole::class . ':stakeholder,admin'])->group(function () {
    Route::get('/dinas/dashboard', [DinasDashboardController::class, 'index'])->name('dinas.dashboard');
});

// 5. PANEL ADMIN & AUDIT TRAIL (Khusus Role: admin)
Route::middleware(['auth', EnsureUserRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/panel', [AdminPanelController::class, 'index'])->name('panel');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');
});
