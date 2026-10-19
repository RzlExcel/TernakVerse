<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — TERNAKVERSE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'system' => 'TERNAKVERSE API (Kabupaten Banyumas)',
        'version' => '1.0.0',
        'status' => 'operational',
        'srid' => 'EPSG:4326',
        'timestamp' => now()->toIso8601String(),
    ]);
});
