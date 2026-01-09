<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AuditListWidget;
use App\Filament\Widgets\ControlsStatsWidget;
use App\Filament\Widgets\ImplementationsStatsWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\ToDoListWidget;

class Dashboard extends TabbedPage
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            ControlsStatsWidget::class,
            AuditListWidget::class,
            ImplementationsStatsWidget::class,
            ToDoListWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }
}
