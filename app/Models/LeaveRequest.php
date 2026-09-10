<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan izin/sakit (tidak masuk) milik seorang Intern.
 * Dikonfirmasi (disetujui/ditolak) oleh pembimbing.
 */
class LeaveRequest extends Model
{
    protected $fillable = [
        'intern_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Izin dimiliki oleh satu Intern.
     */
    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    /**
     * Pembimbing yang mengonfirmasi pengajuan ini.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
