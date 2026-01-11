<?php

namespace App\Policies;

use App\Models\TrustCenterDocument;
use App\Models\User;

class TrustCenterDocumentPolicy
{
    protected string $model = TrustCenterDocument::class;

    public function viewAny(User $user): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function view(User $user, TrustCenterDocument $document): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function create(User $user): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function update(User $user, TrustCenterDocument $document): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function delete(User $user, TrustCenterDocument $document): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function restore(User $user, TrustCenterDocument $document): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function forceDelete(User $user, TrustCenterDocument $document): bool
    {
        return $user->can('Manage Trust Center');
    }
}
