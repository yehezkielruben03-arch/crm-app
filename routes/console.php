<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwalkan pembersihan log dan notifikasi lama (default > 6 bulan) setiap hari
use Illuminate\Support\Facades\Schedule;
Schedule::command('db:prune-logs')->dailyAt('00:00')->withoutOverlapping();

// Gap #10 — Cek dan update customer jadi Inactive jika tidak transaksi > 12 bulan
// Jalan setiap hari jam 01:00 dini hari (low traffic)
Schedule::command('customer:update-status')->dailyAt('01:00')->withoutOverlapping();

// Gap #11 — Kirim reminder follow-up ke Sales setiap hari kerja jam 08:00 pagi
// Hanya Senin-Jumat agar Sales tidak terganggu di akhir pekan
Schedule::command('customer:send-followup-reminders')->weekdays()->dailyAt('08:00')->withoutOverlapping();

