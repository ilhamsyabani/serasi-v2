<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Internal\AuthController;
use App\Http\Controllers\Internal\ChangePasswordController;
use App\Http\Controllers\Internal\ForgotPasswordController;
use App\Http\Controllers\Internal\ResetPasswordController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\Kabalai\DashboardController as KabalaiDashboardController;
use App\Http\Controllers\Internal\Kabalai\PermohonanController as KabalaiPermohonanController;
use App\Http\Controllers\Internal\Kabalai\DisposisiController;
use App\Http\Controllers\Internal\Kabalai\PengajuanUlangController;
use App\Http\Controllers\Internal\Kabalai\NotifikasiLogController;
use App\Http\Controllers\Internal\KetuaTim\DashboardController as KetuaTimDashboardController;
use App\Http\Controllers\Internal\KetuaTim\DistribusiController;
use App\Http\Controllers\Internal\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Internal\Staff\EvaluasiController;
use App\Http\Controllers\Internal\Staff\SuratPengesahanController;
use App\Http\Controllers\Internal\Staff\PermohonanController as StaffPermohonanController;
use App\Http\Controllers\Internal\AdminIt\DashboardController as AdminItDashboardController;
use App\Http\Controllers\Internal\AdminIt\ConfigSettingController;
use App\Http\Controllers\Internal\AdminIt\UserController;
use App\Http\Controllers\Internal\AdminIt\HariLiburController;
use App\Http\Controllers\Internal\AdminIt\HariLiburImportController;
use App\Http\Controllers\Internal\AdminIt\SlaConfigController;
use App\Http\Controllers\Internal\AdminIt\AuditTrailController;
use App\Http\Controllers\Internal\AdminIt\KoreksiStatusController;
use App\Http\Controllers\Internal\Permohonan\PermohonanController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Internal\KetuaTim\EskalasiController;
use App\Http\Controllers\Internal\NotifikasiController;
use App\Http\Controllers\Internal\LaporanController;

Route::prefix('admin')->name('internal.')->middleware(['web', 'force.https', 'security.headers'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.login:5,1')->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.reset-store');

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
            Route::delete('/permohonan/{permohonan}', [KabalaiPermohonanController::class, 'destroy'])->name('permohonan.destroy');
            // Pengajuan ulang permohonan yang ditutup (akun PBF sama, cukup unggah dokumen).
            Route::get('/pengajuan-ulang', [PengajuanUlangController::class, 'index'])->name('pengajuan-ulang.index');
            Route::get('/pengajuan-ulang/{permohonan}', [PengajuanUlangController::class, 'create'])->name('pengajuan-ulang.create');
            Route::post('/pengajuan-ulang/{permohonan}', [PengajuanUlangController::class, 'store'])->name('pengajuan-ulang.store');
            Route::get('/disposisi', [DisposisiController::class, 'index'])->name('disposisi.index');
            Route::post('/disposisi/{permohonan}', [DisposisiController::class, 'store'])->name('disposisi.store');
        });

        Route::prefix('ketua-tim')->middleware('role:ketua_tim')->name('ketua_tim.')->group(function () {
            Route::get('/dashboard', [KetuaTimDashboardController::class, 'index'])->name('dashboard');
            Route::get('/distribusi', [DistribusiController::class, 'index'])->name('distribusi.index');
            Route::post('/distribusi/{permohonan}', [DistribusiController::class, 'store'])->name('distribusi.store');
            // M-17 Eskalasi & Reassignment — khusus Ketua Tim.
            Route::post('/eskalasi/{permohonan}/reassign', [EskalasiController::class, 'reassign'])->name('eskalasi.reassign');
            Route::post('/eskalasi/{permohonan}/reminder', [EskalasiController::class, 'reminder'])->middleware('throttle:20,1')->name('eskalasi.reminder');
        });

        Route::prefix('staff')->middleware('role:staff_sertifikasi')->name('staff.')->group(function () {
            Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
        });

        // Tugas evaluator: Staff Sertifikasi, atau Ketua Tim yang menugaskan dirinya sendiri.
        // Controller tetap memastikan hanya evaluator aktif (distribusi aktif) yang boleh beraksi.
        Route::prefix('staff')->middleware('role:staff_sertifikasi,ketua_tim')->name('staff.')->group(function () {
            Route::get('/permohonan', [StaffPermohonanController::class, 'index'])->name('permohonan.index');
            Route::get('/evaluasi/{permohonan}/edit', [EvaluasiController::class, 'edit'])->name('evaluasi.edit');
            Route::put('/evaluasi/{permohonan}', [EvaluasiController::class, 'update'])->name('evaluasi.update');
            Route::get('/surat/{permohonan}/edit', [SuratPengesahanController::class, 'edit'])->name('surat.edit');
            Route::put('/surat/{permohonan}', [SuratPengesahanController::class, 'update'])->name('surat.update');
        });

        Route::prefix('admin-it')->middleware('role:admin_it')->name('adminit.')->group(function () {
            Route::get('/dashboard', [AdminItDashboardController::class, 'index'])->name('dashboard');
            Route::get('/pengaturan', [ConfigSettingController::class, 'index'])->name('config-setting.index');
            Route::post('/pengaturan', [ConfigSettingController::class, 'update'])->name('config-setting.update');
            Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            // Import (modal di halaman Hari Libur). Didaftarkan sebelum resource agar
            // `hari-libur/import/...` tidak tertangkap sebagai {hari_libur}.
            Route::post('/hari-libur/import', [HariLiburImportController::class, 'store'])->name('hari-libur.import.store');
            Route::get('/hari-libur/import/template', [HariLiburImportController::class, 'template'])->name('hari-libur.import.template');
            Route::resource('hari-libur', HariLiburController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::post('/hari-libur/bulk', [HariLiburController::class, 'bulkStore'])->name('hari-libur.bulk-store');
            Route::resource('sla-config', SlaConfigController::class)->only(['index', 'edit', 'update']);
            Route::resource('notifikasi-log', NotifikasiLogController::class)->only(['index']);
            Route::post('/notifikasi-log/{notifikasi}/resend', [NotifikasiLogController::class, 'resend'])->name('notifikasi-log.resend');
            Route::post('/notifikasi-log/resend-all', [NotifikasiLogController::class, 'resendAll'])->name('notifikasi-log.resend-all');
            Route::resource('audit-trail', AuditTrailController::class)->only(['index']);
            Route::get('/koreksi-status', [KoreksiStatusController::class, 'index'])->name('koreksi-status.index');
            Route::post('/koreksi-status/{permohonan}', [KoreksiStatusController::class, 'store'])->name('koreksi-status.store');
        });

        // M-14 Laporan & Ekspor — Ketua Tim (timnya) dan Staff (miliknya).
        Route::prefix('laporan')->middleware('role:ketua_tim,staff_sertifikasi')->name('laporan.')->group(function () {
            Route::get('/', [LaporanController::class, 'index'])->name('index');
            Route::get('/export', [LaporanController::class, 'export'])->middleware('throttle:10,1')->name('export');
            Route::get('/export-pdf', [LaporanController::class, 'exportPdf'])->middleware('throttle:10,1')->name('export-pdf');
        });

        // Detail permohonan lintas-role. Harus di DALAM grup `auth`, agar tamu
        // diarahkan ke halaman login, bukan menerima 403 yang membingungkan.
        Route::get('/permohonan/{permohonan}', [PermohonanController::class, 'show'])->name('permohonan.show');
        Route::get('/download/dokumen/{permohonan}/{jenisDokumen}', [DownloadController::class, 'dokumen'])->name('download.dokumen');
        Route::get('/download/surat/{permohonan}', [DownloadController::class, 'surat'])->name('download.surat');
        Route::get('/download/revisi/{revisi}', [DownloadController::class, 'revisi'])->name('download.revisi');

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
        Route::get('/notifikasi/count', [NotifikasiController::class, 'count'])->name('notifikasi.count');
        Route::get('/notifikasi/dropdown', [NotifikasiController::class, 'dropdown'])->name('notifikasi.dropdown');
        Route::post('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'markAsRead'])->name('notifikasi.mark-as-read');
        Route::post('/notifikasi/read-all', [NotifikasiController::class, 'markAllAsRead'])->name('notifikasi.mark-all-as-read');

        // Ubah password
        Route::get('/ubah-password', [ChangePasswordController::class, 'show'])->name('password.change');
        Route::put('/ubah-password', [ChangePasswordController::class, 'update'])->name('password.update');
    });
});
