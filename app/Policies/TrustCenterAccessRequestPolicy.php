<?php

namespace App\Policies;

use App\Models\TrustCenterAccessRequest;
use App\Models\User;

class TrustCenterAccessRequestPolicy
{
    protected string $model = TrustCenterAccessRequest::class;

    public function viewAny(User $user): bool
    {
        return $user->can('Manage Trust Center') || $user->can('Manage Trust Access');
    }

    public function view(User $user, TrustCenterAccessRequest $accessRequest): bool
    {
        return $user->can('Manage Trust Center') || $user->can('Manage Trust Access');
    }

    public function create(User $user): bool
    {
        // Access requests are created by external users, not internal users
        return $user->can('Manage Trust Center');
    }

    public function update(User $user, TrustCenterAccessRequest $accessRequest): bool
    {
        // Both permissions can approve/deny/revoke requests
        return $user->can('Manage Trust Center') || $user->can('Manage Trust Access');
    }

    public function delete(User $user, TrustCenterAccessRequest $accessRequest): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function restore(User $user, TrustCenterAccessRequest $accessRequest): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function forceDelete(User $user, TrustCenterAccessRequest $accessRequest): bool
    {
        return $user->can('Manage Trust Center');
    }
}
