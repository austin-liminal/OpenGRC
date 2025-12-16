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
    case PENDING_SCORING = 'pending_scoring';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::SENT => __('Sent'),
            self::IN_PROGRESS => __('In Progress'),
            self::PENDING_SCORING => __('Pending Scoring'),
            self::COMPLETED => __('Completed'),
            self::EXPIRED => __('Expired'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'primary',
            self::IN_PROGRESS => 'warning',
            self::PENDING_SCORING => 'orange',
            self::COMPLETED => 'success',
            self::EXPIRED => 'danger',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::DRAFT => __('Survey has been created but not sent.'),
            self::SENT => __('Survey has been sent to the respondent.'),
            self::IN_PROGRESS => __('Respondent has started answering the survey.'),
            self::PENDING_SCORING => __('Survey responses submitted but manual scoring required.'),
            self::COMPLETED => __('All questions have been answered and scored.'),
            self::EXPIRED => __('Survey has passed its due date without completion.'),
        };
    }
}
