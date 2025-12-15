<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use App\Models\SurveyTemplate;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class VendorPortalSchema
{
    public static function schema(): array
    {
        return [
            Section::make('General Settings')
                ->schema([
                    Toggle::make('vendor_portal.enabled')
                        ->label('Enable Vendor Portal')
                        ->helperText('Allow vendors to access the portal')
                        ->default(true),
                    TextInput::make('vendor_portal.name')
                        ->label('Portal Name')
                        ->placeholder('Vendor Portal')
                        ->helperText('Display name shown to vendors'),
                    Select::make('vendor_portal.default_survey_template_id')
                        ->label('Default Survey Template')
                        ->options(SurveyTemplate::pluck('title', 'id'))
                        ->searchable()
                        ->helperText('Default survey template for new vendor assessments'),
                ]),

            Section::make('Risk Scoring Thresholds')
                ->description('Configure the score ranges for each risk level (0-100 scale)')
                ->schema([
                    TextInput::make('vendor_portal.risk_threshold_very_low')
                        ->label('Very Low Threshold')
                        ->numeric()
                        ->default(20)
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Scores 0 to this value = Very Low risk'),
                    TextInput::make('vendor_portal.risk_threshold_low')
                        ->label('Low Threshold')
                        ->numeric()
                        ->default(40)
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Scores above Very Low to this value = Low risk'),
                    TextInput::make('vendor_portal.risk_threshold_medium')
                        ->label('Medium Threshold')
                        ->numeric()
                        ->default(60)
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Scores above Low to this value = Medium risk'),
                    TextInput::make('vendor_portal.risk_threshold_high')
                        ->label('High Threshold')
                        ->numeric()
                        ->default(80)
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Scores above Medium to this value = High risk. Above this = Critical'),
                ]),

            Section::make('Magic Link Settings')
                ->schema([
                    TextInput::make('vendor_portal.magic_link_expiry_hours')
                        ->label('Magic Link Expiry (hours)')
                        ->numeric()
                        ->default(24)
                        ->minValue(1)
                        ->maxValue(168)
                        ->helperText('How long magic links remain valid'),
                ]),

            Section::make('Session Settings')
                ->schema([
                    TextInput::make('vendor_portal.session_timeout_minutes')
                        ->label('Session Timeout (minutes)')
                        ->numeric()
                        ->default(120)
                        ->minValue(5)
                        ->maxValue(1440)
                        ->helperText('Vendor session inactivity timeout'),
                ]),
        ];
    }
}
