<?php

namespace App\Filament\Resources\TrustCenterContentBlockResource\Pages;

use App\Filament\Resources\TrustCenterContentBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrustCenterContentBlocks extends ListRecords
{
    protected static string $resource = TrustCenterContentBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
