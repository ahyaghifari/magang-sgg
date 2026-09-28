<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Teks sertifikat PKL yang diedit lewat editor sertifikat (satu baris per intern).
 * `data` hanya menyimpan field yang BERBEDA dari data asli — field yang tidak diedit tetap
 * mengikuti database (mis. nilai penilaian yang diperbarui belakangan tetap ikut tampil).
 * Digabung dengan data asli oleh App\Support\CertificateContent::for().
 */
class SertifikatOverride extends Model
{
    protected $fillable = [
        'intern_id',
        'data',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    /** User yang terakhir menyimpan editan sertifikat ini. */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
