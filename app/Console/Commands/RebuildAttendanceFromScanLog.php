<?php

namespace App\Console\Commands;

use App\Models\AccessScanLog;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Services\Attendance\AttendanceRecordWriter;
use App\Services\Attendance\FixedScheduleCalculator;
use Illuminate\Console\Command;

/**
 * Hitung ulang `attendance_records` dari `access_scan_logs` (log tap lokal) —
 * TIDAK menyentuh DB HRIS maupun watermark. Berguna untuk memperbaiki rekap lama
 * setelah aturan perhitungan berubah, atau saat koneksi HRIS sedang tidak ada.
 */
class RebuildAttendanceFromScanLog extends Command
{
    protected $signature = 'attendance:rebuild
        {--from= : tanggal mulai (Y-m-d), default: semua}
        {--to=   : tanggal akhir (Y-m-d), default: semua}';

    protected $description = 'Hitung ulang attendance_records dari access_scan_logs (tanpa HRIS)';

    public function handle(FixedScheduleCalculator $calculator, AttendanceRecordWriter $writer): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        $scans = AccessScanLog::query()
            ->where('matched', true)
            ->when($from, fn ($q) => $q->whereDate('scan_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('scan_date', '<=', $to))
            ->orderBy('scanned_at')
            ->get(['nip', 'scan_date', 'scan_time', 'scanned_at']);

        if ($scans->isEmpty()) {
            $this->info('Tidak ada tap yang cocok di rentang itu.');

            return self::SUCCESS;
        }

        $companyByNip = Intern::query()
            ->whereIn('nip', $scans->pluck('nip')->unique())
            ->with('unit:id,company_id')
            ->get(['id', 'nip', 'unit_id'])
            ->keyBy('nip')
            ->map(fn (Intern $i) => $i->unit?->company_id);

        // Bersihkan rekap lama di rentang ini supaya benar-benar dihitung dari nol.
        AttendanceRecord::query()
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->delete();

        $groups = $scans->groupBy(fn ($s) => $s->nip.'|'.$s->scan_date->toDateString());

        $built = 0;
        $skipped = 0;

        foreach ($groups as $key => $dayScans) {
            [$nip, $date] = explode('|', $key, 2);

            $times = $dayScans->map(fn ($s) => substr((string) $s->scan_time, 0, 8))->filter()->unique()->sort()->values();

            $entry = [
                'nip' => $nip,
                'company_id' => $companyByNip->get($nip),
                'date' => $date,
                'check_in' => $times->first(),
                'check_out' => $times->count() > 1 ? $times->last() : null,
                'single_scan' => $times->count() === 1,
            ];

            $calc = $calculator->calculate($entry);

            if ($calc === null) {
                $skipped++; // hari libur / jadwal tidak ada
                continue;
            }

            $writer->save($calc);
            $built++;
        }

        $this->info("Selesai: {$built} rekap dibangun ulang, {$skipped} dilewati (libur/tanpa jadwal).");

        return self::SUCCESS;
    }
}
