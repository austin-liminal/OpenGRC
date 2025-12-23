<?php

namespace App\Filament\Resources\VendorDocumentResource\Pages;

use App\Filament\Resources\VendorDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorDocuments extends ListRecords
{
    protected static string $resource = VendorDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
