<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequesterController;
use App\Http\Controllers\ApproverController;
use App\Http\Controllers\PrintRequestController;

// Genel erişilebilir route'lar
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authentication gerektiren route'lar
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Resource routes
    Route::apiResource('requesters', RequesterController::class);
    // Route::get('/requesters/search', [RequesterController::class, 'search']);
    Route::apiResource('approvers', ApproverController::class);
    // Route::get('/approvers/search', [ApproverController::class, 'search']);
    Route::apiResource('print-requests', PrintRequestController::class);
    Route::get('/print-requests/export/by-requester', [PrintRequestController::class, 'exportByRequester']);

    Route::get('/print-requests/export/comparison', [PrintRequestController::class, 'exportComparison']);

});


// Test endpoint (development için)
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API çalışıyor.',
        'timestamp' => now()
    ]);
});
