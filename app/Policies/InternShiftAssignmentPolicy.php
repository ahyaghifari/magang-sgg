<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Hak akses jadwal shift intern (dipakai di portal, bukan panel Filament).
 *
 * MELIHAT jadwal seorang intern:
 * - intern itu sendiri;
 * - user yang intern-nya kelihatan di portal (User::visibleInterns(): Admin semua,
 *   Pembimbing binaannya, Mentor semua intern);
 * - Pimpinan (semua intern, baca-saja);
 * - super admin.
 *
 * MENGUBAH (buat/ubah/hapus) entri pada suatu tanggal — HANYA:
 * - Pembimbing untuk intern binaannya (pembimbing_id);
 * - Mentor untuk intern dampingannya sendiri (mentor_id);
 * kapan saja, termasuk tanggal lampau. Intern, Admin, Pimpinan, dan super admin hanya melihat.
 *
 * Contoh pakai: Gate::allows('viewSchedule', [InternShiftAssignment::class, $intern])
 *               Gate::allows('manageDate', [InternShiftAssignment::class, $intern, $date])
 */
class InternShiftAssignmentPolicy
{
    public function viewSchedule(User $user, Intern $intern): bool
    {
        return $intern->user_id === $user->id
            || $user->isSuperAdmin()
            || $user->isPimpinan()
            || $user->visibleInterns()->whereKey($intern->id)->exists();
    }

    public function manageDate(User $user, Intern $intern, CarbonInterface|string $date): bool
    {
        return $this->correctAnyDate($user, $intern);
    }

    /** Boleh mengisi/mengoreksi jadwal (tanggal mana saja): Pembimbing binaannya, Mentor dampingannya. */
    public function correctAnyDate(User $user, Intern $intern): bool
    {
        if ($user->isSuperAdmin() || $user->isViewingAsIntern()) {
            return false;
        }

        return ($user->isPembimbing() && (int) $intern->pembimbing_id === (int) $user->id)
            || ($user->isMentor() && (int) $intern->mentor_id === (int) $user->id);
    }

}
