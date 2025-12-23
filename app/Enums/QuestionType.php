<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasColor, HasIcon, HasLabel
{
    case TEXT = 'text';
    case LONG_TEXT = 'long_text';
    case FILE = 'file';
    case SINGLE_CHOICE = 'single_choice';
    case MULTIPLE_CHOICE = 'multiple_choice';
    case BOOLEAN = 'boolean';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TEXT => 'Short Text',
            self::LONG_TEXT => 'Long Text',
            self::FILE => 'File Upload',
            self::SINGLE_CHOICE => 'Single Choice',
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::BOOLEAN => 'Yes/No',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TEXT => 'gray',
            self::LONG_TEXT => 'gray',
            self::FILE => 'warning',
            self::SINGLE_CHOICE => 'primary',
            self::MULTIPLE_CHOICE => 'primary',
            self::BOOLEAN => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::TEXT => 'heroicon-o-bars-2',
            self::LONG_TEXT => 'heroicon-o-bars-4',
            self::FILE => 'heroicon-o-paper-clip',
            self::SINGLE_CHOICE => 'heroicon-o-check-circle',
            self::MULTIPLE_CHOICE => 'heroicon-o-list-bullet',
            self::BOOLEAN => 'heroicon-o-hand-thumb-up',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::SINGLE_CHOICE, self::MULTIPLE_CHOICE]);
    }
}
