<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class RiskScoringSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('Risk Scoring Thresholds'))
                ->description(__('Configure the score ranges for each risk level (0-100 scale). Scores are calculated based on survey responses and question weights.'))
                ->schema([
                    TextInput::make('vendor_portal.risk_threshold_very_low')
                        ->label(__('Very Low Threshold'))
                        ->numeric()
                        ->default(20)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->helperText(__('Scores 0 to this value = Very Low risk')),
                    TextInput::make('vendor_portal.risk_threshold_low')
                        ->label(__('Low Threshold'))
                        ->numeric()
                        ->default(40)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->helperText(__('Scores above Very Low to this value = Low risk')),
                    TextInput::make('vendor_portal.risk_threshold_medium')
                        ->label(__('Medium Threshold'))
                        ->numeric()
                        ->default(60)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->helperText(__('Scores above Low to this value = Medium risk')),
                    TextInput::make('vendor_portal.risk_threshold_high')
                        ->label(__('High Threshold'))
                        ->numeric()
                        ->default(80)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->helperText(__('Scores above Medium to this value = High risk')),
                    TextInput::make('vendor_portal.risk_threshold_critical')
                        ->label(__('Critical Threshold'))
                        ->numeric()
                        ->default(100)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->helperText(__('Scores above High to this value = Critical risk')),
                ]),
        ];
    }
}
