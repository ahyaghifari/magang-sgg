<?php

namespace App\Services\Attendance;

use App\Models\Intern;
use App\Services\Shift\ScheduleResolver;
use Carbon\Carbon;

/**
 * Hitung telat / pulang cepat / menit kerja / status dari satu entri harian, memakai
 * JADWAL EFEKTIF intern pada tanggal itu (ScheduleResolver): jam shift kalau intern mengisi
 * jadwal shift; Libur → tidak dihitung; belum diisi → jadwal kerja tetap perusahaan (perilaku
 * lama, tidak berubah untuk intern tanpa shift). Shift malam/lintas hari tidak didukung, jadi
 * pengelompokan tap per tanggal kalender (AccessLogReader) tetap berlaku.
 */
class FixedScheduleCalculator
{
    /** cache intern per NIP selama 1 proses. */
    private array $internCache = [];

    private ScheduleResolver $resolver;

    public function __construct(?ScheduleResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new ScheduleResolver;
    }

    /**
     * @param  array{nip:string, company_id:mixed, date:string, check_in:?string, check_out:?string, single_scan?:bool}  $entry
     * @return array|null  null = hari libur (perusahaan / Libur di jadwal shift) / jadwal tidak ada / scan diabaikan
     */
    public function calculate(array $entry): ?array
    {
        $cfg = $this->resolver->resolve($this->intern($entry['nip']), $entry['company_id'], $entry['date']);

        if (! $cfg || $cfg['source'] === 'off') {
            return null; // tidak ada jadwal kerja / Libur -> tidak menghasilkan rekap
        }

        $cfg = (object) $cfg;
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

    private function intern(?string $nip): ?Intern
    {
        if (! $nip) {
            return null;
        }

        if (! array_key_exists($nip, $this->internCache)) {
            $this->internCache[$nip] = Intern::where('nip', $nip)->first();
        }

        return $this->internCache[$nip];
    }
}
