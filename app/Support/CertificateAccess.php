<?php

namespace App\Support;

use App\Models\Intern;
use App\Models\User;

/**
 * Aturan akses sertifikat PKL — satu sumber untuk PDF (InternCertificateController::view/
 * download) dan editor sertifikat (InternCertificateController::editor), supaya keduanya
 * tidak bisa berbeda aturan.
 */
class CertificateAccess
{
    /**
     * Boleh MELIHAT sertifikat: super admin, pemilik (intern itu sendiri), atau user yang
     * intern-nya kelihatan di portal (User::visibleInterns() — admin semua, Pembimbing
     * binaannya, Mentor semua intern). Intern pemilik baru boleh setelah tanggal selesai
     * magang tiba; admin & pembimbing/mentor boleh kapan pun untuk pratinjau/cetak.
     * Gagal → abort 403.
     */
    public static function authorizeView(User $user, Intern $intern): void
    {
        $isOwner = $intern->user_id === $user->id;
        $isSupervisor = $user->visibleInterns()->whereKey($intern->id)->exists();

        abort_unless($user->isSuperAdmin() || $isOwner || $isSupervisor, 403);

        if ($isOwner && ! $user->isSuperAdmin() && ! $isSupervisor) {
            abort_unless(
                $intern->tanggal_selesai && $intern->tanggal_selesai->lte(now()),
                403,
                'Sertifikat baru bisa diakses setelah tanggal selesai magang.',
            );
        }
    }

    /**
     * Boleh MENGEDIT teks di editor sertifikat: super admin, atau user yang boleh mengelola
     * intern ini (User::manageableInterns() — admin semua, Pembimbing binaannya, Mentor
     * hanya mentee-nya sendiri). Sama dengan aturan siapa yang boleh mengisi penilaian.
     */
    public static function canEdit(User $user, Intern $intern): bool
    {
        return $user->isSuperAdmin() || $user->manageableInterns()->whereKey($intern->id)->exists();
    }
}
