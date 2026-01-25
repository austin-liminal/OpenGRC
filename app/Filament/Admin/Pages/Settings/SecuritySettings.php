<?php

namespace App\Filament\Admin\Pages\Settings;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SecuritySettings extends BaseSettings
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 7;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Security Configuration')
                    ->columnSpanFull()
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
            ]);
    }
}
