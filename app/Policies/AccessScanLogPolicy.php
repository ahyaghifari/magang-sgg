<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AccessScanLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessScanLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccessScanLog');
    }

    public function view(AuthUser $authUser, AccessScanLog $accessScanLog): bool
    {
        return $authUser->can('View:AccessScanLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccessScanLog');
    }

    public function update(AuthUser $authUser, AccessScanLog $accessScanLog): bool
    {
        return $authUser->can('Update:AccessScanLog');
    }

    public function delete(AuthUser $authUser, AccessScanLog $accessScanLog): bool
    {
        return $authUser->can('Delete:AccessScanLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccessScanLog');
    }

    public function restore(AuthUser $authUser, AccessScanLog $accessScanLog): bool
    {
        return $authUser->can('Restore:AccessScanLog');
    }

    public function forceDelete(AuthUser $authUser, AccessScanLog $accessScanLog): bool
    {
        return $authUser->can('ForceDelete:AccessScanLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccessScanLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccessScanLog');
    }

    public function replicate(AuthUser $authUser, AccessScanLog $accessScanLog): bool
    {
        return $authUser->can('Replicate:AccessScanLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccessScanLog');
    }

}