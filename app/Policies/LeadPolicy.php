<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'sale']);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('sale') && $lead->assigned_to === $user->id);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('sale') && $lead->assigned_to === $user->id);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function export(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
