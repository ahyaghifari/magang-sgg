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
        'nilai_akhir',
        'nilai_performance',
        'nilai_motivation',
        'nilai_responsibility',
        'nilai_cooperativeness',
        'nilai_attendance',
        'nilai_job_knowledge',
        'nilai_quality_of_work',
        'nilai_job_speed',
        'nilai_initiative',
        'nilai_improvement',
        'catatan_penilaian',
        'dinilai_oleh',
        'dinilai_pada',
    ];

    /**
     * 10 kriteria form "Appraisal on the Job Training Result" resmi perusahaan —
     * skala 1.00-4.00 per kriteria, terbagi 2 kategori. Dipakai bareng oleh form
     * Filament, halaman edit portal pembimbing/mentor, dan PDF form penilaian,
     * supaya daftarnya cuma didefinisikan sekali di sini.
     *
     * @var array<string, array{category: string, title: string, description: string}>
     */
    public const CRITERIA = [
        'nilai_performance' => [
            'category' => 'ATTITUDE',
            'title' => 'Performance, Provesional Value',
            'description' => 'Ketaatan pada standar grooming, perilaku, dan sikap serta nilai-nilai Syifa.',
        ],
        'nilai_motivation' => [
            'category' => 'ATTITUDE',
            'title' => 'Motivation',
            'description' => 'Menunjukan semangat dan kemauan untuk belajar.',
        ],
        'nilai_responsibility' => [
            'category' => 'ATTITUDE',
            'title' => 'Responsibility',
            'description' => 'Bekerja secara tuntas sesuai dengan ketentuan yang berlaku.',
        ],
        'nilai_cooperativeness' => [
            'category' => 'ATTITUDE',
            'title' => 'Cooperativeness',
            'description' => 'Menunjukan perilaku kerjasama dalam tim.',
        ],
        'nilai_attendance' => [
            'category' => 'ATTITUDE',
            'title' => 'Attendance',
            'description' => 'Tingkat kehadiran dan ketepatan waktu.',
        ],
        'nilai_job_knowledge' => [
            'category' => 'KNOWLEDGE & SKILL',
            'title' => 'Job Knowledge',
            'description' => 'Pengetahuan tentang bidang tugas.',
        ],
        'nilai_quality_of_work' => [
            'category' => 'KNOWLEDGE & SKILL',
            'title' => 'Quality of Work',
            'description' => 'Kualitas kerja dengan tepat dan akurat.',
        ],
        'nilai_job_speed' => [
            'category' => 'KNOWLEDGE & SKILL',
            'title' => 'Job Speed',
            'description' => 'Dapat mengikuti ritme kerja dan kerapian.',
        ],
        'nilai_initiative' => [
            'category' => 'KNOWLEDGE & SKILL',
            'title' => 'Initiative',
            'description' => 'Menunjukan inisiatif dalam melaksanakan tugas.',
        ],
        'nilai_improvement' => [
            'category' => 'KNOWLEDGE & SKILL',
            'title' => 'Improvement Achieved',
            'description' => 'Kemajuan yang dicapai selama On The Job Training.',
        ],
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'nilai_akhir' => 'decimal:2',
        'nilai_performance' => 'decimal:2',
        'nilai_motivation' => 'decimal:2',
        'nilai_responsibility' => 'decimal:2',
        'nilai_cooperativeness' => 'decimal:2',
        'nilai_attendance' => 'decimal:2',
        'nilai_job_knowledge' => 'decimal:2',
        'nilai_quality_of_work' => 'decimal:2',
        'nilai_job_speed' => 'decimal:2',
        'nilai_initiative' => 'decimal:2',
        'nilai_improvement' => 'decimal:2',
        'dinilai_pada' => 'datetime',
    ];

    /**
     * nilai_akhir SELALU dihitung ulang otomatis (rata-rata 10 kriteria di CRITERIA)
     * setiap kali salah satu kriteria berubah — supaya tidak pernah tidak-sinkron, di
     * mana pun perubahannya berasal (panel admin atau portal pembimbing/mentor).
     */
    protected static function booted(): void
    {
        static::saving(function (self $intern): void {
            $fields = array_keys(self::CRITERIA);

            if (! $intern->isDirty($fields)) {
                return;
            }

            $skor = array_values(array_filter(
                array_map(fn ($field) => $intern->{$field}, $fields),
                fn ($value) => $value !== null,
            ));

            $intern->nilai_akhir = $skor === [] ? null : round(array_sum($skor) / count($skor), 2);
        });
    }

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

    /**
     * Pembimbing/Mentor (atau admin) yang memberi penilaian akhir ini.
     */
    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }

    /**
     * Rating dari nilai_akhir — sesuai skala di form resmi "Appraisal on the Job
     * Training Result" (skala 1.00-4.00). Dihitung otomatis, tidak disimpan terpisah
     * supaya selalu konsisten dengan nilai_akhir. Null kalau belum dinilai.
     */
    public function predikat(): ?string
    {
        if ($this->nilai_akhir === null) {
            return null;
        }

        $nilai = (float) $this->nilai_akhir;

        return match (true) {
            $nilai >= 3.50 => 'Excellent',
            $nilai >= 3.00 => 'Good',
            $nilai >= 2.50 => 'Fair',
            $nilai >= 1.50 => 'Below Average',
            default => 'Poor',
        };
    }
}
