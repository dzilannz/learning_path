<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use App\Schedulers\NimAutomationScheduler;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduler untuk Generate NIM Baru
Artisan::command('nim:generate', function () {
    $this->comment('Generating new NIM...');
    app(\App\Schedulers\NimAutomationScheduler::class)->generateNewNim();
    $this->comment('New NIM generated successfully.');
})->yearlyOn(9, 1, '00:00');

// Scheduler untuk Hapus NIM Lama
Artisan::command('nim:delete-old', function () {
    $this->comment('Deleting old NIM...');
    app(\App\Schedulers\NimAutomationScheduler::class)->deleteOldNim();
    $this->comment('Old NIM deleted successfully.');
})->yearlyOn(9, 1, '00:05');

// Scheduler untuk Validasi Mahasiswa Aktif
Artisan::command('nim:validate', function () {
    $this->comment('Validating active students...');
    app(\App\Schedulers\NimAutomationScheduler::class)->validateActiveStudents();
    $this->comment('Active students validated successfully.');
})->cron('0 1 1 2,9 *');

// Jalankan job CalculateIbtitahCount
Artisan::command('nim:calculate-ibtitah', function () {
    $this->comment('Running CalculateIbtitahCount job...');
    \App\Jobs\CalculateIbtitahCount::dispatch();
    $this->comment('CalculateIbtitahCount job executed successfully.');
})->cron('0 2 1 3,9 *');

// Jalankan job CalculateSidangCount
Artisan::command('nim:calculate-sidang', function () {
    $this->comment('Running CalculateSidangCount job...');
    \App\Jobs\CalculateSidangCount::dispatch();
    $this->comment('CalculateSidangCount job executed successfully.');
})->cron('0 3 1 3,9 *');

