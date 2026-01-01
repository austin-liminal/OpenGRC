<?php

namespace App\Filament\Admin\Pages\Settings;

use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Crypt;

class AiSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-variable';

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
        return __('navigation.settings.ai_settings');
    }

    public function schema(): array|Closure
    {
        $isLocked = setting('storage.locked') === 'true';

        return [
            Section::make('AI Configuration')
                ->schema([
                    Toggle::make('ai.enabled')
                        ->label('Enable AI Suggestions')
                        ->default(false),
                    TextInput::make('ai.openai_key')
                        ->label('OpenAI API Key (Optional)')
                        ->disabled($isLocked)
                        ->password()
                        ->placeholder(fn () => filled(setting('ai.openai_key')) ? '••••••••' : null)
                        ->helperText(fn () => filled(setting('ai.openai_key'))
                            ? 'API key is stored securely. Leave blank to keep current key.'
                            : 'The API key for OpenAI')
                        ->dehydrateStateUsing(function ($state) {
                            // If blank, keep the existing encrypted key
                            if (! filled($state)) {
                                return setting('ai.openai_key');
                            }

                            // Encrypt the new key
                            return Crypt::encryptString($state);
                        })
                        ->afterStateHydrated(function (TextInput $component, $state) {
                            // Never populate the field with the actual key
                            // This prevents the key from appearing in the Livewire payload
                            $component->state(null);
                        }),
                ]),
        ];
    }
}
