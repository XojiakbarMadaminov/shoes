<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SupplierDebt;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierDebtPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierDebt');
    }

    public function view(AuthUser $authUser, SupplierDebt $supplierDebt): bool
    {
        return $authUser->can('View:SupplierDebt');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierDebt');
    }

    public function update(AuthUser $authUser, SupplierDebt $supplierDebt): bool
    {
        return $authUser->can('Update:SupplierDebt');
    }

    public function delete(AuthUser $authUser, SupplierDebt $supplierDebt): bool
    {
        return $authUser->can('Delete:SupplierDebt');
    }

    public function restore(AuthUser $authUser, SupplierDebt $supplierDebt): bool
    {
        return $authUser->can('Restore:SupplierDebt');
    }

    public function forceDelete(AuthUser $authUser, SupplierDebt $supplierDebt): bool
    {
        return $authUser->can('ForceDelete:SupplierDebt');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierDebt');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierDebt');
    }

    public function replicate(AuthUser $authUser, SupplierDebt $supplierDebt): bool
    {
        return $authUser->can('Replicate:SupplierDebt');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierDebt');
    }

}