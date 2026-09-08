<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
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
}
