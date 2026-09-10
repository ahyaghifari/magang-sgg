<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompanyFixedSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyFixedSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompanyFixedSchedule');
    }

    public function view(AuthUser $authUser, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $authUser->can('View:CompanyFixedSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompanyFixedSchedule');
    }

    public function update(AuthUser $authUser, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $authUser->can('Update:CompanyFixedSchedule');
    }

    public function delete(AuthUser $authUser, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $authUser->can('Delete:CompanyFixedSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CompanyFixedSchedule');
    }

    public function restore(AuthUser $authUser, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $authUser->can('Restore:CompanyFixedSchedule');
    }

    public function forceDelete(AuthUser $authUser, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $authUser->can('ForceDelete:CompanyFixedSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompanyFixedSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompanyFixedSchedule');
    }

    public function replicate(AuthUser $authUser, CompanyFixedSchedule $companyFixedSchedule): bool
    {
        return $authUser->can('Replicate:CompanyFixedSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompanyFixedSchedule');
    }

}