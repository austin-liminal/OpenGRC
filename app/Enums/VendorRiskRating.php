<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorRiskRating: string implements HasColor, HasLabel
{
    case VERY_LOW = 'Very Low';
    case LOW = 'Low';
    case MEDIUM = 'Medium';
    case HIGH = 'High';
    case CRITICAL = 'Critical';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VERY_LOW => __('Very Low'),
            self::LOW => __('Low'),
            self::MEDIUM => __('Medium'),
            self::HIGH => __('High'),
            self::CRITICAL => __('Critical'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VERY_LOW => 'success',
            self::LOW => 'info',
            self::MEDIUM => 'warning',
            self::HIGH => 'orange',
            self::CRITICAL => 'danger',
        };
    }

    public static function fromScore(?int $score): self
    {
        if ($score === null) {
            return self::VERY_LOW;
        }

        $thresholds = [
            'very_low' => (int) setting('vendor_portal.risk_threshold_very_low', 20),
            'low' => (int) setting('vendor_portal.risk_threshold_low', 40),
            'medium' => (int) setting('vendor_portal.risk_threshold_medium', 60),
            'high' => (int) setting('vendor_portal.risk_threshold_high', 80),
        ];

        return match (true) {
            $score <= $thresholds['very_low'] => self::VERY_LOW,
            $score <= $thresholds['low'] => self::LOW,
            $score <= $thresholds['medium'] => self::MEDIUM,
            $score <= $thresholds['high'] => self::HIGH,
            default => self::CRITICAL,
        };
    }
}
