<?php

namespace App\Console\Commands;

use App\Enums\QuotaType;
use App\Services\QuotaService;
use Illuminate\Console\Command;

class ResetAiQuota extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quota:reset
                            {--type= : Reset a specific quota type (ai_prompt, ai_response). If not specified, resets all AI quotas.}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset AI quota usage for the current day';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $force = $this->option('force');

        // Determine which quotas to reset
        $quotasToReset = [];
        if ($type) {
            $quotaType = QuotaType::tryFrom($type);
            if (! $quotaType) {
                $this->error("Invalid quota type: {$type}");
                $this->info('Valid types: '.implode(', ', array_column(QuotaType::cases(), 'value')));

                return Command::FAILURE;
            }
            $quotasToReset[] = $quotaType;
        } else {
            // Reset all AI-related quotas
            $quotasToReset = [QuotaType::AI_PROMPT, QuotaType::AI_RESPONSE];
        }

        // Show current usage before reset
        $this->info('Current quota usage:');
        $this->newLine();

        foreach ($quotasToReset as $quotaType) {
            $stats = QuotaService::getStats($quotaType);
            $this->line(sprintf(
                '  %s: %s / %s (%s%%)',
                $stats['label'],
                number_format($stats['usage']),
                number_format($stats['limit']),
                $stats['percentage_used']
            ));
        }

        $this->newLine();

        // Confirm reset
        if (! $force && ! $this->confirm('Are you sure you want to reset these quotas?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Perform reset
        foreach ($quotasToReset as $quotaType) {
            QuotaService::reset($quotaType);
            $this->info("✓ Reset {$quotaType->getLabel()}");
        }

        $this->newLine();
        $this->info('Quota reset complete.');

        return Command::SUCCESS;
    }
}
