<?php

namespace App\Filament\Admin\Resources\VendorUserResource\Pages;

use App\Filament\Admin\Resources\VendorUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorUsers extends ListRecords
{
    protected static string $resource = VendorUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
