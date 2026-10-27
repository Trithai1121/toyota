<?php

namespace App\Policies;

use App\Models\PageRevision;
use App\Models\User;

class PageRevisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function view(User $user, PageRevision $revision): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function update(User $user, PageRevision $revision): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, PageRevision $revision): bool
    {
        return false;
    }
}
