<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkron presensi dari DB HRIS (no-op selama koneksi 'hris' belum dikonfigurasi).
Schedule::command('attendance:sync')->everyThirtySeconds()->withoutOverlapping();
