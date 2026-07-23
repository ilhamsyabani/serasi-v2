<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pemohon\AuthController;
use App\Http\Controllers\Pemohon\DashboardController;
use App\Http\Controllers\Pemohon\PermohonanController;
use App\Http\Controllers\Pemohon\RevisiController;

Route::prefix('pemohon')->middleware(['web', 'force.https', 'security.headers'])->name('pemohon.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.login:5,1')->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('pbf.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/permohonan', [PermohonanController::class, 'index'])->name('permohonan.index');
        Route::get('/permohonan/{permohonan}', [PermohonanController::class, 'show'])->name('permohonan.show');
        Route::get('/permohonan/{permohonan}/revisi', [RevisiController::class, 'show'])->name('revisi.show');
        Route::post('/permohonan/{permohonan}/revisi', [RevisiController::class, 'store'])->name('revisi.store');
        Route::get('/permohonan/baru', [PermohonanController::class, 'create'])->name('permohonan.create');
        Route::post('/permohonan/baru', [PermohonanController::class, 'store'])->name('permohonan.store');
    });
});
