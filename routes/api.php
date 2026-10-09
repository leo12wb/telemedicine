<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorPhotoController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\UserController;
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
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // ─── Rotas protegidas ────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth — requer token
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
        });

        // ─── Usuários ────────────────────────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        // ─── Especialidades ──────────────────────────────────────────────────
        Route::prefix('specialties')->name('specialties.')->group(function () {
            Route::get('/active', [SpecialtyController::class, 'active'])->name('active');
            Route::get('/', [SpecialtyController::class, 'index'])->name('index');
            Route::post('/', [SpecialtyController::class, 'store'])->name('store');
            Route::get('/{specialty}', [SpecialtyController::class, 'show'])->name('show');
            Route::put('/{specialty}', [SpecialtyController::class, 'update'])->name('update');
            Route::patch('/{specialty}/toggle-active', [SpecialtyController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{specialty}', [SpecialtyController::class, 'destroy'])->name('destroy');
        });

        // ─── Médicos ─────────────────────────────────────────────────────────
        Route::prefix('doctors')->name('doctors.')->group(function () {
            Route::get('/', [DoctorController::class, 'index'])->name('index');
            Route::post('/', [DoctorController::class, 'store'])->name('store');
            Route::get('/{doctor}', [DoctorController::class, 'show'])->name('show');
            Route::put('/{doctor}', [DoctorController::class, 'update'])->name('update');
            Route::patch('/{doctor}/toggle-active', [DoctorController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{doctor}', [DoctorController::class, 'destroy'])->name('destroy');
            Route::post('/{doctor}/photo', [DoctorPhotoController::class, 'store'])->name('photo.store');
            Route::delete('/{doctor}/photo', [DoctorPhotoController::class, 'destroy'])->name('photo.destroy');
        });

        // ─── Pacientes ───────────────────────────────────────────────────────
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::get('/profile', [PatientController::class, 'profile'])->name('profile');
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::post('/', [PatientController::class, 'store'])->name('store');
            Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
            Route::put('/{patient}', [PatientController::class, 'update'])->name('update');
            Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('destroy');
        });

        // ─── Disponibilidade ─────────────────────────────────────────────────
        Route::prefix('doctors/{doctor}')->name('doctors.')->group(function () {
            // Slots disponíveis
            Route::get('/availability', [AvailabilityController::class, 'availability'])->name('availability');

            // Horários recorrentes
            Route::prefix('schedules')->name('schedules.')->group(function () {
                Route::get('/', [AvailabilityController::class, 'indexSchedules'])->name('index');
                Route::post('/', [AvailabilityController::class, 'storeSchedule'])->name('store');
                Route::put('/{schedule}', [AvailabilityController::class, 'updateSchedule'])->name('update');
                Route::delete('/{schedule}', [AvailabilityController::class, 'destroySchedule'])->name('destroy');
            });

            // Bloqueios de horário
            Route::prefix('blocks')->name('blocks.')->group(function () {
                Route::get('/', [AvailabilityController::class, 'indexBlocks'])->name('index');
                Route::post('/', [AvailabilityController::class, 'storeBlock'])->name('store');
                Route::delete('/{block}', [AvailabilityController::class, 'destroyBlock'])->name('destroy');
            });
        });

        // ─── Consultas ───────────────────────────────────────────────────────
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])->name('index');
            Route::post('/', [AppointmentController::class, 'store'])->name('store');
            Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
            Route::patch('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
            Route::patch('/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('reschedule');
            Route::patch('/{appointment}/start', [AppointmentController::class, 'start'])->name('start');
            Route::patch('/{appointment}/finish', [AppointmentController::class, 'finish'])->name('finish');
            Route::patch('/{appointment}/notes', [AppointmentController::class, 'updateNotes'])->name('notes');
            Route::get('/{appointment}/meeting', [MeetingController::class, 'show'])->name('meeting');
        });

        // ─── Configurações do sistema ────────────────────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/', [SettingsController::class, 'update'])->name('update');
        });

        // ─── Dashboard ───────────────────────────────────────────────────────
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
});
