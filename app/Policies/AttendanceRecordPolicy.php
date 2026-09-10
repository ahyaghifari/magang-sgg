<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AttendanceRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AttendanceRecord');
    }

    public function view(AuthUser $authUser, AttendanceRecord $attendanceRecord): bool
    {
        return $authUser->can('View:AttendanceRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AttendanceRecord');
    }

    public function update(AuthUser $authUser, AttendanceRecord $attendanceRecord): bool
    {
        return $authUser->can('Update:AttendanceRecord');
    }

    public function delete(AuthUser $authUser, AttendanceRecord $attendanceRecord): bool
    {
        return $authUser->can('Delete:AttendanceRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AttendanceRecord');
    }

    public function restore(AuthUser $authUser, AttendanceRecord $attendanceRecord): bool
    {
        return $authUser->can('Restore:AttendanceRecord');
    }

    public function forceDelete(AuthUser $authUser, AttendanceRecord $attendanceRecord): bool
    {
        return $authUser->can('ForceDelete:AttendanceRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AttendanceRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AttendanceRecord');
    }

    public function replicate(AuthUser $authUser, AttendanceRecord $attendanceRecord): bool
    {
        return $authUser->can('Replicate:AttendanceRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AttendanceRecord');
    }

}