<?php

namespace App\Services\Shift;

use App\Models\CompanyFixedSchedule;
use App\Models\Intern;
use App\Models\InternShiftAssignment;
use Illuminate\Support\Carbon;

/**
 * Satu-satunya penentu "jadwal efektif" seorang intern pada satu tanggal — dipakai semua
 * jalur presensi (sync HRIS per beberapa detik, sync ulang per jam, rebuild, hitung ulang
 * setelah koreksi jadwal), lewat FixedScheduleCalculator.
 *
 * Urutan:
 *  1. Ada entri jadwal shift intern di tanggal itu:
 *     - Libur → ['source' => 'off'] (tidak dihitung — tidak ada rekap/alfa baru);
 *     - shift → jam & toleransi dari master shift.
 *  2. Belum diisi → jadwal kerja TETAP perusahaan (company_fixed_schedules) untuk hari itu —
 *     persis perilaku lama, jadi intern tanpa shift tidak berubah sama sekali.
 *  3. Tidak ada jadwal sama sekali / hari libur perusahaan → null.
 *
 * Hasil di-cache per instance (satu proses sync); buat instance baru bila jadwal baru diubah.
 */
class ScheduleResolver
{
    private array $assignmentCache = [];

    private array $fixedCache = [];

    /**
     * @param  int|null  $companyId  perusahaan untuk fallback jadwal tetap (null = pakai unit intern)
     * @return array{source: string, label: ?string, shift_id: ?int, start_time: ?string, end_time: ?string,
     *               break_minutes: int, late_tolerance_minutes: int, early_leave_tolerance_minutes: int,
     *               checkin_buffer_minutes: int, checkout_buffer_minutes: int}|null
     *         source: 'shift' | 'off' | 'fixed'. null = tidak ada jadwal kerja.
     */
    public function resolve(?Intern $intern, mixed $companyId, Carbon|string $date): ?array
    {
        $date = Carbon::parse($date)->toDateString();

        if ($intern) {
            $assignment = $this->assignment($intern->id, $date);

            if ($assignment?->off_day) {
                return [
                    'source' => 'off', 'label' => 'Libur', 'shift_id' => null,
                    'start_time' => null, 'end_time' => null, 'break_minutes' => 0,
                    'late_tolerance_minutes' => 0, 'early_leave_tolerance_minutes' => 0,
                    'checkin_buffer_minutes' => 0, 'checkout_buffer_minutes' => 0,
                ];
            }

            if ($assignment?->shift) {
                $s = $assignment->shift;

                return [
                    'source' => 'shift', 'label' => $s->label(), 'shift_id' => $s->id,
                    'start_time' => $this->hms($s->start_time), 'end_time' => $this->hms($s->end_time),
                    'break_minutes' => (int) $s->break_minutes,
                    'late_tolerance_minutes' => (int) $s->late_tolerance_minutes,
                    'early_leave_tolerance_minutes' => (int) $s->early_leave_tolerance_minutes,
                    'checkin_buffer_minutes' => (int) $s->checkin_buffer_minutes,
                    'checkout_buffer_minutes' => (int) $s->checkout_buffer_minutes,
                ];
            }

            $companyId ??= $intern->loadMissing('unit')->unit?->company_id;
        }

        $cfg = $this->fixed($companyId, $date);

        if (! $cfg || $cfg->is_off_day || ! $cfg->start_time || ! $cfg->end_time) {
            return null; // perilaku lama: tidak ada jadwal kerja → tidak menghasilkan rekap
        }

        return [
            'source' => 'fixed', 'label' => null, 'shift_id' => null,
            'start_time' => $this->hms($cfg->start_time), 'end_time' => $this->hms($cfg->end_time),
            'break_minutes' => (int) $cfg->break_minutes,
            'late_tolerance_minutes' => (int) $cfg->late_tolerance_minutes,
            'early_leave_tolerance_minutes' => (int) $cfg->early_leave_tolerance_minutes,
            'checkin_buffer_minutes' => (int) $cfg->checkin_buffer_minutes,
            'checkout_buffer_minutes' => (int) $cfg->checkout_buffer_minutes,
        ];
    }

    /**
     * Label jadwal untuk tampilan rekap: "Pagi (08:30–16:30)", "Libur", atau null (jadwal biasa).
     * Ringan: hanya membaca entri jadwal shift, tidak membaca jadwal tetap.
     */
    public function labelFor(?Intern $intern, Carbon|string $date): ?string
    {
        if (! $intern) {
            return null;
        }

        $assignment = $this->assignment($intern->id, Carbon::parse($date)->toDateString());

        return $assignment ? $assignment->label() : null;
    }

    /**
     * Label shift untuk banyak baris rekap presensi sekaligus (1 query) — dibaca saat tampil,
     * tidak disimpan di attendance_records. Baris tanpa entri jadwal shift tidak ada di hasil
     * (tampilan rekap intern non-shift tetap seperti sebelumnya).
     *
     * @param  iterable<\App\Models\AttendanceRecord>  $records  butuh nip, date, dan relasi intern
     * @return array<string, array{type: string, label: string}>  kunci "nip|Y-m-d"
     */
    public static function labelsForRecords(iterable $records): array
    {
        $nipByIntern = [];
        $dates = [];
        foreach ($records as $row) {
            if ($row->intern) {
                $nipByIntern[$row->intern->id] = $row->nip;
                $dates[] = Carbon::parse($row->date)->toDateString();
            }
        }

        if ($nipByIntern === []) {
            return [];
        }

        $labels = [];
        InternShiftAssignment::with('shift')
            ->whereIn('intern_id', array_keys($nipByIntern))
            ->whereIn('date', array_unique($dates))
            ->get()
            ->each(function (InternShiftAssignment $a) use (&$labels, $nipByIntern) {
                $labels[$nipByIntern[$a->intern_id] . '|' . $a->date->toDateString()] = [
                    'type' => $a->off_day ? 'Libur' : ($a->shift?->code ?? 'Shift'),
                    'label' => $a->label(),
                ];
            });

        return $labels;
    }

    private function assignment(int $internId, string $date): ?InternShiftAssignment
    {
        $key = $internId . '|' . $date;

        if (! array_key_exists($key, $this->assignmentCache)) {
            $this->assignmentCache[$key] = InternShiftAssignment::with('shift')
                ->where('intern_id', $internId)
                ->whereDate('date', $date)
                ->first();
        }

        return $this->assignmentCache[$key];
    }

    private function fixed(mixed $companyId, string $date): ?CompanyFixedSchedule
    {
        if (! $companyId) {
            return null;
        }

        $dow = Carbon::parse($date)->dayOfWeek; // 0..6
        $key = $companyId . '|' . $dow;

        return $this->fixedCache[$key] ??= CompanyFixedSchedule::query()
            ->where('company_id', $companyId)
            ->where('day_of_week', $dow)
            ->first() ?: null;
    }

    private function hms(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) . ':00' : null;
    }
}
