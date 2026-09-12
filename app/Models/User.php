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
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

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
     * Tidak berlaku selama user sedang "melihat sebagai intern" (lihat isViewingAsIntern()).
     */
    public function isAdmin(): bool
    {
        if ($this->isViewingAsIntern()) {
            return false;
        }

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
     * Apakah `role` user ini Admin. Cek MURNI `role` — TIDAK melibatkan super admin
     * (email). Untuk kebutuhan panel /admin pakai isSuperAdmin() / isAdmin().
     */
    public function hasAdminRole(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Apakah user ini memakai sisi "pembimbing" di portal (menu & halaman intern).
     *
     * Super admin TIDAK PERNAH dianggap pembimbing di portal — kuasa super admin
     * hanya berlaku di panel /admin. Di portal, super admin diperlakukan persis
     * seperti intern biasa, apa pun nilai `role`-nya. Selain super admin, sisi
     * pembimbing ditentukan murni dari `role` (pembimbing atau admin).
     */
    public function isPortalMentor(): bool
    {
        if ($this->isSuperAdmin() || $this->isViewingAsIntern()) {
            return false;
        }

        return $this->isPembimbing() || $this->hasAdminRole();
    }

    /**
     * Hanya super admin (email terdaftar di config) yang boleh masuk panel Filament
     * (/admin). Peserta/pembimbing/admin biasa diarahkan ke portal oleh
     * App\Http\Middleware\Authenticate, bukan dilempar 403.
     *
     * Selama "melihat sebagai intern" akses panel ditutup juga untuk super admin —
     * mode intern berarti benar-benar tanpa akses admin, termasuk lewat URL /admin langsung.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() && ! $this->isViewingAsIntern();
    }

    /**
     * Boleh tidaknya user ini pakai toggle "lihat sebagai intern": harus punya sisi
     * admin/pembimbing di portal DAN punya data Intern sendiri (supaya beranda,
     * jurnal, dst tetap terisi wajar saat berperan sebagai intern).
     */
    public function canToggleIntern(): bool
    {
        return ($this->hasAdminRole() || $this->isPembimbing() || $this->isSuperAdmin())
            && $this->intern()->exists();
    }

    /**
     * Mode sementara (per sesi login) di mana user admin/pembimbing memilih tampil
     * sebagai peserta magang biasa — tidak ada menu maupun akses admin selama aktif.
     * Diset lewat toggle di sidebar portal, tersimpan di session, hilang saat logout.
     */
    public function isViewingAsIntern(): bool
    {
        return $this->canToggleIntern() && session('portal_view_as_intern') === true;
    }
}
