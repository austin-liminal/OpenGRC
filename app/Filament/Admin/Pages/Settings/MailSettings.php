<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\MailSchema;
use Closure;
use Filament\Forms\Components\Section;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class MailSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

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
        return __('navigation.settings.mail_settings');
    }

    public function schema(): array|Closure
    {
        return [
            Section::make(__('navigation.settings.tabs.mail'))
                ->columns(3)
                ->schema(MailSchema::schema()),
        ];
    }
}