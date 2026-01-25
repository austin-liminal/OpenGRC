<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Filament\Resources\DataRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataRequests extends ListRecords
{
    protected static string $resource = DataRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
