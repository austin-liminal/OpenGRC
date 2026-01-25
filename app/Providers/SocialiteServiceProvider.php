<?php

namespace App\Providers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SocialiteServiceProvider extends ServiceProvider
{
    private bool $configured = false;

    /**
     * Register SSO config early so it's available when Filament panels boot.
     */
    public function register(): void
    {
        $this->configureSsoProviders();
    }

    public function boot(): void
    {
        // Re-run in boot to ensure config is set for any late initialization
        $this->configureSsoProviders();
    }

    private function configureSsoProviders(): void
    {
        // Only configure once and skip if running in console or settings table doesn't exist
        if ($this->configured || app()->runningInConsole() || ! Schema::hasTable('settings')) {
            return;
        }

        $this->configured = true;
        $baseUrl = config('app.url');

        // Configure Okta
        if (setting('auth.okta.enabled')) {
            $clientSecret = setting('auth.okta.client_secret');
            config([
                'services.okta' => [
                    'client_id' => setting('auth.okta.client_id'),
                    'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                    'base_url' => setting('auth.okta.base_url'),
                    'redirect' => "{$baseUrl}auth/okta/callback",
                ],
            ]);
        }

        // Configure Google
        if (setting('auth.google.enabled')) {
            $clientSecret = setting('auth.google.client_secret');
            config([
                'services.google' => [
                    'client_id' => setting('auth.google.client_id'),
                    'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                    'redirect' => "{$baseUrl}/auth/google/callback",
                ],
            ]);
        }

        // Configure Azure
        if (setting('auth.azure.enabled')) {
            $clientSecret = setting('auth.azure.client_secret');
            config([
                'services.azure' => [
                    'client_id' => setting('auth.azure.client_id'),
                    'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                    'tenant' => setting('auth.azure.tenant', 'common'),
                    'redirect' => "{$baseUrl}auth/azure/callback",
                ],
            ]);
        }

        // Configure Auth0
        if (setting('auth.auth0.enabled')) {
            $clientSecret = setting('auth.auth0.client_secret');
            config([
                'services.auth0' => [
                    'client_id' => setting('auth.auth0.client_id'),
                    'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                    'domain' => setting('auth.auth0.domain'),
                    'redirect' => "{$baseUrl}auth/auth0/callback",
                ],
            ]);
        }
    }
}
