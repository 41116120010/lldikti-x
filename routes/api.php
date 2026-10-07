<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AgendaController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SIPERAPAT LLDIKTI (v1)
|--------------------------------------------------------------------------
|
| Designed for Mobile Client (React Native / Android).
| All responses follow standardized JSON envelope: { success, message, data, meta? }.
| Protected endpoints require `Authorization: Bearer <token>`.
|
*/

Route::prefix('v1')->group(function () {

    // 1. Authentication (Multi-Identifier Login)
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.v1.auth.login');

    // 2. Protected Routes (Bearer Token via Sanctum)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Session & Current User
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Dashboard Summary
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.v1.dashboard');

        // Profile Management & Activity Audit
        Route::get('/profile', [ProfileController::class, 'show'])->name('api.v1.profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('api.v1.profile.update');
        Route::get('/profile/logs', [ProfileController::class, 'logs'])->name('api.v1.profile.logs');

        // Agency-Wide Activity Audit (Administrator Only)
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('api.v1.activity-logs.index');

        // Agenda Rapat Management
        Route::get('/agendas', [AgendaController::class, 'index'])->name('api.v1.agendas.index');
        Route::post('/agendas', [AgendaController::class, 'store'])->name('api.v1.agendas.store');
        Route::get('/agendas/{agenda}', [AgendaController::class, 'show'])->name('api.v1.agendas.show');
        Route::put('/agendas/{agenda}', [AgendaController::class, 'update'])->name('api.v1.agendas.update');
        Route::delete('/agendas/{agenda}', [AgendaController::class, 'destroy'])->name('api.v1.agendas.destroy');
        Route::patch('/agendas/{agenda}/status', [AgendaController::class, 'updateStatus'])->name('api.v1.agendas.status');
        Route::patch('/agendas/{agenda}/roles', [AgendaController::class, 'updateRoles'])->name('api.v1.agendas.roles');
        Route::put('/agendas/{agenda}/notulen', [AgendaController::class, 'updateNotulen'])->name('api.v1.agendas.notulen');
        Route::delete('/agendas/{agenda}/documentations/{documentation}', [AgendaController::class, 'deleteDocumentation'])->name('api.v1.agendas.documentations.destroy');

        // Attendance Portal & Check-in
        Route::get('/attendances/portal', [AttendanceController::class, 'portalFeed'])->name('api.v1.attendances.portal');
        Route::get('/agendas/{agenda}/attendance-portal', [AttendanceController::class, 'portal'])->name('api.v1.agendas.portal');
        Route::post('/agendas/{agenda}/attendances', [AttendanceController::class, 'store'])
            ->middleware('throttle:attendance')
            ->name('api.v1.agendas.attendances.store');
        Route::get('/agendas/{agenda}/attendances', [AttendanceController::class, 'agendaAttendances'])->name('api.v1.agendas.attendances.index');
        Route::get('/attendances/my-history', [AttendanceController::class, 'myHistory'])->name('api.v1.attendances.history');

        // Protected Biometric Media Streaming
        Route::get('/attendances/{attendance}/selfie', [AttendanceController::class, 'selfie'])->name('api.v1.attendances.selfie');
        Route::get('/attendances/{attendance}/signature', [AttendanceController::class, 'signature'])->name('api.v1.attendances.signature');

        // User Management (RBAC & Scoped)
        Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('api.v1.users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('api.v1.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('api.v1.users.destroy');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('api.v1.users.toggle-status');

        // Unit Management
        Route::get('/units', [UnitController::class, 'index'])->name('api.v1.units.index');
        Route::post('/units', [UnitController::class, 'store'])->name('api.v1.units.store');
        Route::get('/units/{unit}', [UnitController::class, 'show'])->name('api.v1.units.show');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('api.v1.units.update');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('api.v1.units.destroy');
        Route::patch('/units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('api.v1.units.toggle-status');

        // Reports & Export Downloads
        Route::get('/reports/summary', [ReportController::class, 'summary'])->name('api.v1.reports.summary');
        Route::get('/reports/summary/csv', [ReportController::class, 'exportSummaryCsv'])
            ->middleware('throttle:export')
            ->name('api.v1.reports.summary.csv');
        Route::get('/reports/agendas/{agenda}', [ReportController::class, 'agendaRecap'])->name('api.v1.reports.agenda');
        Route::post('/reports/agendas/{agenda}/config/reset', [ReportController::class, 'resetReportConfig'])->name('api.v1.reports.agenda.config.reset');
        Route::get('/reports/agendas/{agenda}/export/pdf', [ReportController::class, 'exportPdf'])
            ->middleware('throttle:export')
            ->name('api.v1.reports.export.pdf');
        Route::get('/reports/agendas/{agenda}/export/word', [ReportController::class, 'exportWord'])
            ->middleware('throttle:export')
            ->name('api.v1.reports.export.word');
    });
});
