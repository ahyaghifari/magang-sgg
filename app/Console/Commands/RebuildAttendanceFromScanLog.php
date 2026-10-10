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
        {--to=   : tanggal akhir (Y-m-d), default: semua}
        {--nip=  : batasi 1 NIP (mis. setelah jadwal shift intern dikoreksi)}';

    protected $description = 'Hitung ulang attendance_records dari access_scan_logs (tanpa HRIS)';

    public function handle(FixedScheduleCalculator $calculator, AttendanceRecordWriter $writer): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $nip = $this->option('nip');

        $scans = AccessScanLog::query()
            ->where('matched', true)
            ->when($nip, fn ($q) => $q->where('nip', $nip))
            ->when($from, fn ($q) => $q->whereDate('scan_date', '>=', $from))
            // +1 hari: tap pulang shift Malam di tanggal terakhir jatuh keesokan paginya.
            ->when($to, fn ($q) => $q->whereDate('scan_date', '<=', \Illuminate\Support\Carbon::parse($to)->addDay()->toDateString()))
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
            ->when($nip, fn ($q) => $q->where('nip', $nip))
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->delete();

        // Per (nip, tanggal KERJA): tap pagi setelah shift lintas hari (Malam) ikut tanggal shift dimulai.
        $resolver = $calculator->resolver();
        $groups = $scans->groupBy(fn ($s) => $s->nip.'|'.$resolver->workDate(
            $calculator->internByNip($s->nip), $s->scan_date, (string) $s->scan_time,
        ));

        $built = 0;
        $skipped = 0;

        foreach ($groups as $key => $dayScans) {
            [$nip, $date] = explode('|', $key, 2);

            if (($to && $date > $to) || ($from && $date < $from)) {
                continue; // tanggal kerja di luar rentang (mis. tap pulang Malam milik hari sebelum rentang)
            }

            $times = $calculator->orderTimes($nip, $companyByNip->get($nip), $date, $dayScans->pluck('scan_time'));

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
