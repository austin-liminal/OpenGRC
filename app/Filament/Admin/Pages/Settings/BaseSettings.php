<?php

namespace App\Filament\Admin\Pages\Settings;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use MangoldSecurity\FilamentSettings\Pages\Settings as PackageSettings;
use MangoldSecurity\Settings\Facades\Setting;
use MangoldSecurity\Settings\Models\Setting as SettingModel;

/**
 * Base settings class that filters sensitive data from the Livewire payload.
 *
 * All settings pages should extend this class instead of the package's Settings class
 * to ensure sensitive credentials are not exposed in the browser DOM.
 */
abstract class BaseSettings extends PackageSettings
{
    /**
     * Override mount to fix Filament 4 compatibility issue where the package
     * tries to call getChildComponents() on Action objects.
     */
    public function mount(): void
    {
        $this->form->components(
            collect($this->form->getComponents(true, true))
                ->map(function (Component|Action|ActionGroup $component): Component|Action|ActionGroup {
                    return $this->addModelToFieldComponentsRecursively($component);
                })
                ->all(),
        );

        $this->fillForm();
    }

    /**
     * Fixed version of addModelToFieldComponentsRecursively that properly handles
     * Actions and ActionGroups in Filament 4 (they don't have getChildComponents).
     */
    private function addModelToFieldComponentsRecursively(Component|Action|ActionGroup $component): Component|Action|ActionGroup
    {
        if ($component instanceof Field) {
            $component->model(function (Field $component): SettingModel {
                return $this->getModelForField($component);
            });
        }

        // Actions and ActionGroups don't have child components in Filament 4
        if ($component instanceof Action || $component instanceof ActionGroup) {
            return $component;
        }

        // Only process child components for actual Components
        if ($component instanceof Component && method_exists($component, 'getChildComponents')) {
            return $component->childComponents(
                collect($component->getChildComponents())
                    ->map(function (Component|Action|ActionGroup $childComponent): Action|ActionGroup|Component {
                        return $this->addModelToFieldComponentsRecursively($childComponent);
                    })
                    ->all(),
            );
        }

        return $component;
    }

    /**
     * Get the model for a field (copied from parent package).
     */
    private function getModelForField(Field $field): SettingModel
    {
        return Setting::model()::query()
            ->firstOrNew(['key' => $field->getName()]);
    }

    /**
     * Override mutateFormDataBeforeFill to exclude sensitive data from initial data load.
     * This prevents encrypted credentials from appearing in the Livewire payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove sensitive credentials from the data
        // These fields use placeholder patterns and only update when new values are entered
        $this->removeSensitiveData($data);

        return $data;
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
