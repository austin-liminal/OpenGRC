<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\MailTemplatesSchema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class MailTemplateSettings extends BaseSettings
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function canAccess(): bool
    {
        if (auth()->check() && auth()->user()->can('Manage Preferences') && setting('storage.locked') != 'true') {
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
        return __('navigation.settings.templates');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('MailTemplateSettings')
                    ->tabs([
                        Tab::make(__('navigation.settings.tabs.mail_templates'))
                            ->icon('heroicon-o-envelope')
                            ->schema(MailTemplatesSchema::schema()),
                    ]),
            ]);
    }
}
