<?php

use App\Console\Commands\KirimNotifikasiSlaTerlambat;
use App\Console\Commands\KirimSlaReminder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SLA Reminder: jalan setiap jam kerja (09:00–17:00) pada hari kerja.
// Hari kerja mengikuti tabel `hari_libur` (bukan Senin–Jumat polos), sehingga
// reminder tidak terkirim saat libur nasional.
Schedule::command(KirimSlaReminder::class)
    ->weekdays()
    ->when(fn () => app(\App\Services\SlaCalculator::class)->isHariKerja(now()))
    ->between('09:00', '17:00')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// SLA terlambat: sekali per hari kerja; command sendiri mencegah kirim ganda di hari yang sama.
Schedule::command(KirimNotifikasiSlaTerlambat::class)
    ->weekdays()
    ->at('08:00')
    ->withoutOverlapping()
    ->runInBackground();
