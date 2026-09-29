<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkron presensi dari DB HRIS (no-op selama koneksi 'hris' belum dikonfigurasi).
// withoutOverlapping(5): kunci otomatis lepas setelah 5 menit — dulu bawaannya 24 jam, jadi
// kalau satu proses mati di tengah jalan, sinkron bisa berhenti seharian.
Schedule::command('attendance:sync')->everyTenSeconds()->withoutOverlapping(5);

// Jaring pengaman: tiap jam, sinkron ulang KEMARIN s/d HARI INI dari HRIS (mode rentang,
// tidak menyentuh watermark). Menangkap tap yang masuk ke HRIS sangat terlambat (lebih dari
// jendela baca-ulang 2 jam di sync rutin). Aman diulang — tidak membuat data dobel.
Schedule::command('attendance:sync', [
    '--from' => now('Asia/Makassar')->subDay()->toDateString(),
    '--to' => now('Asia/Makassar')->toDateString(),
])->hourly()->withoutOverlapping(30)->name('attendance:sync-safety-net');
