<?php

namespace App\Filament\Admin\Pages\Settings;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ReportSettings extends BaseSettings
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

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
        return __('navigation.settings.report_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report Configuration')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('report.logo')
                            ->label('Custom Report Logo (Optional)')
                            ->helperText('The logo to display on reports. Be sure to upload a file that is at least 512px wide.')
                            ->acceptedFileTypes(['image/*'])
                            ->directory('report-assets')
                            ->image()
                            ->disk(fn () => config('filesystems.default'))
                            ->visibility('private')
                            ->maxFiles(1)
                            ->imagePreviewHeight('300px')
                            ->deleteUploadedFileUsing(function ($state) {
                                if ($state) {
                                    Storage::disk(config('filesystems.default'))->delete($state);
                                }
                            }),
                    ]),
            ]);
    }
}
