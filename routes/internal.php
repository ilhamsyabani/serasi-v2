<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Internal\AuthController;
use App\Http\Controllers\Internal\ForgotPasswordController;
use App\Http\Controllers\Internal\ResetPasswordController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\Kabalai\DashboardController as KabalaiDashboardController;
use App\Http\Controllers\Internal\Kabalai\PermohonanController as KabalaiPermohonanController;
use App\Http\Controllers\Internal\Kabalai\DisposisiController;
use App\Http\Controllers\Internal\KetuaTim\DashboardController as KetuaTimDashboardController;
use App\Http\Controllers\Internal\KetuaTim\DistribusiController;
use App\Http\Controllers\Internal\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Internal\Staff\EvaluasiController;
use App\Http\Controllers\Internal\Staff\SuratPengesahanController;
use App\Http\Controllers\Internal\AdminIt\DashboardController as AdminItDashboardController;
use App\Http\Controllers\Internal\AdminIt\UserController;
use App\Http\Controllers\Internal\AdminIt\HariLiburController;
use App\Http\Controllers\Internal\Permohonan\PermohonanController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Internal\NotifikasiController;

Route::prefix('admin')->name('internal.')->middleware(['web', 'force.https', 'security.headers'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.login:5,1')->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::middleware(['auth', 'role:kepala_balai,ketua_tim,staff_sertifikasi,admin_it'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('kabalai')->middleware('role:kepala_balai')->name('kabalai.')->group(function () {
            Route::get('/dashboard', [KabalaiDashboardController::class, 'index'])->name('dashboard');
            Route::get('/permohonan', [KabalaiPermohonanController::class, 'index'])->name('permohonan.index');
            Route::get('/permohonan/baru', [KabalaiPermohonanController::class, 'create'])->name('permohonan.create');
            Route::post('/permohonan/baru', [KabalaiPermohonanController::class, 'store'])->name('permohonan.store');
            Route::get('/permohonan/{permohonan}', [KabalaiPermohonanController::class, 'show'])->name('permohonan.show');
            Route::get('/permohonan/{permohonan}/edit', [KabalaiPermohonanController::class, 'edit'])->name('permohonan.edit');
            Route::put('/permohonan/{permohonan}', [KabalaiPermohonanController::class, 'update'])->name('permohonan.update');
            Route::get('/disposisi', [DisposisiController::class, 'index'])->name('disposisi.index');
            Route::post('/disposisi/{permohonan}', [DisposisiController::class, 'store'])->name('disposisi.store');
        });

        Route::prefix('ketua-tim')->middleware('role:ketua_tim')->name('ketua_tim.')->group(function () {
            Route::get('/dashboard', [KetuaTimDashboardController::class, 'index'])->name('dashboard');
            Route::get('/distribusi', [DistribusiController::class, 'index'])->name('distribusi.index');
            Route::post('/distribusi/{permohonan}', [DistribusiController::class, 'store'])->name('distribusi.store');
        });

        Route::prefix('staff')->middleware('role:staff_sertifikasi')->name('staff.')->group(function () {
            Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
            Route::get('/evaluasi/{permohonan}/edit', [EvaluasiController::class, 'edit'])->name('evaluasi.edit');
            Route::put('/evaluasi/{permohonan}', [EvaluasiController::class, 'update'])->name('evaluasi.update');
            Route::get('/surat/{permohonan}/edit', [SuratPengesahanController::class, 'edit'])->name('surat.edit');
            Route::put('/surat/{permohonan}', [SuratPengesahanController::class, 'update'])->name('surat.update');
        });

        Route::prefix('admin-it')->middleware('role:admin_it')->name('adminit.')->group(function () {
            Route::get('/dashboard', [AdminItDashboardController::class, 'index'])->name('dashboard');
            Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::resource('hari-libur', HariLiburController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::post('/hari-libur/bulk', [HariLiburController::class, 'bulkStore'])->name('hari-libur.bulk-store');
        });

        // Detail permohonan lintas-role. Harus di DALAM grup `auth`, agar tamu
        // diarahkan ke halaman login, bukan menerima 403 yang membingungkan.
        Route::get('/permohonan/{permohonan}', [PermohonanController::class, 'show'])->name('permohonan.show');
        Route::get('/download/dokumen/{permohonan}/{jenisDokumen}', [DownloadController::class, 'dokumen'])->name('download.dokumen');
        Route::get('/download/surat/{permohonan}', [DownloadController::class, 'surat'])->name('download.surat');
        Route::get('/download/revisi/{revisi}', [DownloadController::class, 'revisi'])->name('download.revisi');

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    });
});
