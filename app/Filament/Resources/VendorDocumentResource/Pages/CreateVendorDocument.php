<?php

namespace App\Filament\Resources\VendorDocumentResource\Pages;

use App\Filament\Resources\VendorDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorDocument extends CreateRecord
{
    protected static string $resource = VendorDocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
