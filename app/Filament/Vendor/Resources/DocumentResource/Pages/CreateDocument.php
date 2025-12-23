<?php

namespace App\Filament\Vendor\Resources\DocumentResource\Pages;

use App\Enums\VendorDocumentStatus;
use App\Filament\Vendor\Resources\DocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendorUser = Auth::guard('vendor')->user();

        $data['vendor_id'] = $vendorUser->vendor_id;
        $data['uploaded_by'] = $vendorUser->id;
        $data['status'] = VendorDocumentStatus::PENDING;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
