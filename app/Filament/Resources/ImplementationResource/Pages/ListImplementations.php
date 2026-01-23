<?php

namespace App\Filament\Resources\ImplementationResource\Pages;

use App\Filament\Resources\ImplementationResource;
use App\Filament\Widgets\TableDescriptionWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImplementations extends ListRecords
{
    protected static string $resource = ImplementationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('implementation.actions.create')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TableDescriptionWidget::make(['description' => __('implementation.table.description')]),
        ];
    }
}
