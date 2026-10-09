<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistema de Telemedicina
|--------------------------------------------------------------------------
|
| Prefixo: /api/v1
| Autenticação: Laravel Sanctum (Bearer token)
|
| Os endpoints serão implementados módulo a módulo na Fase 5.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Health check da API
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => '1.0.0']));

    // ─── Autenticação (sem middleware de auth) ───────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register'])->name('register');
        Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [\App\Http\Controllers\Api\Auth\AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [\App\Http\Controllers\Api\Auth\AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // ─── Rotas protegidas ────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth — requer token
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me'])->name('me');
        });

        // ─── Usuários ────────────────────────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\UserController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\UserController::class, 'store'])->name('store');
            Route::get('/{user}', [\App\Http\Controllers\Api\UserController::class, 'show'])->name('show');
            Route::put('/{user}', [\App\Http\Controllers\Api\UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-active', [\App\Http\Controllers\Api\UserController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy'])->name('destroy');
        });

        // Os demais módulos serão adicionados na Fase 5:
        // - /doctors
        // - /patients
        // - /specialties
        // - /appointments
        // - /consultations
        // - /dashboard
    });
});
