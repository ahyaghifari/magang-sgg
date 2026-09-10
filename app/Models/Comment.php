<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Komentar pada sebuah jurnal atau tugas. Utas dua arah: intern pemilik data
 * maupun pembimbing/admin sama-sama bisa menulis.
 */
class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'body',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Penulis komentar (bisa null bila akunnya sudah dihapus).
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
