<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penilaian 10 kriteria dari SATU penilai (role: mentor/pembimbing/pimpinan) untuk satu
 * intern. Setiap simpan/hapus memicu Intern::syncAppraisals() agar nilai akumulasi di
 * tabel interns (rata-rata semua penilai) selalu sinkron.
 */
class InternAppraisal extends Model
{
    /** Urutan tampil penilai. */
    public const ROLES = [
        'mentor' => 'Mentor',
        'pembimbing' => 'Pembimbing',
        'pimpinan' => 'Pimpinan',
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        $sync = fn (self $a) => $a->intern?->syncAppraisals();

        static::saved($sync);
        static::deleted($sync);
    }

    protected function casts(): array
    {
        return array_fill_keys(array_keys(Intern::CRITERIA), 'decimal:2');
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rata-rata kriteria yang sudah diisi penilai ini (null kalau belum ada). */
    public function average(): ?float
    {
        $scores = array_filter(
            array_map(fn ($f) => $this->{$f}, array_keys(Intern::CRITERIA)),
            fn ($v) => $v !== null,
        );

        return $scores === [] ? null : round(array_sum($scores) / count($scores), 2);
    }
}
