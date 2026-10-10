<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan perubahan jadwal shift intern pada satu tanggal, diputuskan Mentor intern tsb.
 * Dibuat/diputuskan/dibatalkan lewat App\Services\Shift\ShiftChangeRequestService (bukan
 * langsung) supaya aturan akses, batas tanggal, dan notifikasi selalu sama.
 */
class ShiftChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'intern_id', 'date',
        'old_shift_id', 'old_off_day',
        'requested_shift_id', 'requested_shift_code', 'requested_off_day',
        'reason', 'status', 'decided_by', 'decision_note', 'decided_at',
    ];

    protected $casts = [
        'date' => 'date',
        'old_off_day' => 'boolean',
        'requested_off_day' => 'boolean',
        'decided_at' => 'datetime',
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function oldShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'old_shift_id');
    }

    public function requestedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    /** Mentor yang menyetujui/menolak. */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** Isian saat diajukan: "Pagi" / "Libur" / "Kosong". */
    public function oldLabel(): string
    {
        if ($this->old_off_day) {
            return 'Libur';
        }

        return $this->old_shift_id ? ($this->oldShift?->code ?? 'Shift') : 'Kosong';
    }

    /** Isian yang diajukan: "Siang" / "Libur". */
    public function requestedLabel(): string
    {
        return $this->requested_off_day ? 'Libur' : ($this->requestedShift?->code ?? $this->requested_shift_code ?? 'Shift');
    }

    /** Ringkas "Pagi → Siang". */
    public function changeLabel(): string
    {
        return $this->oldLabel() . ' → ' . $this->requestedLabel();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Menunggu',
        };
    }
}
