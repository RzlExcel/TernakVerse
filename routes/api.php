<?php

use App\Http\Controllers\Api\ChatbotApiController;
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

Route::prefix('v1/chatbot')->group(function () {
    Route::post('/chat', [ChatbotApiController::class, 'chat'])->middleware('throttle:60,1');
    Route::post('/feedback', [ChatbotApiController::class, 'feedback'])->middleware('throttle:60,1');
    Route::get('/history/{conversationId}', [ChatbotApiController::class, 'history'])->middleware('throttle:60,1');
});
