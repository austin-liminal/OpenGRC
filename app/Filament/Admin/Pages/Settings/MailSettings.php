<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\MailSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MailSettings extends BaseSettings
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

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
        return __('navigation.settings.mail_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('navigation.settings.tabs.mail'))
                    ->columns(3)
                    ->schema(MailSchema::schema()),
            ]);
    }
}
