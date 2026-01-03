<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuotaType: string implements HasColor, HasLabel
{
    case AI_PROMPT = 'ai_prompt';
    case AI_RESPONSE = 'ai_response';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AI_PROMPT => 'AI Prompt Tokens',
            self::AI_RESPONSE => 'AI Response Tokens',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AI_PROMPT => 'info',
            self::AI_RESPONSE => 'success',
        };
    }

    /**
     * Get the environment variable name for this quota's limit.
     */
    public function getEnvKey(): string
    {
        return match ($this) {
            self::AI_PROMPT => 'AI_PROMPT_QUOTA',
            self::AI_RESPONSE => 'AI_RESPONSE_QUOTA',
        };
    }

    /**
     * Get the default quota limit.
     */
    public function getDefaultLimit(): int
    {
        return match ($this) {
            self::AI_PROMPT => 1000000,
            self::AI_RESPONSE => 1000000,
        };
    }

    /**
     * Get the cache key for daily usage tracking.
     */
    public function getCacheKey(): string
    {
        $date = now()->format('Y-m-d');

        return "quota:{$this->value}:{$date}";
    }
}
