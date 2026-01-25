<?php

namespace App\Filament\Resources\TrustCenterDocumentResource\Pages;

use App\Filament\Resources\TrustCenterDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrustCenterDocuments extends ListRecords
{
    protected static string $resource = TrustCenterDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
