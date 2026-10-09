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
        $resolver = $calculator->resolver();
        $today = Carbon::today()->toDateString();
        $done = 0;

        // Tanggal yang diubah + keesokan harinya: bila tanggal itu jadi/berhenti jadi shift Malam,
        // tap pagi esoknya pindah kepemilikan, jadi rekap esok hari juga perlu dihitung ulang.
        $dates = collect($dates)
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flatMap(fn ($d) => [$d, Carbon::parse($d)->addDay()->toDateString()])
            ->filter(fn ($d) => $d <= $today)
            ->unique()
            ->sort()
            ->values();

        foreach ($dates as $date) {
            $next = Carbon::parse($date)->addDay()->toDateString();

            $scans = AccessScanLog::query()
                ->where('nip', $intern->nip)
                ->where('matched', true)
                ->whereBetween('scan_date', [$date, $next])
                ->get(['scan_date', 'scan_time']);

            // Hanya tap yang tanggal KERJA-nya tanggal ini (tap pagi setelah Malam milik hari sebelumnya).
            $mine = $scans->filter(fn ($s) => $resolver->workDate($intern, $s->scan_date, (string) $s->scan_time) === $date);
            $times = $calculator->orderTimes($intern->nip, $companyId, $date, $mine->pluck('scan_time'));
            $hasRawTapsOnDate = $scans->contains(fn ($s) => $s->scan_date->toDateString() === $date);

            $existing = AttendanceRecord::where('nip', $intern->nip)->whereDate('date', $date)->first();

            if ($times->isEmpty() && $existing && $hasRawTapsOnDate) {
                // Semua tap di tanggal ini ternyata tap pulang shift Malam hari sebelumnya → rekap ini usang.
                $existing->delete();
                $done++;
                continue;
            }

            if ($times->isEmpty() && $existing) {
                $times = $calculator->orderTimes($intern->nip, $existing->company_id ?? $companyId, $date,
                    [$existing->check_in_time, $existing->check_out_time]);
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
