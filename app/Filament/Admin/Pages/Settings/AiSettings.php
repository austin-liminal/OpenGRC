<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\AiQuotaSchema;
use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
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
            Tabs::make('AiSettings')
                ->tabs([
                    Tabs\Tab::make('Configuration')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
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
                            Section::make('MCP Server')
                                ->description('Model Context Protocol (MCP) allows AI assistants like Claude to interact with OpenGRC data.')
                                ->schema([
                                    Toggle::make('mcp.enabled')
                                        ->label('Enable MCP Server')
                                        ->helperText('When enabled, authenticated API clients can access OpenGRC via the MCP protocol at /mcp/opengrc')
                                        ->default(false),
                                ]),
                        ]),
                    Tabs\Tab::make('Quota Usage')
                        ->icon('heroicon-o-chart-bar')
                        ->schema(AiQuotaSchema::schema()),
                ]),
        ];
    }
}
