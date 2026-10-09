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
    Route::prefix('auth')->name('auth.')->middleware('throttle:5,1')->group(function () {
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

        // ─── Especialidades ──────────────────────────────────────────────────
        Route::prefix('specialties')->name('specialties.')->group(function () {
            Route::get('/active', [\App\Http\Controllers\Api\SpecialtyController::class, 'active'])->name('active');
            Route::get('/', [\App\Http\Controllers\Api\SpecialtyController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\SpecialtyController::class, 'store'])->name('store');
            Route::get('/{specialty}', [\App\Http\Controllers\Api\SpecialtyController::class, 'show'])->name('show');
            Route::put('/{specialty}', [\App\Http\Controllers\Api\SpecialtyController::class, 'update'])->name('update');
            Route::patch('/{specialty}/toggle-active', [\App\Http\Controllers\Api\SpecialtyController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{specialty}', [\App\Http\Controllers\Api\SpecialtyController::class, 'destroy'])->name('destroy');
        });

        // ─── Médicos ─────────────────────────────────────────────────────────
        Route::prefix('doctors')->name('doctors.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\DoctorController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\DoctorController::class, 'store'])->name('store');
            Route::get('/{doctor}', [\App\Http\Controllers\Api\DoctorController::class, 'show'])->name('show');
            Route::put('/{doctor}', [\App\Http\Controllers\Api\DoctorController::class, 'update'])->name('update');
            Route::patch('/{doctor}/toggle-active', [\App\Http\Controllers\Api\DoctorController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{doctor}', [\App\Http\Controllers\Api\DoctorController::class, 'destroy'])->name('destroy');
        });

        // ─── Pacientes ───────────────────────────────────────────────────────
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::get('/profile', [\App\Http\Controllers\Api\PatientController::class, 'profile'])->name('profile');
            Route::get('/', [\App\Http\Controllers\Api\PatientController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\PatientController::class, 'store'])->name('store');
            Route::get('/{patient}', [\App\Http\Controllers\Api\PatientController::class, 'show'])->name('show');
            Route::put('/{patient}', [\App\Http\Controllers\Api\PatientController::class, 'update'])->name('update');
            Route::delete('/{patient}', [\App\Http\Controllers\Api\PatientController::class, 'destroy'])->name('destroy');
        });

        // ─── Disponibilidade ─────────────────────────────────────────────────
        Route::prefix('doctors/{doctor}')->name('doctors.')->group(function () {
            // Slots disponíveis
            Route::get('/availability', [\App\Http\Controllers\Api\AvailabilityController::class, 'availability'])->name('availability');

            // Horários recorrentes
            Route::prefix('schedules')->name('schedules.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\AvailabilityController::class, 'indexSchedules'])->name('index');
                Route::post('/', [\App\Http\Controllers\Api\AvailabilityController::class, 'storeSchedule'])->name('store');
                Route::put('/{schedule}', [\App\Http\Controllers\Api\AvailabilityController::class, 'updateSchedule'])->name('update');
                Route::delete('/{schedule}', [\App\Http\Controllers\Api\AvailabilityController::class, 'destroySchedule'])->name('destroy');
            });

            // Bloqueios de horário
            Route::prefix('blocks')->name('blocks.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\AvailabilityController::class, 'indexBlocks'])->name('index');
                Route::post('/', [\App\Http\Controllers\Api\AvailabilityController::class, 'storeBlock'])->name('store');
                Route::delete('/{block}', [\App\Http\Controllers\Api\AvailabilityController::class, 'destroyBlock'])->name('destroy');
            });
        });

        // ─── Consultas ───────────────────────────────────────────────────────
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AppointmentController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\AppointmentController::class, 'store'])->name('store');
            Route::get('/{appointment}', [\App\Http\Controllers\Api\AppointmentController::class, 'show'])->name('show');
            Route::patch('/{appointment}/cancel', [\App\Http\Controllers\Api\AppointmentController::class, 'cancel'])->name('cancel');
            Route::patch('/{appointment}/reschedule', [\App\Http\Controllers\Api\AppointmentController::class, 'reschedule'])->name('reschedule');
            Route::patch('/{appointment}/start', [\App\Http\Controllers\Api\AppointmentController::class, 'start'])->name('start');
            Route::patch('/{appointment}/finish', [\App\Http\Controllers\Api\AppointmentController::class, 'finish'])->name('finish');
            Route::patch('/{appointment}/notes', [\App\Http\Controllers\Api\AppointmentController::class, 'updateNotes'])->name('notes');
        });

        // ─── Dashboard ───────────────────────────────────────────────────────
        Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index'])->name('dashboard');
    });
});
