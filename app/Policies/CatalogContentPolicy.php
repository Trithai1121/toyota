<?php

namespace App\Policies;

use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CatalogContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function update(User $user, Model $record): bool
    {
        if (
            $record instanceof ThemeSetting
            && ($record->key === 'tracking' || $record->getOriginal('key') === 'tracking')
        ) {
            return $user->hasRole('admin');
        }

        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
