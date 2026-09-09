<?php

namespace App\Services\Attendance;

use App\Models\CompanyFixedSchedule;
use Carbon\Carbon;

/**
 * Hitung telat / pulang cepat / menit kerja / status dari satu entri harian,
 * memakai jadwal fixed perusahaan untuk hari tersebut.
 */
class FixedScheduleCalculator
{
    /** cache jadwal per (company_id, day_of_week) selama 1 proses. */
    private array $cache = [];

    /**
     * @param  array{nip:string, company_id:mixed, date:string, check_in:?string, check_out:?string, single_scan?:bool}  $entry
     * @return array|null  null = hari libur / jadwal tidak ada / scan diabaikan
     */
    public function calculate(array $entry): ?array
    {
        $cfg = $this->schedule($entry['company_id'], $entry['date']);

        if (! $cfg || $cfg->is_off_day || ! $cfg->start_time || ! $cfg->end_time) {
            return null; // tidak ada jadwal kerja -> tidak menghasilkan rekap
        }

        $start = Carbon::createFromFormat('H:i:s', $cfg->start_time);
        $end = Carbon::createFromFormat('H:i:s', $cfg->end_time);

        $checkIn = $entry['check_in'];
        $checkOut = $entry['check_out'];

        // Scan tunggal yang waktunya sudah >= jam pulang: perlakukan sebagai check-out,
        // check_in dikosongkan. Tetap hadir.
        if (! empty($entry['single_scan']) && $checkIn && $checkIn >= $cfg->end_time) {
            $checkOut = $checkIn;
            $checkIn = null;
        }

        if ($checkIn === null && $checkOut === null) {
            return null;
        }

        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;
        $workingMinutes = null;
        $outOfWindow = false;

        if ($checkIn !== null) {
            $ci = Carbon::createFromFormat('H:i:s', $checkIn);
            $lateThreshold = $start->copy()->addMinutes($cfg->late_tolerance_minutes);
            $lateMinutes = $ci->gt($lateThreshold) ? (int) $lateThreshold->diffInMinutes($ci) : 0;

            $earliest = $start->copy()->subMinutes($cfg->checkin_buffer_minutes);
            $outOfWindow = $ci->lt($earliest);
        }

        if ($checkOut !== null) {
            $co = Carbon::createFromFormat('H:i:s', $checkOut);
            $earlyThreshold = $end->copy()->subMinutes($cfg->early_leave_tolerance_minutes);
            $earlyLeaveMinutes = $co->lt($earlyThreshold) ? (int) $co->diffInMinutes($earlyThreshold) : 0;

            $latest = $end->copy()->addMinutes($cfg->checkout_buffer_minutes);
            $outOfWindow = $outOfWindow || $co->gt($latest);

            if ($checkIn !== null) {
                $ci = Carbon::createFromFormat('H:i:s', $checkIn);
                $workingMinutes = max(0, (int) $ci->diffInMinutes($co) - $cfg->break_minutes);
            }
        }

        // Early-leave tidak mengubah status (konsisten dengan HRIS). check_out kosong pun
        // tetap 'present' — karyawan sudah scan masuk, cuma belum/lupa scan pulang.
        $status = $lateMinutes > 0 ? 'late' : 'present';

        return [
            'nip' => $entry['nip'],
            'company_id' => $entry['company_id'],
            'date' => $entry['date'],
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'working_minutes' => $workingMinutes,
            'status' => $status,
            'out_of_window' => $outOfWindow,
        ];
    }

    private function schedule(mixed $companyId, string $date): ?CompanyFixedSchedule
    {
        $dow = Carbon::parse($date)->dayOfWeek; // 0..6
        $key = $companyId.'|'.$dow;

        return $this->cache[$key] ??= CompanyFixedSchedule::query()
            ->where('company_id', $companyId)
            ->where('day_of_week', $dow)
            ->first() ?: null;
    }
}
