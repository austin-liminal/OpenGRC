<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\VendorPortalSchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class VendorPortalSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function canAccess(): bool
    {
        if (auth()->check() && auth()->user()->can('Manage Preferences')) {
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
        return 'Vendor Portal';
    }

    public function getTitle(): string
    {
        return 'Vendor Portal Settings';
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('VendorPortalSettings')
                ->tabs([
                    Tabs\Tab::make('Configuration')
                        ->schema(VendorPortalSchema::schema()),
                ]),
        ];
    }
}
