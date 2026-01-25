<?php

namespace App\Filament\Resources\AuditResource\Widgets;

use App\Enums\WorkflowStatus;
use App\Models\Audit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AuditStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $totalAudited = Audit::count();
        $totalInProgress = Audit::where('status', WorkflowStatus::INPROGRESS)->count();
        $totalCompleted = Audit::where('status', WorkflowStatus::COMPLETED)->count();

        return [
            Stat::make('Total Audits', $totalAudited),
            Stat::make('In Progress', $totalInProgress),
            Stat::make('Completed', $totalCompleted),
        ];
    }
}
