<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — SIPERAPAT LLDIKTI
|--------------------------------------------------------------------------
*/

// Root redirects to Dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Profile Management
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    // Attendance Portal & Personal History
    Route::get('/presensi', [AttendanceController::class, 'portal'])->name('attendances.portal');
    Route::get('/presensi/riwayat', [AttendanceController::class, 'history'])->name('attendances.history');
    Route::get('/agendas/{agenda}/presensi', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('/agendas/{agenda}/presensi', [AttendanceController::class, 'store'])->middleware('throttle:30,1')->name('attendances.store');
    Route::get('/agendas/{agenda}/presensi/{attendance}/sukses', [AttendanceController::class, 'success'])->name('attendances.success');

    // Agenda Management & Notulensi
    Route::prefix('admin/agendas')->name('admin.agendas.')->group(function () {
        Route::get('/', [AgendaController::class, 'index'])->name('index');
        Route::get('/create', [AgendaController::class, 'create'])->name('create');
        Route::post('/', [AgendaController::class, 'store'])->name('store');
        Route::get('/{agenda}', [AgendaController::class, 'show'])->name('show');
        Route::get('/{agenda}/edit', [AgendaController::class, 'edit'])->name('edit');
        Route::put('/{agenda}', [AgendaController::class, 'update'])->name('update');
        Route::delete('/{agenda}', [AgendaController::class, 'destroy'])->name('destroy');
        Route::patch('/{agenda}/status', [AgendaController::class, 'updateStatus'])->name('update-status');
        Route::get('/{agenda}/notulen', [AgendaController::class, 'notulen'])->name('notulen');
        Route::put('/{agenda}/notulen', [AgendaController::class, 'updateNotulen'])->name('update-notulen');
        Route::delete('/{agenda}/documentations/{documentation}', [AgendaController::class, 'deleteDocumentation'])->name('delete-documentation');
    });

    // Reports, Recap & Official Exports (PDF/Word/CSV)
    Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/summary/csv', [ReportController::class, 'exportSummaryCsv'])->name('summary.csv');
        Route::get('/{agenda}', [ReportController::class, 'show'])->name('show');
        Route::get('/{agenda}/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{agenda}/export/word', [ReportController::class, 'exportWord'])->name('export.word');
    });

    // Unit Management (Superadmin)
    Route::prefix('admin/units')->name('admin.units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::get('/create', [UnitController::class, 'create'])->name('create');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
        Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
        Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
        Route::patch('/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Scoped User Management (Superadmin & Admin Unit)
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });

    // System Activity Logs (Superadmin Only)
    Route::prefix('admin/logs')->name('admin.logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    });
});
