<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'company_id',
        'name',
    ];

    /**
     * Unit dimiliki oleh satu Perusahaan.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Pegawai (User) di unit ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Peserta magang (Intern) yang ditempatkan di unit ini.
     */
    public function interns(): HasMany
    {
        return $this->hasMany(Intern::class);
    }
}
