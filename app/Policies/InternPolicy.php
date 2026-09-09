<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Intern;
use Illuminate\Auth\Access\HandlesAuthorization;

class InternPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Intern');
    }

    public function view(AuthUser $authUser, Intern $intern): bool
    {
        return $authUser->can('View:Intern');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Intern');
    }

    public function update(AuthUser $authUser, Intern $intern): bool
    {
        return $authUser->can('Update:Intern');
    }

    public function delete(AuthUser $authUser, Intern $intern): bool
    {
        return $authUser->can('Delete:Intern');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Intern');
    }

    public function restore(AuthUser $authUser, Intern $intern): bool
    {
        return $authUser->can('Restore:Intern');
    }

    public function forceDelete(AuthUser $authUser, Intern $intern): bool
    {
        return $authUser->can('ForceDelete:Intern');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Intern');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Intern');
    }

    public function replicate(AuthUser $authUser, Intern $intern): bool
    {
        return $authUser->can('Replicate:Intern');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Intern');
    }

}