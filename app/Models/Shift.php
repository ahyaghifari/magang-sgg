<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master shift per perusahaan — jenis shift (Pagi / Siang / Malam) + jamnya, mis. Pagi 08:00–14:00,
 * Siang 14:00–20:00, Malam 20:00–08:00. Dikelola super admin di panel Filament. Intern mana
 * yang memakai jadwal shift dipilih per intern (Intern::uses_shift), bukan per master shift.
 *
 * Kolom `code` menyimpan jenis shift (salah satu TYPES, unik per perusahaan); `name` selalu
 * diisi sama dengan jenisnya. Shift LINTAS HARI (jam pulang <= jam masuk, mis. Malam 20:00–08:00)
 * otomatis ditandai is_overnight: jam pulangnya jatuh keesokan hari, dan tap pulang esok pagi
 * dihitung ke tanggal shift itu dimulai (lihat ScheduleResolver::workDate).
 */
class Shift extends Model
{
    use HasFactory;

    /** Pilihan jenis shift (nama lengkap, tidak disingkat). */
    public const TYPES = ['Pagi', 'Siang', 'Malam'];

    /** Jam bawaan per jenis — otomatis terisi di form Master Shift saat jenis dipilih (masih bisa diubah). */
    public const DEFAULT_TIMES = [
        'Pagi' => ['start' => '08:00', 'end' => '14:00'],
        'Siang' => ['start' => '14:00', 'end' => '20:00'],
        'Malam' => ['start' => '20:00', 'end' => '08:00'], // pulang keesokan hari
    ];

    protected $fillable = [
        'company_id', 'code', 'name', 'start_time', 'end_time',
        'break_minutes', 'late_tolerance_minutes', 'early_leave_tolerance_minutes',
        'checkin_buffer_minutes', 'checkout_buffer_minutes',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'break_minutes' => 'integer',
        'late_tolerance_minutes' => 'integer',
        'early_leave_tolerance_minutes' => 'integer',
        'checkin_buffer_minutes' => 'integer',
        'checkout_buffer_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $shift): void {
            $shift->code = trim((string) $shift->code);
            $shift->name = $shift->code;
            // Jam pulang <= jam masuk → pulang keesokan hari.
            $shift->is_overnight = $shift->start_time !== null && $shift->end_time !== null
                && substr((string) $shift->end_time, 0, 5) <= substr((string) $shift->start_time, 0, 5);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Entri jadwal shift intern yang memakai shift ini. */
    public function assignments(): HasMany
    {
        return $this->hasMany(InternShiftAssignment::class);
    }

    /** Label untuk pilihan & tampilan, mis. "Pagi (08:30–16:30)". */
    public function label(): string
    {
        return sprintf(
            '%s (%s–%s)',
            $this->code,
            substr((string) $this->start_time, 0, 5),
            substr((string) $this->end_time, 0, 5),
        );
    }

    /** Durasi kerja bersih dalam menit (sudah dipotong istirahat). */
    public function workingMinutes(): int
    {
        [$sh, $sm] = array_map('intval', explode(':', substr((string) $this->start_time, 0, 5)));
        [$eh, $em] = array_map('intval', explode(':', substr((string) $this->end_time, 0, 5)));

        $minutes = ($eh * 60 + $em) - ($sh * 60 + $sm);
        if ($minutes <= 0) {
            $minutes += 24 * 60; // lintas hari, mis. 20:00–08:00 = 12 jam
        }

        return max(0, $minutes - (int) $this->break_minutes);
    }
}
