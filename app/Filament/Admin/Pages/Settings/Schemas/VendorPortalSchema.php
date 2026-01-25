<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use App\Models\SurveyTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class VendorPortalSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('General Settings'))
                ->schema([
                    Toggle::make('vendor_portal.enabled')
                        ->label(__('Enable Vendor Portal'))
                        ->helperText(__('Allow vendors to access the portal'))
                        ->default(true),
                    TextInput::make('vendor_portal.name')
                        ->label(__('Portal Name'))
                        ->placeholder('Vendor Portal')
                        ->helperText(__('Display name shown to vendors')),
                    Select::make('vendor_portal.default_survey_template_id')
                        ->label(__('Default Survey Template'))
                        ->options(SurveyTemplate::pluck('title', 'id'))
                        ->searchable()
                        ->helperText(__('Default survey template for new vendor assessments')),
                ]),

            Section::make(__('Magic Link Settings'))
                ->schema([
                    TextInput::make('vendor_portal.magic_link_expiry_hours')
                        ->label(__('Magic Link Expiry (hours)'))
                        ->numeric()
                        ->default(24)
                        ->minValue(1)
                        ->maxValue(168)
                        ->helperText(__('How long magic links remain valid')),
                ]),

            Section::make(__('Session Settings'))
                ->schema([
                    TextInput::make('vendor_portal.session_timeout_minutes')
                        ->label(__('Session Timeout (minutes)'))
                        ->numeric()
                        ->default(120)
                        ->minValue(5)
                        ->maxValue(1440)
                        ->helperText(__('Vendor session inactivity timeout')),
                ]),
        ];
    }
}
