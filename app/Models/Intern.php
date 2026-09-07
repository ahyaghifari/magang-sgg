<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Intern extends Model
{
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'institusi_id',
        'nama',
        'jenis_kelamin',
    ];

    /**
     * Intern dimiliki oleh satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Intern terhubung ke satu Institusi (tabel: institutions).
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institusi_id');
    }

    /**
     * Satu Intern punya banyak Journal.
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}
