<?php

namespace App\Policies;

use App\Models\TrustCenterContentBlock;
use App\Models\User;

class TrustCenterContentBlockPolicy
{
    protected string $model = TrustCenterContentBlock::class;

    public function viewAny(User $user): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function view(User $user, TrustCenterContentBlock $contentBlock): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function create(User $user): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function update(User $user, TrustCenterContentBlock $contentBlock): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function delete(User $user, TrustCenterContentBlock $contentBlock): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function restore(User $user, TrustCenterContentBlock $contentBlock): bool
    {
        return $user->can('Manage Trust Center');
    }

    public function forceDelete(User $user, TrustCenterContentBlock $contentBlock): bool
    {
        return $user->can('Manage Trust Center');
    }
}
