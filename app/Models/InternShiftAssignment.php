<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu entri jadwal shift intern pada satu tanggal: shift tertentu ATAU Libur.
 * Tanggal tanpa entri = belum diisi. Diubah lewat App\Services\Shift\ShiftAssignmentService
 * (bukan langsung) supaya aturan kunci edit & hak akses selalu ditegakkan.
 */
class InternShiftAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'intern_id', 'date', 'shift_id', 'off_day', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'off_day' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Libur selalu tanpa shift; entri bershift selalu bukan libur.
        static::saving(function (self $assignment): void {
            if ($assignment->off_day) {
                $assignment->shift_id = null;
            }
        });
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** User yang terakhir mengubah entri ini. */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Label ringkas: "Libur" atau label shift (mis. "P · Pagi (07:00–15:00)"). */
    public function label(): string
    {
        if ($this->off_day) {
            return 'Libur';
        }

        return $this->shift?->label() ?? '-';
    }
}
