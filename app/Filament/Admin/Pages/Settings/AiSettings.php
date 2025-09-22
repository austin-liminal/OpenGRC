<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\AiSchema;
use Closure;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
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
        $isLocked = setting('storage.locked') === "true";
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
                        ->helperText('The API key for OpenAI')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                        ->afterStateHydrated(function (TextInput $component, $state) {
                            if (filled($state)) {
                                try {
                                    $component->state(Crypt::decryptString($state));
                                } catch (\Exception $e) {
                                    $component->state('');
                                }
                            }
                        }),
                ]),
        ];
    }
}