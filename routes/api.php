<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\PublisherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequesterController;
use App\Http\Controllers\ApproverController;
use App\Http\Controllers\PrintRequestController;


    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

// Authentication gerektiren route'lar
Route::middleware('auth:sanctum')->group(function () {

    // Auth işlemleri
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);


    // Requester işlemleri
    Route::apiResource('requesters', RequesterController::class);
    Route::get('requesters/search', [RequesterController::class, 'search']);

    // Approver işlemleri
    Route::apiResource('approvers', ApproverController::class);
    Route::get('approvers/search', [ApproverController::class, 'search']);

    // Print request işlemleri
    Route::apiResource('print-requests', PrintRequestController::class);

    Route::prefix('print-requests/export')->group(function () {
        Route::get('/by-requester', [PrintRequestController::class, 'exportByRequester']);
        Route::get('/comparison', [PrintRequestController::class, 'exportComparison']);
    });

    Route::apiResource('publishers', PublisherController::class);

    // Route::apiResource('grades', GradeController::class); // Artık kullanılmıyor - level sistemi kullanılıyor

    Route::apiResource('books', BookController::class);

    Route::apiResource('authors', AuthorController::class);

});