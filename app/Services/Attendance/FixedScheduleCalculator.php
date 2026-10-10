<?php

namespace App\Services\Attendance;

use App\Models\Intern;
use App\Services\Shift\ScheduleResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Hitung telat / pulang cepat / menit kerja / status dari satu entri harian, memakai
 * JADWAL EFEKTIF intern pada tanggal itu (ScheduleResolver): jam shift kalau intern mengisi
 * jadwal shift; Libur → tidak dihitung; belum diisi → jadwal kerja tetap perusahaan (perilaku
 * lama, tidak berubah untuk intern tanpa shift).
 *
 * Shift lintas hari (mis. Malam 20:00–08:00): tap pagi esoknya sudah dikelompokkan ke tanggal
 * shift dimulai (ScheduleResolver::workDate), dan di sini jam yang masih dalam jendela pulang
 * (<= jam pulang + jendela tap pulang) dibaca sebagai jam KEESOKAN HARI — jadi pulang 08:05
 * dihitung setelah masuk 19:58, bukan sebelumnya.
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

    public function resolver(): ScheduleResolver
    {
        return $this->resolver;
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
        $start = $this->at($cfg->start_time, $cfg, isEnd: false);
        $end = $this->at($cfg->end_time, $cfg, isEnd: true);

        $checkIn = $entry['check_in'];
        $checkOut = $entry['check_out'];

        // Scan tunggal yang waktunya sudah >= jam pulang: perlakukan sebagai check-out,
        // check_in dikosongkan. Tetap hadir.
        if (! empty($entry['single_scan']) && $checkIn && $this->at($checkIn, $cfg)->gte($end)) {
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
            $ci = $this->at($checkIn, $cfg);
            $lateThreshold = $start->copy()->addMinutes($cfg->late_tolerance_minutes);
            $lateMinutes = $ci->gt($lateThreshold) ? (int) $lateThreshold->diffInMinutes($ci) : 0;

            $earliest = $start->copy()->subMinutes($cfg->checkin_buffer_minutes);
            $outOfWindow = $ci->lt($earliest);
        }

        if ($checkOut !== null) {
            $co = $this->at($checkOut, $cfg);
            $earlyThreshold = $end->copy()->subMinutes($cfg->early_leave_tolerance_minutes);
            $earlyLeaveMinutes = $co->lt($earlyThreshold) ? (int) $co->diffInMinutes($earlyThreshold) : 0;

            $latest = $end->copy()->addMinutes($cfg->checkout_buffer_minutes);
            $outOfWindow = $outOfWindow || $co->gt($latest);

            if ($checkIn !== null) {
                $workingMinutes = max(0, (int) $this->at($checkIn, $cfg)->diffInMinutes($co) - $cfg->break_minutes);
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

    /**
     * Urutkan jam tap satu tanggal kerja secara kronologis — untuk shift lintas hari, jam pagi
     * (keesokan hari) diurutkan SETELAH jam malam. Dipakai AttendanceRecordWriter & recalculator
     * untuk menentukan tap paling awal (masuk) dan paling akhir (pulang).
     *
     * @param  iterable<?string>  $times  jam "HH:MM:SS"
     */
    public function orderTimes(?string $nip, mixed $companyId, string $date, iterable $times): Collection
    {
        $times = collect($times)->filter()->map(fn ($t) => substr((string) $t, 0, 8))->unique();

        $cfg = $this->resolver->resolve($this->intern($nip), $companyId, $date);

        if (! $cfg || empty($cfg['overnight'])) {
            return $times->sort()->values();
        }

        $cfg = (object) $cfg;

        return $times->sortBy(fn ($t) => $this->at($t, $cfg)->timestamp)->values();
    }

    /**
     * Jam → waktu pada "tanggal kerja" (tanggal dasar tetap). Untuk shift lintas hari, jam yang
     * masih dalam jendela pulang (<= jam pulang + jendela tap pulang) dianggap keesokan hari.
     * Jam pulang shift lintas hari selalu keesokan hari.
     */
    private function at(string $time, object $cfg, bool $isEnd = false): Carbon
    {
        $at = Carbon::createFromFormat('Y-m-d H:i:s', '2000-01-01 ' . substr($time, 0, 8));

        if (! empty($cfg->overnight)) {
            $latestCheckout = ScheduleResolver::minutes($cfg->end_time) + (int) $cfg->checkout_buffer_minutes;
            if ($isEnd || ScheduleResolver::minutes($time) <= min(24 * 60 - 1, $latestCheckout)) {
                $at->addDay();
            }
        }

        return $at;
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

    /** Intern pemilik NIP (cache yang sama dengan perhitungan). */
    public function internByNip(?string $nip): ?Intern
    {
        return $this->intern($nip);
    }
}
