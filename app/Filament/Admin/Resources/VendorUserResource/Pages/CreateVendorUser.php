<?php

namespace App\Filament\Admin\Resources\VendorUserResource\Pages;

use App\Filament\Admin\Resources\VendorUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorUser extends CreateRecord
{
    protected static string $resource = VendorUserResource::class;

    protected function afterCreate(): void
    {
        // TODO: Send invitation email with magic link
        VendorUserResource::resendInvitation($this->record);
    }
}
