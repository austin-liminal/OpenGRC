<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\SecuritySchema;
use Closure;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;

class SecuritySettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

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
        return __('navigation.settings.security_settings');
    }

    public function schema(): array|Closure
    {
        return [
            Section::make('Security Configuration')
                ->schema([
                    TextInput::make('security.session_timeout')
                        ->label('Session Timeout (minutes)')
                        ->numeric()
                        ->default(15)
                        ->minValue(1)
                        ->maxValue(1440)
                        ->required()
                        ->helperText('Number of minutes before an inactive session expires. Default: 15 minutes'),
                ]),
        ];
    }
}