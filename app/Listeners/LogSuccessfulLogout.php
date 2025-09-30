<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use App\Models\User;


class LogSuccessfulLogout
{
    public function handle(Logout $event)
    {
        $badUser = new User();
        $badUser->id = -1;
        $badUser->email = $event->credentials['email'] ?? 'unknown';
        $event->name = "Invalid User";

        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('Logout')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged out');

    \Log::info("APPLOG - User logged out", [
            'user_id' => $event->user->id ?? $badUser->id,
            'email' => $event->user->email ?? $badUser->email,
            'ip' => request()->ip(),
            'host' => request()->header('host'),
            'forwarded_for' => request()->header('X-Forwarded-For'),
            'referer' => request()->header('referer'),
            'content-length' => request()->header('content-length'),
            'user_agent' => request()->userAgent(),
        ]);
    }
}