<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Stock;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Stock');
    }

    public function view(AuthUser $authUser, Stock $stock): bool
    {
        return $authUser->can('View:Stock');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Stock');
    }

    public function update(AuthUser $authUser, Stock $stock): bool
    {
        return $authUser->can('Update:Stock');
    }

    public function delete(AuthUser $authUser, Stock $stock): bool
    {
        return $authUser->can('Delete:Stock');
    }

    public function restore(AuthUser $authUser, Stock $stock): bool
    {
        return $authUser->can('Restore:Stock');
    }

    public function forceDelete(AuthUser $authUser, Stock $stock): bool
    {
        return $authUser->can('ForceDelete:Stock');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Stock');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Stock');
    }

    public function replicate(AuthUser $authUser, Stock $stock): bool
    {
        return $authUser->can('Replicate:Stock');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Stock');
    }

}