<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'approved_at',
        'approved_by',
        'unit_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Satu User (peserta) terhubung ke satu data Intern.
     */
    public function intern(): HasOne
    {
        return $this->hasOne(Intern::class);
    }

    /**
     * Unit (IT, Humas, dst) tempat user ini bekerja. Kosong untuk admin.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Admin yang menyetujui pendaftaran user ini.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User yang mendaftar tapi belum disetujui admin.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('approved_at');
    }

    /**
     * Apakah pendaftaran user ini sudah disetujui admin.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Super admin ditentukan lewat daftar email di config('access.super_admin_emails').
     * Merekalah satu-satunya yang punya akses penuh ke panel Filament (/admin).
     */
    public function isSuperAdmin(): bool
    {
        $email = mb_strtolower(trim((string) $this->email));

        return $email !== '' && in_array($email, config('access.super_admin_emails', []), true);
    }

    /**
     * Apakah user ini admin — dari enum `role` maupun super admin (email).
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->isSuperAdmin();
    }

    /**
     * Apakah user ini peserta magang.
     */
    public function isIntern(): bool
    {
        return $this->role === UserRole::Intern;
    }

    /**
     * Apakah user ini pembimbing (mentor peserta magang).
     */
    public function isPembimbing(): bool
    {
        return $this->role === UserRole::Pembimbing;
    }

    /**
     * Hanya super admin (email terdaftar di config) yang boleh masuk panel Filament
     * (/admin). Peserta/pembimbing/admin biasa diarahkan ke portal oleh
     * App\Http\Middleware\Authenticate, bukan dilempar 403.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin();
    }
}
