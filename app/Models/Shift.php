<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master shift per perusahaan — jenis shift (Pagi / Siang) + jamnya, mis. Pagi 08:30–16:30,
 * Siang 12:00–21:00. Dikelola super admin di panel Filament, beserta daftar intern yang
 * memakai shift itu (interns()). Intern hanya bisa memilih shift tempat dia terdaftar saat
 * mengisi jadwal shift hariannya.
 *
 * Kolom `code` menyimpan jenis shift (salah satu TYPES, unik per perusahaan); `name` selalu
 * diisi sama dengan jenisnya. Shift malam / lintas hari sengaja TIDAK didukung: jam pulang
 * wajib setelah jam masuk di hari yang sama, dan kolom is_overnight selalu false.
 */
class Shift extends Model
{
    use HasFactory;

    /** Pilihan jenis shift (nama lengkap, tidak disingkat). Shift malam sengaja tidak ada. */
    public const TYPES = ['Pagi', 'Siang'];

    /** Jam bawaan per jenis — otomatis terisi di form Master Shift saat jenis dipilih (masih bisa diubah). */
    public const DEFAULT_TIMES = [
        'Pagi' => ['start' => '08:30', 'end' => '16:30'],
        'Siang' => ['start' => '12:00', 'end' => '21:00'],
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
            $shift->is_overnight = false;
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

    /** Intern yang terdaftar di shift ini (boleh memilihnya di Jadwal Shift). */
    public function interns(): BelongsToMany
    {
        return $this->belongsToMany(Intern::class, 'intern_shift')->withTimestamps();
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

        return max(0, ($eh * 60 + $em) - ($sh * 60 + $sm) - (int) $this->break_minutes);
    }
}
