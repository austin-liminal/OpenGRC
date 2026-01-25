<?php

namespace App\Filament\Vendor\Resources\DocumentResource\Pages;

use App\Filament\Vendor\Resources\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Upload Document'),
        ];
    }
}
