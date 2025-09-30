<?php
namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    public function handle(Failed $event)
    {

        $badUser = new User();
        $badUser->id = -1;
        $badUser->email = $event->credentials['email'] ?? 'unknown';
        $event->name = "Invalid User";


        activity('auth')
            ->event('Failed Login')
            ->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Failed login attempt');

        \Log::warning("APPLOG - Failed Login Attempt", [
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