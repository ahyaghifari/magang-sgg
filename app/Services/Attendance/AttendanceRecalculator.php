<?php

namespace App\Services\Attendance;

use App\Models\AccessScanLog;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Services\Shift\ScheduleResolver;
use Illuminate\Support\Carbon;

/**
 * Hitung ulang rekap presensi satu intern untuk tanggal tertentu — dipanggil setelah jadwal
 * shift tanggal lampau/hari ini dikoreksi (ShiftAssignmentService), supaya jam masuk/pulang,
 * telat & pulang cepat konsisten dengan shift yang baru. Idempoten (aman dipanggil berulang).
 *
 * Sumber tap: log tap lokal (access_scan_logs, tanpa menghubungi HRIS). Kalau log tap tanggal
 * itu tidak ada tapi rekapnya ada (data lama), jam masuk/pulang rekap itu yang dipakai ulang.
 * Kalau tanggal itu kini Libur, rekap yang sudah ada TIDAK dihapus (data kehadiran tetap utuh),
 * cukup tidak dihitung ulang.
 */
class AttendanceRecalculator
{
    /** @param  array<int, string>  $dates  tanggal Y-m-d */
    public function recalc(Intern $intern, array $dates): int
    {
        if (! $intern->nip || $dates === []) {
            return 0;
        }

        // Instance baru supaya jadwal yang barusan diubah terbaca (tanpa cache lama).
        $calculator = new FixedScheduleCalculator(new ScheduleResolver);
        $writer = new AttendanceRecordWriter($calculator);
        $companyId = $intern->loadMissing('unit')->unit?->company_id;
        $done = 0;

        foreach (array_unique($dates) as $date) {
            $date = Carbon::parse($date)->toDateString();

            $times = AccessScanLog::query()
                ->where('nip', $intern->nip)
                ->where('matched', true)
                ->whereDate('scan_date', $date)
                ->pluck('scan_time')
                ->map(fn ($t) => substr((string) $t, 0, 8))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $existing = AttendanceRecord::where('nip', $intern->nip)->whereDate('date', $date)->first();

            if ($times->isEmpty() && $existing) {
                $times = collect([$existing->check_in_time, $existing->check_out_time])
                    ->map(fn ($t) => $t ? substr((string) $t, 0, 8) : null)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();
            }

            if ($times->isEmpty()) {
                continue; // tidak ada tap sama sekali → tidak ada yang dihitung (tidak membuat alfa)
            }

            $calc = $calculator->calculate([
                'nip' => $intern->nip,
                'company_id' => $existing?->company_id ?? $companyId,
                'date' => $date,
                'check_in' => $times->first(),
                'check_out' => $times->count() > 1 ? $times->last() : null,
                'single_scan' => $times->count() === 1,
            ]);

            if ($calc === null) {
                continue; // Libur / tidak ada jadwal → rekap lama dibiarkan apa adanya
            }

            // Dihitung dari nol (bukan digabung dengan nilai lama) supaya jam shift baru dipakai.
            $existing?->delete();
            $writer->save($calc);
            $done++;
        }

        return $done;
    }
}
