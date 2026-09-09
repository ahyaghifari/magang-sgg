<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu jadwal kerja fixed per perusahaan per hari (7 baris per Company).
 * Dipakai FixedScheduleCalculator untuk menghitung telat / pulang cepat / menit kerja.
 */
class CompanyFixedSchedule extends Model
{
    protected $fillable = [
        'company_id', 'day_of_week', 'is_off_day', 'start_time', 'end_time',
        'break_minutes', 'late_tolerance_minutes', 'early_leave_tolerance_minutes',
        'checkin_buffer_minutes', 'checkout_buffer_minutes',
    ];

    protected $casts = ['is_off_day' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
