<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\GeneralSchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class Settings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

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
        return __('navigation.settings.general_settings');
    }

    public function schema(): array|Closure
    {
        return [
            Section::make('General Configuration')
                ->schema([
                    TextInput::make('general.name')
                        ->default('ets')
                        ->minLength(2)
                        ->maxLength(16)
                        ->label('Application Name')
                        ->helperText('The name of your application')
                        ->required(),
                    TextInput::make('general.url')
                        ->default('http://localhost')
                        ->url()
                        ->label('Application URL')
                        ->helperText('The URL of your application')
                        ->required(),
                    TextInput::make('general.repo')
                        ->default('https://repo.opengrc.com')
                        ->url()
                        ->label('Update Repository URL')
                        ->helperText('The URL of the repository to check for content updates')
                        ->required(),
                ]),
        ];
    }

}
