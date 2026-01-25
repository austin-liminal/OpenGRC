<?php

namespace App\Filament\Resources\VendorDocumentResource\Pages;

use App\Filament\Resources\VendorDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorDocument extends EditRecord
{
    protected static string $resource = VendorDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
