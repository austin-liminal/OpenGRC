<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RiskImpact: string implements HasColor, HasLabel
{
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';
    case NEUTRAL = 'neutral';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::POSITIVE => 'Positive (Yes reduces risk)',
            self::NEGATIVE => 'Negative (Yes increases risk)',
            self::NEUTRAL => 'Neutral (Informational only)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::POSITIVE => 'success',
            self::NEGATIVE => 'danger',
            self::NEUTRAL => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::POSITIVE => 'A "Yes" answer reduces risk (e.g., "Do you have MFA enabled?")',
            self::NEGATIVE => 'A "Yes" answer increases risk (e.g., "Do you store data in unencrypted form?")',
            self::NEUTRAL => 'Answer has no impact on risk score (informational question)',
        };
    }
}
