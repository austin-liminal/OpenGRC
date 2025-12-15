<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum VendorDocumentType: string implements HasColor, HasIcon, HasLabel
{
    case SOC2_REPORT = 'soc2_report';
    case ISO_CERTIFICATE = 'iso_certificate';
    case PENETRATION_TEST = 'penetration_test';
    case INSURANCE_CERTIFICATE = 'insurance_certificate';
    case BUSINESS_CONTINUITY_PLAN = 'business_continuity_plan';
    case PRIVACY_POLICY = 'privacy_policy';
    case SECURITY_POLICY = 'security_policy';
    case CONTRACT = 'contract';
    case SLA = 'sla';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::SOC2_REPORT => 'SOC 2 Report',
            self::ISO_CERTIFICATE => 'ISO Certificate',
            self::PENETRATION_TEST => 'Penetration Test Report',
            self::INSURANCE_CERTIFICATE => 'Insurance Certificate',
            self::BUSINESS_CONTINUITY_PLAN => 'Business Continuity Plan',
            self::PRIVACY_POLICY => 'Privacy Policy',
            self::SECURITY_POLICY => 'Security Policy',
            self::CONTRACT => 'Contract',
            self::SLA => 'Service Level Agreement',
            self::OTHER => 'Other',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SOC2_REPORT, self::ISO_CERTIFICATE => 'success',
            self::PENETRATION_TEST => 'warning',
            self::INSURANCE_CERTIFICATE => 'info',
            self::BUSINESS_CONTINUITY_PLAN => 'primary',
            self::PRIVACY_POLICY, self::SECURITY_POLICY => 'gray',
            self::CONTRACT, self::SLA => 'secondary',
            self::OTHER => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SOC2_REPORT => 'heroicon-o-shield-check',
            self::ISO_CERTIFICATE => 'heroicon-o-academic-cap',
            self::PENETRATION_TEST => 'heroicon-o-bug-ant',
            self::INSURANCE_CERTIFICATE => 'heroicon-o-document-check',
            self::BUSINESS_CONTINUITY_PLAN => 'heroicon-o-arrow-path',
            self::PRIVACY_POLICY => 'heroicon-o-eye-slash',
            self::SECURITY_POLICY => 'heroicon-o-lock-closed',
            self::CONTRACT => 'heroicon-o-document-text',
            self::SLA => 'heroicon-o-clock',
            self::OTHER => 'heroicon-o-document',
        };
    }
}
