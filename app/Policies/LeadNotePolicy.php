<?php

namespace App\Policies;

use App\Models\LeadNote;
use App\Models\User;

class LeadNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'sale']);
    }

    public function view(User $user, LeadNote $leadNote): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('sale') && $leadNote->lead()->where('assigned_to', $user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'sale']);
    }

    public function update(User $user, LeadNote $leadNote): bool
    {
        return false;
    }

    public function delete(User $user, LeadNote $leadNote): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
