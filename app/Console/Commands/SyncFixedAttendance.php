<?php

namespace App\Console\Commands;

use App\Models\AttendanceSyncState;
use App\Services\Attendance\AccessLogReader;
use App\Services\Attendance\AccessScanLogWriter;
use App\Services\Attendance\AttendanceRecordWriter;
use App\Services\Attendance\FixedScheduleCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncFixedAttendance extends Command
{
    protected $signature = 'attendance:sync
        {--from= : tanggal mulai (Y-m-d) untuk mode rentang}
        {--to=   : tanggal akhir (Y-m-d) untuk mode rentang}
        {--nip=  : batasi 1 NIP (mode rentang)}';

    protected $description = 'Sinkron access_logs (DB HRIS) -> attendance_records (jadwal fixed per perusahaan)';

    public function handle(
        AccessLogReader $reader,
        FixedScheduleCalculator $calculator,
        AttendanceRecordWriter $writer,
        AccessScanLogWriter $scanWriter,
    ): int {
        if (blank(config('database.connections.hris.username'))) {
            $this->warn('Koneksi DB HRIS belum dikonfigurasi (HRIS_DB_USERNAME kosong). Lewati.');

            return self::SUCCESS;
        }

        $isRange = $this->option('from') && $this->option('to');

        try {
            if ($isRange) {
                $result = $reader->readRange($this->option('from'), $this->option('to'), $this->option('nip'));
            } else {
                $state = AttendanceSyncState::singleton();
                $result = $reader->readIncremental($state->last_synced_at?->toDateTimeString());
            }
        } catch (Throwable $e) {
            $this->error('Gagal membaca access_logs dari DB HRIS: '.$e->getMessage());
            Log::error('attendance:sync — baca access_logs gagal', ['exception' => $e]);

            return self::FAILURE;
        }

        if ($result['max_synced_at'] === null) {
            $this->info('Tidak ada access_logs baru.');

            return self::SUCCESS;
        }

        // Catat SEMUA tap sidik jari lebih dulu — termasuk yang NIP-nya tak dikenal.
        $loggedScans = $scanWriter->saveMany($result['scans'] ?? []);

        $saved = 0;
        $skipped = 0;

        foreach ($result['entries'] as $entry) {
            $calc = $calculator->calculate($entry);

            if ($calc === null) {
                $skipped++; // hari libur / tidak ada jadwal
                continue;
            }

            $writer->save($calc);
            $saved++;
        }

        foreach ($result['unmatched'] as $nip => $count) {
            Log::warning("attendance:sync — NIP tidak dikenal: {$nip} ({$count} scan)");
        }

        if (! $isRange) {
            AttendanceSyncState::singleton()->update(['last_synced_at' => $result['max_synced_at']]);
        }

        $this->info("Selesai: {$saved} disimpan, {$skipped} dilewati, "
            .count($result['unmatched']).' NIP tidak dikenal, '
            ."{$loggedScans} tap dicatat di log scan. Watermark -> ".$result['max_synced_at']);

        return self::SUCCESS;
    }
}
