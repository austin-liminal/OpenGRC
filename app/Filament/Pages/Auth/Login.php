<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;

class Login extends \Filament\Auth\Pages\Login
{
    public function authenticate(): ?LoginResponse
    {
        $result = parent::authenticate();

        // Update last activity after successful login
        auth()->user()?->updateLastActivity();

        return $result;
    }
}
