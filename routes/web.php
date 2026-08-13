<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.attempt');
});

Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/notulen/create', [AdminController::class, 'createNotulen'])->name('notulen.create');
    Route::get('/notulen/editor', [AdminController::class, 'editNotulen'])->name('notulen.editor');
    Route::post('/notulen', [AdminController::class, 'storeNotulen'])->name('notulen.store');
    Route::get('/notulen/{index?}', [AdminController::class, 'notulen'])->name('notulen');
    Route::get('/meetings', [AdminController::class, 'meetings'])->name('meetings');
});