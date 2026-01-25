<?php

namespace App\Livewire;

use DB;
use EightCedars\FilamentInactivityGuard\Livewire\SessionGuard;
use Filament\Facades\Filament;

class CustomSessionGuard extends SessionGuard
{
    public function keepAlive(): void
    {
        // Touch the session to extend the Laravel session lifetime
        session()->put('last_activity', now()->timestamp);

        // Also update the user's last_activity in the database if needed
        if (Filament::auth()->check()) {
            DB::table('users')
                ->where('id', Filament::auth()->id())
                ->update(['last_activity' => now()]);
        }
    }
}
