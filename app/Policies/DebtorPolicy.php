<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Debtor;
use Illuminate\Auth\Access\HandlesAuthorization;

class DebtorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Debtor');
    }

    public function view(AuthUser $authUser, Debtor $debtor): bool
    {
        return $authUser->can('View:Debtor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Debtor');
    }

    public function update(AuthUser $authUser, Debtor $debtor): bool
    {
        return $authUser->can('Update:Debtor');
    }

    public function delete(AuthUser $authUser, Debtor $debtor): bool
    {
        return $authUser->can('Delete:Debtor');
    }

    public function restore(AuthUser $authUser, Debtor $debtor): bool
    {
        return $authUser->can('Restore:Debtor');
    }

    public function forceDelete(AuthUser $authUser, Debtor $debtor): bool
    {
        return $authUser->can('ForceDelete:Debtor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Debtor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Debtor');
    }

    public function replicate(AuthUser $authUser, Debtor $debtor): bool
    {
        return $authUser->can('Replicate:Debtor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Debtor');
    }

}