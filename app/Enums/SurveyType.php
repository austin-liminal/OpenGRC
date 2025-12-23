<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SurveyType: string implements HasColor, HasIcon, HasLabel
{
    case VENDOR_ASSESSMENT = 'vendor_assessment';
    case INTERNAL_CHECKLIST = 'internal_checklist';
    case QUESTIONNAIRE = 'questionnaire';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VENDOR_ASSESSMENT => __('Vendor Assessment'),
            self::INTERNAL_CHECKLIST => __('Internal Checklist'),
            self::QUESTIONNAIRE => __('Questionnaire'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VENDOR_ASSESSMENT => 'primary',
            self::INTERNAL_CHECKLIST => 'success',
            self::QUESTIONNAIRE => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::VENDOR_ASSESSMENT => 'heroicon-o-clipboard-document-check',
            self::INTERNAL_CHECKLIST => 'heroicon-o-clipboard-document-list',
            self::QUESTIONNAIRE => 'heroicon-o-question-mark-circle',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::VENDOR_ASSESSMENT => __('Third-party vendor risk assessment survey'),
            self::INTERNAL_CHECKLIST => __('Internal compliance or security checklist'),
            self::QUESTIONNAIRE => __('General purpose questionnaire'),
        };
    }
}
