<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Intern;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Setelah Filament Shield dilepas, akses penuh ke panel /admin hanya untuk super
 * admin (email di config('access.super_admin_emails')). Semua kemampuan resource
 * mengikuti aturan itu.
 */
class InternPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Intern $intern): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Intern $intern): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Intern $intern): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Intern $intern): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Intern $intern): bool
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

    public function replicate(User $user, Intern $intern): bool
    {
        return $user->isSuperAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isSuperAdmin();
    }

}
