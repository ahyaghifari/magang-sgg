<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyFixedSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Setelah Filament Shield dilepas, akses penuh ke panel /admin hanya untuk super
 * admin (email di config('access.super_admin_emails')). Semua kemampuan resource
 * mengikuti aturan itu.
 */
class CompanyFixedSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, CompanyFixedSchedule $companyFixedSchedule): bool
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

    public function replicate(User $user, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $user->isSuperAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isSuperAdmin();
    }

}
