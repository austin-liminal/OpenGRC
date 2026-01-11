<?php

namespace App\Policies;

use App\Models\DataRequestResponse;
use App\Models\User;
use Illuminate\Support\Str;
use Kirschbaum\Commentions\CommentSubscription;

class DataRequestResponsePolicy
{
    protected string $model = DataRequestResponse::class;

    public function viewAny(User $user): bool
    {
        return $user->can('List '.Str::plural(class_basename($this->model)));
    }

    public function view(User $user, DataRequestResponse $dataRequestResponse): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (! $user->can('Read '.Str::plural(class_basename($this->model)))) {
            return false;
        }

        // User is the requestee
        if ($dataRequestResponse->requestee_id === $user->id) {
            return true;
        }

        // User is subscribed (tagged/following) to this response
        if ($this->isSubscribed($user, $dataRequestResponse)) {
            return true;
        }

        // User was mentioned in a comment on this response
        if ($this->wasMentioned($user, $dataRequestResponse)) {
            return true;
        }

        return false;
    }

    protected function isSubscribed(User $user, DataRequestResponse $dataRequestResponse): bool
    {
        return CommentSubscription::where('subscribable_type', DataRequestResponse::class)
            ->where('subscribable_id', $dataRequestResponse->id)
            ->where('subscriber_type', User::class)
            ->where('subscriber_id', $user->id)
            ->exists();
    }

    protected function wasMentioned(User $user, DataRequestResponse $dataRequestResponse): bool
    {
        return $dataRequestResponse->comments()
            ->where('body', 'like', '%data-id="'.$user->id.'"%')
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.Str::plural(class_basename($this->model)));
    }

    public function update(User $user, DataRequestResponse $dataRequestResponse): bool
    {
        return $dataRequestResponse->requestee_id === $user->id;
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model)));
    }
}
