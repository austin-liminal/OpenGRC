<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Filament\Resources\AuditResource\Widgets\AuditStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('audit.actions.create')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AuditStatsWidget::class,
        ];
    }
}
