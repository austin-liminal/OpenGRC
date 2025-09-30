<?php
namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminage\Support\Facades\Log;
use App\Models\User;


class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        $badUser = new User();
        $badUser->id = -1;
        $badUser->email = $event->credentials['email'] ?? 'unknown';
        $event->name = "Invalid User";

        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('Login')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in');

	\Log::info("APPLOG - User logged in", [
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
