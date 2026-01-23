<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasColor, HasIcon, HasLabel
{
    case Policy = 'policy';
    case Procedure = 'procedure';
    case Standard = 'standard';
    case Guide = 'guide';
    case Handbook = 'handbook';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Policy => 'Policy',
            self::Procedure => 'Procedure',
            self::Standard => 'Standard',
            self::Guide => 'Guide',
            self::Handbook => 'Handbook',
            self::Other => 'Other',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Policy => 'primary',
            self::Procedure => 'info',
            self::Standard => 'success',
            self::Guide => 'warning',
            self::Handbook => 'secondary',
            self::Other => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Policy => 'heroicon-o-document-text',
            self::Procedure => 'heroicon-o-list-bullet',
            self::Standard => 'heroicon-o-shield-check',
            self::Guide => 'heroicon-o-book-open',
            self::Handbook => 'heroicon-o-book-open',
            self::Other => 'heroicon-o-document',
        };
    }
}
