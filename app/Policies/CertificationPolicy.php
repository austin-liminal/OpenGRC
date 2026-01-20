<?php

namespace App\Policies;

use App\Models\Certification;
use App\Models\User;

class CertificationPolicy
{
    protected string $model = Certification::class;

    public function viewAny(User $user): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function view(User $user, Certification $certification): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function create(User $user): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function update(User $user, Certification $certification): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function delete(User $user, Certification $certification): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function restore(User $user, Certification $certification): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function forceDelete(User $user, Certification $certification): bool
    {
        return $user->can('Manage Trust Center');
    }
}
