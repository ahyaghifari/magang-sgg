<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalAttachment extends Model
{
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'journal_id',
        'type',
        'path',
        'url',
        'label',
    ];

    /**
     * Lampiran dimiliki oleh satu Journal.
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
