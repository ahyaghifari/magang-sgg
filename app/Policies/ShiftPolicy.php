<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Master shift dikelola di panel /admin — sama seperti resource lain, aksesnya hanya untuk
 * super admin (email di config('access.super_admin_emails')). Aturan akses panel tidak diubah.
 */
class ShiftPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Shift yang sudah dipakai jadwal intern tidak bisa dihapus (riwayat jadwal & presensi
     * tetap utuh) — tombol hapusnya otomatis tersembunyi. Ubah saja nama/jamnya bila perlu.
     */
    public function delete(User $user, Shift $shift): bool
    {
        return $user->isSuperAdmin() && ! $shift->assignments()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Shift $shift): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Shift $shift): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function replicate(User $user, Shift $shift): bool
    {
        return $user->isSuperAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
