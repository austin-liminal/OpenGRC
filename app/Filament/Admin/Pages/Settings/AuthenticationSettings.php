<?php

namespace App\Filament\Admin\Pages\Settings;

use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;

class AuthenticationSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

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
        return __('navigation.settings.authentication_settings');
    }

    public function schema(): array|Closure
    {
        return [
            // Azure Authentication
            Section::make('Azure Authentication')
                ->schema([
                    Toggle::make('auth.azure.enabled')
                        ->label('Enable Azure Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.azure.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->required(fn ($get) => $get('auth.azure.enabled')),
                            TextInput::make('auth.azure.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->required(fn ($get) => $get('auth.azure.enabled'))
                                ->placeholder(fn () => filled(setting('auth.azure.client_secret')) ? '••••••••' : null)
                                ->helperText(fn () => filled(setting('auth.azure.client_secret'))
                                    ? 'Secret is stored securely. Leave blank to keep current secret.'
                                    : null)
                                ->dehydrateStateUsing(function ($state) {
                                    if (! filled($state)) {
                                        return setting('auth.azure.client_secret');
                                    }

                                    return Crypt::encryptString($state);
                                })
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    $component->state(null);
                                }),
                            TextInput::make('auth.azure.tenant')
                                ->label('Tenant')
                                ->placeholder('common')
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->required(fn ($get) => $get('auth.azure.enabled')),
                            Placeholder::make('auth.azure.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->content(config('app.url').'auth/azure/callback'),
                            Toggle::make('auth.azure.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Azure.'),
                            Select::make('auth.azure.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.azure.enabled') && $get('auth.azure.auto_provision'))
                                ->required(fn ($get) => $get('auth.azure.enabled') && $get('auth.azure.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),

            // Okta Authentication
            Section::make('Okta Authentication')
                ->schema([
                    Toggle::make('auth.okta.enabled')
                        ->label('Enable Okta Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.okta.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->required(fn ($get) => $get('auth.okta.enabled')),
                            TextInput::make('auth.okta.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->required(fn ($get) => $get('auth.okta.enabled'))
                                ->placeholder(fn () => filled(setting('auth.okta.client_secret')) ? '••••••••' : null)
                                ->helperText(fn () => filled(setting('auth.okta.client_secret'))
                                    ? 'Secret is stored securely. Leave blank to keep current secret.'
                                    : null)
                                ->dehydrateStateUsing(function ($state) {
                                    if (! filled($state)) {
                                        return setting('auth.okta.client_secret');
                                    }

                                    return Crypt::encryptString($state);
                                })
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    $component->state(null);
                                }),
                            TextInput::make('auth.okta.base_url')
                                ->label('Base URL')
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->required(fn ($get) => $get('auth.okta.enabled')),
                            Placeholder::make('auth.okta.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->content(config('app.url').'auth/okta/callback'),
                            Toggle::make('auth.okta.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Okta.'),
                            Select::make('auth.okta.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.okta.enabled') && $get('auth.okta.auto_provision'))
                                ->required(fn ($get) => $get('auth.okta.enabled') && $get('auth.okta.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),

            // Google Authentication
            Section::make('Google Authentication')
                ->schema([
                    Toggle::make('auth.google.enabled')
                        ->label('Enable Google Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.google.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->required(fn ($get) => $get('auth.google.enabled')),
                            TextInput::make('auth.google.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->required(fn ($get) => $get('auth.google.enabled'))
                                ->placeholder(fn () => filled(setting('auth.google.client_secret')) ? '••••••••' : null)
                                ->helperText(fn () => filled(setting('auth.google.client_secret'))
                                    ? 'Secret is stored securely. Leave blank to keep current secret.'
                                    : null)
                                ->dehydrateStateUsing(function ($state) {
                                    if (! filled($state)) {
                                        return setting('auth.google.client_secret');
                                    }

                                    return Crypt::encryptString($state);
                                })
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    $component->state(null);
                                }),
                            Placeholder::make('auth.google.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->content(config('app.url').'auth/google/callback'),
                            Toggle::make('auth.google.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Google.'),
                            Select::make('auth.google.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.google.enabled') && $get('auth.google.auto_provision'))
                                ->required(fn ($get) => $get('auth.google.enabled') && $get('auth.google.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),

            // Auth0 Authentication
            Section::make('Auth0 Authentication')
                ->schema([
                    Toggle::make('auth.auth0.enabled')
                        ->label('Enable Auth0 Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.auth0.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->required(fn ($get) => $get('auth.auth0.enabled')),
                            TextInput::make('auth.auth0.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->required(fn ($get) => $get('auth.auth0.enabled'))
                                ->placeholder(fn () => filled(setting('auth.auth0.client_secret')) ? '••••••••' : null)
                                ->helperText(fn () => filled(setting('auth.auth0.client_secret'))
                                    ? 'Secret is stored securely. Leave blank to keep current secret.'
                                    : null)
                                ->dehydrateStateUsing(function ($state) {
                                    if (! filled($state)) {
                                        return setting('auth.auth0.client_secret');
                                    }

                                    return Crypt::encryptString($state);
                                })
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    $component->state(null);
                                }),
                            TextInput::make('auth.auth0.domain')
                                ->label('Domain')
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->required(fn ($get) => $get('auth.auth0.enabled')),
                            Placeholder::make('auth.auth0.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->content(config('app.url').'auth/auth0/callback'),
                            Toggle::make('auth.auth0.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Auth0.'),
                            Select::make('auth.auth0.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.auth0.enabled') && $get('auth.auth0.auto_provision'))
                                ->required(fn ($get) => $get('auth.auth0.enabled') && $get('auth.auth0.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),
        ];
    }
}
