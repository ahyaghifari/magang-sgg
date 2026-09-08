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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
     * Apakah user ini admin — dari enum `role` maupun peran Shield (super admin/admin).
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->hasAnyRole(['admin', 'super_admin']);
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
     * Hanya pemilik peran Shield admin/super_admin yang boleh masuk panel Filament (/admin) —
     * itulah yang benar-benar punya izin ke resource-nya. Enum `role = admin` saja tidak cukup
     * (nanti bisa masuk panel tapi kena 403 di tiap resource). Peserta/pembimbing diarahkan
     * ke portal oleh RedirectNonAdminFromPanel, bukan dilempar 403.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Apakah user ini peserta magang (alias lama, berbasis peran Shield).
     */
    public function isPeserta(): bool
    {
        return $this->hasRole('peserta');
    }
}
