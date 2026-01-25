<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\TrustCenterMailSchema;
use App\Filament\Admin\Pages\Settings\Schemas\TrustCenterNdaSchema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class TrustCenterSettings extends BaseSettings
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 9;

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
        return 'Trust Center';
    }

    public function getTitle(): string
    {
        return 'Trust Center Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('TrustCenterSettings')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('NDA'))
                            ->icon('heroicon-o-document-text')
                            ->schema(TrustCenterNdaSchema::schema()),
                        Tab::make(__('Email Templates'))
                            ->icon('heroicon-o-envelope')
                            ->schema(TrustCenterMailSchema::schema()),
                    ]),
            ]);
    }
}
