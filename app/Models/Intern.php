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
        'pembimbing_id',
        'mentor_id',
        'nama',
        'nama_panggilan',
        'nip',
        'jenis_kelamin',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
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
     * Pembimbing yang ditugaskan mendampingi intern ini secara khusus.
     */
    public function pembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_id');
    }

    /**
     * Mentor yang ditugaskan mendampingi intern ini secara khusus.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
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

    /**
     * Satu Intern punya banyak Task (tugas dari pembimbing / dicatat sendiri).
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Satu Intern punya banyak pengajuan izin/sakit.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
