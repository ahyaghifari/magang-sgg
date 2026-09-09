<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rekap presensi harian — 1 baris per (nip, tanggal). Diisi oleh pipeline
 * `attendance:sync` (AccessLogReader -> FixedScheduleCalculator -> AttendanceRecordWriter).
 */
class AttendanceRecord extends Model
{
    protected $fillable = [
        'nip', 'company_id', 'date',
        'check_in_time', 'check_out_time',
        'late_minutes', 'early_leave_minutes', 'working_minutes',
        'status', 'out_of_window', 'source',
    ];

    protected $casts = [
        'date' => 'date',
        'out_of_window' => 'boolean',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'working_minutes' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Intern pemilik NIP ini (bila datanya ada di tabel interns). */
    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class, 'nip', 'nip');
    }
}
