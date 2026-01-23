<?php

namespace App\Filament\Resources\StandardResource\Pages;

use App\Filament\Resources\StandardResource;
use App\Filament\Widgets\TableDescriptionWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStandards extends ListRecords
{
    protected static string $resource = StandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TableDescriptionWidget::make(['description' => __('standard.table.description')]),
        ];
    }
}
