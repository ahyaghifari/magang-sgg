<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
    ];

    /**
     * Satu Perusahaan punya banyak Unit (IT, Humas, dst).
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Master shift milik perusahaan ini (kamus kode shift untuk jadwal shift intern).
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
