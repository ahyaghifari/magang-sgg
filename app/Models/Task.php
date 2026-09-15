<?php

namespace App\Models;

use App\Models\Concerns\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tugas milik seorang Intern. Bisa dicatat sendiri oleh intern (tugas yang
 * disampaikan pembimbing secara lisan/omongan langsung) atau dibuat langsung
 * oleh pembimbing lewat web (source = 'web').
 */
class Task extends Model
{
    use HasComments;

    protected $fillable = [
        'intern_id',
        'assigned_by',
        'title',
        'description',
        'source',
        'status',
        'due_date',
        'completed_at',
        'completion_photo_path',
        'rejection_reason',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Tugas dimiliki oleh satu Intern.
     */
    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    /**
     * Pembimbing yang memberi/menunjuk tugas ini (bisa kosong).
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
