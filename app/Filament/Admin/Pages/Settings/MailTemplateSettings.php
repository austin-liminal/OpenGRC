<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\MailTemplatesSchema;
use App\Filament\Admin\Pages\Settings\Schemas\SurveySettingsSchema;
use App\Filament\Admin\Pages\Settings\Schemas\VendorPortalMailSchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class MailTemplateSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function canAccess(): bool
    {
        if (auth()->check() && auth()->user()->can('Manage Preferences') && setting('storage.locked') != "true") {
            return true;
        }

        return false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings.templates');
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('MailTemplateSettings')
                ->tabs([
                    Tabs\Tab::make(__('navigation.settings.tabs.mail_templates'))
                        ->icon('heroicon-o-envelope')
                        ->schema(MailTemplatesSchema::schema()),
                    Tabs\Tab::make(__('Surveys'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema(SurveySettingsSchema::schema()),
                    Tabs\Tab::make(__('Vendor Portal'))
                        ->icon('heroicon-o-building-storefront')
                        ->schema(VendorPortalMailSchema::schema()),
                ]),
        ];
    }
}