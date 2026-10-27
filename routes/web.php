<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Portal Publik Murni (Sesuai Bab 11.1 Design Rule: Zero Data Leakage & Bebas Tombol Peran)
Route::get('/', function () {
    return Inertia::render('Public/Portal');
})->name('public.portal');

// 2. Portal Internal Petugas (Pintu Masuk Login)
Route::get('/internal/login', function () {
    return Inertia::render('Internal/Login');
})->name('internal.login');

Route::get('/internal', function () {
    return redirect()->route('internal.login');
});

// 3. Portal Dinas (Stakeholder Pimpinan - Kuantitatif & Kewaspadaan Wabah)
Route::get('/internal/dinas', function () {
    return Inertia::render('Internal/DinasPortal');
})->name('internal.dinas');

// 4. Panel Admin (Staf Teknis - Tata Letak Satu Kolom 1160px & CRUD Data)
Route::get('/internal/admin', function () {
    return Inertia::render('Internal/AdminPortal');
})->name('internal.admin');

// 5. Living Design System & Token Inspector
Route::get('/design-rule', function () {
    return Inertia::render('DesignRule');
})->name('design.rule');

// Route Dashboard Standar
Route::get('dashboard', function () {
    return redirect()->route('internal.admin');
})->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
