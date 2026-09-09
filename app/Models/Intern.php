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
        'unit_id',
        'nama',
        'nip',
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
     * Intern terhubung ke satu Institusi (tabel: institutions) — asal sekolah/kampus.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institusi_id');
    }

    /**
     * Unit (IT, Humas, dst) tempat intern ini ditempatkan magang.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Satu Intern punya banyak Journal.
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Rekap presensi harian (tabel attendance_records), dicocokkan lewat NIP.
     * Diisi oleh pipeline `attendance:sync` dari dump mesin absensi.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'nip', 'nip');
    }
}
