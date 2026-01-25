<?php

namespace App\Filament\Resources\StandardResource\Pages;

use App\Filament\Resources\StandardResource;
use App\Filament\Widgets\TableDescriptionWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStandards extends ListRecords
{
    protected static string $resource = StandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TableDescriptionWidget::make(['description' => __('standard.table.description')]),
        ];
    }
}
