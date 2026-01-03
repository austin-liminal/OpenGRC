<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use App\Enums\QuotaType;
use App\Services\QuotaService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Illuminate\Support\HtmlString;

class AiQuotaSchema
{
    public static function schema(): array
    {
        $promptStats = QuotaService::getStats(QuotaType::AI_PROMPT);
        $responseStats = QuotaService::getStats(QuotaType::AI_RESPONSE);

        return [
            Section::make('AI Token Usage')
                ->description('Current daily quota usage. Quotas reset automatically at midnight. Configure limits in your .env file.')
                ->schema([
                    Placeholder::make('prompt_usage')
                        ->label('Prompt Tokens')
                        ->content(fn () => new HtmlString(self::formatUsage($promptStats)))
                        ->helperText('Tokens used for AI prompts (input)'),
                    Placeholder::make('response_usage')
                        ->label('Response Tokens')
                        ->content(fn () => new HtmlString(self::formatUsage($responseStats)))
                        ->helperText('Tokens used for AI responses (output)'),
                    Placeholder::make('resets_at')
                        ->label('Quota Resets At')
                        ->content(fn () => now()->endOfDay()->format('M j, Y g:i A')),
                ]),
            Section::make('Quota Limits')
                ->description('Quota limits are configured in the .env file. Set to 0 to disable limits.')
                ->schema([
                    Placeholder::make('prompt_limit')
                        ->label('AI_PROMPT_QUOTA')
                        ->content(fn () => number_format($promptStats['limit']).' tokens/day'),
                    Placeholder::make('response_limit')
                        ->label('AI_RESPONSE_QUOTA')
                        ->content(fn () => number_format($responseStats['limit']).' tokens/day'),
                ]),
        ];
    }

    protected static function formatUsage(array $stats): string
    {
        $usage = number_format($stats['usage']);
        $limit = number_format($stats['limit']);
        $percentage = $stats['percentage_used'];

        $color = match (true) {
            $percentage >= 90 => 'text-red-600 dark:text-red-400',
            $percentage >= 75 => 'text-yellow-600 dark:text-yellow-400',
            default => 'text-green-600 dark:text-green-400',
        };

        return "<span class=\"{$color} font-semibold\">{$usage}</span> / {$limit} ({$percentage}%)";
    }
}
