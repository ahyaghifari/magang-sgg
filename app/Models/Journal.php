<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'intern_id',
        'date',
        'activity',
    ];

    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Journal dimiliki oleh satu Intern.
     */
    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    /**
     * Satu Journal punya banyak lampiran (foto / dokumen / link).
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(JournalAttachment::class);
    }

    /**
     * Penilaian bintang (1..5) dari pembimbing. Rata-ratanya dihitung antar-pembimbing.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(JournalReview::class);
    }
}
