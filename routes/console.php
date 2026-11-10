<?php

use App\Console\Commands\KirimNotifikasiSlaTerlambat;
use App\Console\Commands\KirimSlaReminder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SLA Reminder: jalan setiap jam kerja (09:00–17:00) pada hari kerja
// Admin IT bisa ubah jam via config('services.sla_reminder_jam')
Schedule::command(KirimSlaReminder::class)
    ->weekdays()
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
