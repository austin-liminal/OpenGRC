<?php

namespace App\Filament\Admin\Pages\Settings;

use Outerweb\FilamentSettings\Filament\Pages\Settings as PackageSettings;
use Outerweb\Settings\Models\Setting;

/**
 * Base settings class that filters sensitive data from the Livewire payload.
 *
 * All settings pages should extend this class instead of the package's Settings class
 * to ensure sensitive credentials are not exposed in the browser DOM.
 */
abstract class BaseSettings extends PackageSettings
{
    /**
     * Override fillForm to exclude sensitive data from initial data load.
     * This prevents encrypted credentials from appearing in the Livewire payload.
     */
    protected function fillForm(): void
    {
        $data = Setting::get();

        // Remove sensitive credentials from the data
        // These fields use placeholder patterns and only update when new values are entered
        $this->removeSensitiveData($data);

        $this->callHook('beforeFill');

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    /**
     * Remove sensitive data from the settings array.
     * Override this method to add additional sensitive fields.
     */
    protected function removeSensitiveData(array &$data): void
    {
        // Mail password - encrypted and should never be sent to browser
        if (isset($data['mail']['password'])) {
            unset($data['mail']['password']);
        }

        // AI OpenAI API key - encrypted and should never be sent to browser
        if (isset($data['ai']['openai_key'])) {
            unset($data['ai']['openai_key']);
        }

        // Storage credentials - encrypted and should never be sent to browser
        // S3 credentials
        if (isset($data['storage']['s3']['key'])) {
            unset($data['storage']['s3']['key']);
        }
        if (isset($data['storage']['s3']['secret'])) {
            unset($data['storage']['s3']['secret']);
        }

        // DigitalOcean Spaces credentials
        if (isset($data['storage']['digitalocean']['key'])) {
            unset($data['storage']['digitalocean']['key']);
        }
        if (isset($data['storage']['digitalocean']['secret'])) {
            unset($data['storage']['digitalocean']['secret']);
        }

        // SSO client secrets - encrypted and should never be sent to browser
        if (isset($data['auth']['azure']['client_secret'])) {
            unset($data['auth']['azure']['client_secret']);
        }
        if (isset($data['auth']['okta']['client_secret'])) {
            unset($data['auth']['okta']['client_secret']);
        }
        if (isset($data['auth']['google']['client_secret'])) {
            unset($data['auth']['google']['client_secret']);
        }
        if (isset($data['auth']['auth0']['client_secret'])) {
            unset($data['auth']['auth0']['client_secret']);
        }
    }
}
