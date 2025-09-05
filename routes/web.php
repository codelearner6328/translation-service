<?php

use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\TranslationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;




Route::get('/', function () {
    return redirect('/api/documentation');
});

Route::prefix('api')->middleware('api')->group(function () {

    //create user and bearer token for APIs
    Route::get('/generate-token', [AuthTokenController::class, 'createBearerToken']);
    // Public export
    Route::middleware('throttle:120,1')->get('/translations/export', [TranslationController::class, 'export']);

    // Authenticated management routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('/translations', [TranslationController::class, 'index']);
        Route::post('/translations', [TranslationController::class, 'store']);
        Route::get('/translations/{translationKey}', [TranslationController::class, 'show']);
        Route::put('/translations/{translationKey}', [TranslationController::class, 'update']);
        Route::delete('/translations/{translationKey}', [TranslationController::class, 'destroy']);

        Route::post('/tokens', [AuthTokenController::class, 'issue']);
    });
});
