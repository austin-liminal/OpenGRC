<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SurveyStatus: string implements HasColor, HasDescription, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'primary',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::EXPIRED => 'danger',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Survey has been created but not sent.',
            self::SENT => 'Survey has been sent to the respondent.',
            self::IN_PROGRESS => 'Respondent has started answering the survey.',
            self::COMPLETED => 'All required questions have been answered.',
            self::EXPIRED => 'Survey has passed its due date without completion.',
        };
    }
}
