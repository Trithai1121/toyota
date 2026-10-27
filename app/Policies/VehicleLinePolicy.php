<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleLine;

class VehicleLinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function view(User $user, VehicleLine $vehicleLine): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function update(User $user, VehicleLine $vehicleLine): bool
    {
        return $user->hasAnyRole(['admin', 'marketing']);
    }

    public function delete(User $user, VehicleLine $vehicleLine): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
