<?php

namespace App\Filament\Admin\Resources\VendorUserResource\Pages;

use App\Filament\Admin\Resources\VendorUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorUser extends EditRecord
{
    protected static string $resource = VendorUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
