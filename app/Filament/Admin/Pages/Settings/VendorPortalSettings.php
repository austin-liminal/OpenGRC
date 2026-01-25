<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\RiskScoringSchema;
use App\Filament\Admin\Pages\Settings\Schemas\SurveySettingsSchema;
use App\Filament\Admin\Pages\Settings\Schemas\VendorPortalMailSchema;
use App\Filament\Admin\Pages\Settings\Schemas\VendorPortalSchema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class VendorPortalSettings extends BaseSettings
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 8;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('VendorPortalSettings')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('Configuration'))
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema(VendorPortalSchema::schema()),
                        Tab::make(__('Risk Scoring'))
                            ->icon('heroicon-o-chart-bar')
                            ->schema(RiskScoringSchema::schema()),
                        Tab::make(__('Email Templates'))
                            ->icon('heroicon-o-envelope')
                            ->schema(VendorPortalMailSchema::schema()),
                        Tab::make(__('Surveys'))
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema(SurveySettingsSchema::schema()),
                    ]),
            ]);
    }
}
