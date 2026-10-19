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
*/

// Redirect root ke dashboard dinas
Route::get('/', function () {
    return redirect()->route('dinas.dashboard');
});

// 1. PORTAL DINAS (Role: stakeholder, admin)
Route::middleware([EnsureUserRole::class . ':stakeholder,admin'])->group(function () {
    Route::get('/dinas/dashboard', [DinasDashboardController::class, 'index'])->name('dinas.dashboard');
});

// 2. PANEL ADMIN & AUDIT TRAIL (Khusus Role: admin)
Route::middleware([EnsureUserRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/panel', [AdminPanelController::class, 'index'])->name('panel');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');
});
